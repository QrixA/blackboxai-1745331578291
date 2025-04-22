<?php
class AffiliateController {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    /**
     * Apply to become an affiliate
     */
    public function applyForAffiliate($userId, $email) {
        try {
            $this->conn->beginTransaction();

            // Check if user exists and is not already an affiliate
            $stmt = $this->conn->prepare("
                SELECT is_affiliate FROM users 
                WHERE id = ? AND is_affiliate = 0
            ");
            $stmt->execute([$userId]);
            
            if (!$stmt->fetch()) {
                throw new Exception("User not eligible for affiliate program");
            }

            // Generate unique affiliate code
            $affiliateCode = $this->generateAffiliateCode();

            // Get default commission rate from settings
            $stmt = $this->conn->prepare("
                SELECT value FROM settings 
                WHERE key_name = 'default_affiliate_commission'
            ");
            $stmt->execute();
            $defaultCommission = floatval($stmt->fetchColumn());

            // Update user as affiliate
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET is_affiliate = 1,
                    affiliate_code = ?,
                    affiliate_commission = ?
                WHERE id = ?
            ");
            $stmt->execute([$affiliateCode, $defaultCommission, $userId]);

            $this->conn->commit();
            return [
                'affiliate_code' => $affiliateCode,
                'commission_rate' => $defaultCommission
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error applying for affiliate: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate affiliate code
     */
    public function validateAffiliateCode($code) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, username, affiliate_commission 
                FROM users 
                WHERE affiliate_code = ? AND is_affiliate = 1
            ");
            $stmt->execute([$code]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error validating affiliate code: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get affiliate statistics
     */
    public function getAffiliateStats($userId) {
        try {
            // Get total commission earned
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(affiliate_commission), 0) as total_commission,
                       COUNT(*) as total_orders
                FROM orders 
                WHERE affiliate_code = (
                    SELECT affiliate_code FROM users WHERE id = ?
                )
            ");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get recent referrals
            $stmt = $this->conn->prepare("
                SELECT o.*, u.username as customer_name,
                       p.name as product_name
                FROM orders o
                JOIN users u ON o.user_id = u.id
                JOIN products p ON o.product_id = p.id
                WHERE o.affiliate_code = (
                    SELECT affiliate_code FROM users WHERE id = ?
                )
                ORDER BY o.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $stats['recent_referrals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;
        } catch (Exception $e) {
            error_log("Error getting affiliate stats: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process affiliate commission
     */
    public function processCommission($orderId) {
        try {
            $this->conn->beginTransaction();

            // Get order details with affiliate info
            $stmt = $this->conn->prepare("
                SELECT o.*, u.id as affiliate_id, u.affiliate_commission
                FROM orders o
                JOIN users u ON o.affiliate_code = u.affiliate_code
                WHERE o.id = ? AND o.affiliate_code IS NOT NULL
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return false; // No affiliate commission to process
            }

            // Calculate commission amount
            $commissionAmount = $order['total_amount'] * ($order['affiliate_commission'] / 100);

            // Update affiliate's balance
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET balance = balance + ? 
                WHERE id = ?
            ");
            $stmt->execute([$commissionAmount, $order['affiliate_id']]);

            // Record in balance history
            $stmt = $this->conn->prepare("
                INSERT INTO balance_history (
                    user_id, type, amount, description
                ) VALUES (?, 'affiliate_commission', ?, ?)
            ");
            $stmt->execute([
                $order['affiliate_id'],
                $commissionAmount,
                "Commission from order #{$order['order_number']}"
            ]);

            // Update order with processed commission
            $stmt = $this->conn->prepare("
                UPDATE orders 
                SET affiliate_commission = ? 
                WHERE id = ?
            ");
            $stmt->execute([$commissionAmount, $orderId]);

            $this->conn->commit();
            return $commissionAmount;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error processing affiliate commission: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate unique affiliate code
     */
    private function generateAffiliateCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }

            // Check if code exists
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM users WHERE affiliate_code = ?
            ");
            $stmt->execute([$code]);
            $exists = $stmt->fetchColumn() > 0;

            $attempt++;
        } while ($exists && $attempt < $maxAttempts);

        if ($exists) {
            throw new Exception("Could not generate unique affiliate code");
        }

        return $code;
    }

    /**
     * Update affiliate commission rate
     */
    public function updateCommissionRate($userId, $rate) {
        try {
            if ($rate < 0 || $rate > 100) {
                throw new Exception("Invalid commission rate");
            }

            $stmt = $this->conn->prepare("
                UPDATE users 
                SET affiliate_commission = ? 
                WHERE id = ? AND is_affiliate = 1
            ");
            return $stmt->execute([$rate, $userId]);
        } catch (Exception $e) {
            error_log("Error updating commission rate: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get affiliate information
     */
    public function getAffiliateInfo($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT affiliate_code, affiliate_commission, is_affiliate 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting affiliate info: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize controller if accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $affiliate = new AffiliateController();
    
    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not authenticated");
        }

        switch ($_POST['action']) {
            case 'apply':
                $result = $affiliate->applyForAffiliate(
                    $_SESSION['user_id'],
                    $_POST['email']
                );
                echo json_encode(['success' => true, 'data' => $result]);
                break;

            case 'validate_code':
                if (empty($_POST['code'])) {
                    throw new Exception("Affiliate code is required");
                }
                $result = $affiliate->validateAffiliateCode($_POST['code']);
                echo json_encode(['valid' => (bool)$result, 'data' => $result]);
                break;

            case 'get_stats':
                $stats = $affiliate->getAffiliateStats($_SESSION['user_id']);
                echo json_encode($stats);
                break;

            case 'update_commission':
                if (!isset($_POST['rate'])) {
                    throw new Exception("Commission rate is required");
                }
                $result = $affiliate->updateCommissionRate(
                    $_SESSION['user_id'],
                    floatval($_POST['rate'])
                );
                echo json_encode(['success' => $result]);
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
