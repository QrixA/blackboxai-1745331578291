<?php
// Disable direct access
if (!defined('BASE_URL')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data || !isset($data['action'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid input']));
}

try {
    // Initialize payment controller
    $payment = new PaymentController();
    
    switch ($data['action']) {
        case 'topup':
            // Validate amount
            if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] < 10000) {
                throw new Exception("Invalid amount. Minimum top-up is Rp 10.000");
            }

            // Create top-up payment
            $result = $payment->createTopUpPayment(
                $_SESSION['user_id'],
                floatval($data['amount'])
            );
            break;

        case 'checkout':
            // Get cart totals
            $cart = new CartController();
            $totals = $cart->calculateTotals($_SESSION['user_id']);

            if ($totals['total'] <= 0) {
                throw new Exception("Cart is empty");
            }

            // Create order payment
            $result = $payment->createOrderPayment(
                $_SESSION['user_id'],
                $totals['total'],
                $data['notes'] ?? null
            );

            // Clear cart after successful payment creation
            if ($result) {
                $cart->clearCart($_SESSION['user_id']);
            }
            break;

        default:
            throw new Exception("Invalid action");
    }

    // Return payment URL
    echo json_encode([
        'success' => true,
        'payment_url' => $result['payment_url'],
        'reference' => $result['reference']
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Error creating payment: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to create payment',
        'message' => $e->getMessage()
    ]);
}

// Helper function to validate amount
function validateAmount($amount) {
    // Remove any thousand separators and convert to float
    $amount = str_replace(['.', ','], ['', '.'], $amount);
    return is_numeric($amount) ? floatval($amount) : false;
}
?>
