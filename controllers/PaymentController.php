<?php
class PaymentController {
    private $conn;
    private $merchantCode;
    private $apiKey;
    private $callbackUrl;
    private $returnUrl;
    private $environment;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
        
        // Get Duitku settings
        $stmt = $this->conn->prepare("
            SELECT key_name, value FROM settings 
            WHERE key_name IN ('duitku_merchant_code', 'duitku_api_key', 'duitku_callback_url')
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->merchantCode = $settings['duitku_merchant_code'] ?? '';
        $this->apiKey = $settings['duitku_api_key'] ?? '';
        $this->callbackUrl = $settings['duitku_callback_url'] ?? '';
        $this->returnUrl = BASE_URL . '/index.php?page=payment/return';
        $this->environment = 'sandbox'; // Change to 'production' for live
    }

    /**
     * Create payment for top-up
     */
    public function createTopUpPayment($userId, $amount) {
        try {
            // Generate unique payment reference
            $paymentRef = $this->generatePaymentReference();
            
            // Get user details
            $stmt = $this->conn->prepare("SELECT username, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("User not found");
            }

            // Prepare payment data
            $data = [
                'merchantCode' => $this->merchantCode,
                'paymentAmount' => $amount,
                'paymentMethod' => 'VC',
                'merchantOrderId' => $paymentRef,
                'productDetails' => 'Balance Top-up',
                'email' => $user['email'],
                'customerVaName' => $user['username'],
                'callbackUrl' => $this->callbackUrl,
                'returnUrl' => $this->returnUrl,
                'signature' => $this->generateSignature($amount, $paymentRef)
            ];

            // Call Duitku API
            $response = $this->callDuitkuApi('v2/inquiry', $data);
            
            if (!isset($response['paymentUrl'])) {
                throw new Exception("Invalid payment response");
            }

            // Record payment request
            $stmt = $this->conn->prepare("
                INSERT INTO payment_requests (
                    user_id, reference, amount, type, status, payment_url
                ) VALUES (?, ?, ?, 'topup', 'pending', ?)
            ");
            $stmt->execute([
                $userId,
                $paymentRef,
                $amount,
                $response['paymentUrl']
            ]);

            return [
                'payment_url' => $response['paymentUrl'],
                'reference' => $paymentRef
            ];
        } catch (Exception $e) {
            error_log("Error creating top-up payment: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create payment for order
     */
    public function createOrderPayment($userId, $orderId) {
        try {
            // Get order details
            $stmt = $this->conn->prepare("
                SELECT o.*, u.email, u.username 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ? AND o.user_id = ?
            ");
            $stmt->execute([$orderId, $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception("Order not found");
            }

            // Generate payment reference
            $paymentRef = $this->generatePaymentReference();

            // Prepare payment data
            $data = [
                'merchantCode' => $this->merchantCode,
                'paymentAmount' => $order['total_amount'],
                'paymentMethod' => 'VC',
                'merchantOrderId' => $paymentRef,
                'productDetails' => 'Order #' . $order['order_number'],
                'email' => $order['email'],
                'customerVaName' => $order['username'],
                'callbackUrl' => $this->callbackUrl,
                'returnUrl' => $this->returnUrl,
                'signature' => $this->generateSignature($order['total_amount'], $paymentRef)
            ];

            // Call Duitku API
            $response = $this->callDuitkuApi('v2/inquiry', $data);
            
            if (!isset($response['paymentUrl'])) {
                throw new Exception("Invalid payment response");
            }

            // Record payment request
            $stmt = $this->conn->prepare("
                INSERT INTO payment_requests (
                    user_id, order_id, reference, amount, type, status, payment_url
                ) VALUES (?, ?, ?, ?, 'order', 'pending', ?)
            ");
            $stmt->execute([
                $userId,
                $orderId,
                $paymentRef,
                $order['total_amount'],
                $response['paymentUrl']
            ]);

            return [
                'payment_url' => $response['paymentUrl'],
                'reference' => $paymentRef
            ];
        } catch (Exception $e) {
            error_log("Error creating order payment: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle payment callback
     */
    public function handleCallback($data) {
        try {
            $this->conn->beginTransaction();

            // Verify signature
            $signature = $this->generateCallbackSignature(
                $data['merchantCode'],
                $data['amount'],
                $data['merchantOrderId']
            );

            if ($signature !== $data['signature']) {
                throw new Exception("Invalid signature");
            }

            // Get payment request
            $stmt = $this->conn->prepare("
                SELECT * FROM payment_requests 
                WHERE reference = ? AND status = 'pending'
            ");
            $stmt->execute([$data['merchantOrderId']]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new Exception("Payment request not found");
            }

            // Update payment status
            $stmt = $this->conn->prepare("
                UPDATE payment_requests 
                SET status = ?, 
                    payment_code = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $data['resultCode'] === '00' ? 'success' : 'failed',
                $data['resultCode'],
                $payment['id']
            ]);

            if ($data['resultCode'] === '00') {
                if ($payment['type'] === 'topup') {
                    // Process top-up
                    $stmt = $this->conn->prepare("
                        UPDATE users 
                        SET balance = balance + ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$payment['amount'], $payment['user_id']]);

                    // Record balance history
                    $stmt = $this->conn->prepare("
                        INSERT INTO balance_history (
                            user_id, type, amount, description
                        ) VALUES (?, 'topup', ?, ?)
                    ");
                    $stmt->execute([
                        $payment['user_id'],
                        $payment['amount'],
                        "Top-up via Duitku #" . $payment['reference']
                    ]);
                } else {
                    // Process order payment
                    $stmt = $this->conn->prepare("
                        UPDATE orders 
                        SET payment_status = 1,
                            status = 'confirmed'
                        WHERE id = ?
                    ");
                    $stmt->execute([$payment['order_id']]);

                    // Process affiliate commission if applicable
                    $affiliate = new AffiliateController();
                    $affiliate->processCommission($payment['order_id']);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error handling payment callback: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate payment reference
     */
    private function generatePaymentReference() {
        return uniqid('PAY-', true);
    }

    /**
     * Generate signature for payment request
     */
    private function generateSignature($amount, $merchantOrderId) {
        $signature = $this->merchantCode . $amount . $merchantOrderId . $this->apiKey;
        return md5($signature);
    }

    /**
     * Generate signature for callback verification
     */
    private function generateCallbackSignature($merchantCode, $amount, $merchantOrderId) {
        $signature = $merchantCode . $amount . $merchantOrderId . $this->apiKey;
        return md5($signature);
    }

    /**
     * Call Duitku API
     */
    private function callDuitkuApi($endpoint, $data) {
        $baseUrl = $this->environment === 'production' 
            ? 'https://api.duitku.com/' 
            : 'https://api-sandbox.duitku.com/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("API request failed with code " . $httpCode);
        }

        $result = json_decode($response, true);
        if (!$result) {
            throw new Exception("Invalid API response");
        }

        return $result;
    }
}

// Initialize controller if accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment = new PaymentController();
    
    try {
        // Handle callback
        if (isset($_GET['callback'])) {
            $result = $payment->handleCallback($_POST);
            echo json_encode(['success' => $result]);
            exit;
        }

        // Handle payment requests
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not authenticated");
        }

        switch ($_POST['action'] ?? '') {
            case 'topup':
                if (!isset($_POST['amount']) || floatval($_POST['amount']) <= 0) {
                    throw new Exception("Invalid amount");
                }
                $result = $payment->createTopUpPayment(
                    $_SESSION['user_id'],
                    floatval($_POST['amount'])
                );
                echo json_encode($result);
                break;

            case 'pay_order':
                if (!isset($_POST['order_id'])) {
                    throw new Exception("Order ID is required");
                }
                $result = $payment->createOrderPayment(
                    $_SESSION['user_id'],
                    $_POST['order_id']
                );
                echo json_encode($result);
                break;

            default:
                throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
