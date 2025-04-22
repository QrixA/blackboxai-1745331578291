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
if (!$data || !isset($data['reference'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid input']));
}

try {
    // Initialize payment controller
    $payment = new PaymentController();
    
    // Get payment details
    $paymentDetails = $payment->getPaymentByReference($data['reference']);
    
    // Verify payment belongs to current user
    if ($paymentDetails['user_id'] !== $_SESSION['user_id']) {
        http_response_code(403);
        die(json_encode(['error' => 'Forbidden']));
    }
    
    // Return payment status
    echo json_encode([
        'status' => $paymentDetails['status'],
        'reference' => $paymentDetails['reference'],
        'amount' => $paymentDetails['amount'],
        'type' => $paymentDetails['type'],
        'created_at' => $paymentDetails['created_at'],
        'updated_at' => $paymentDetails['updated_at']
    ]);
} catch (Exception $e) {
    // Log error
    error_log("Error checking payment status: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to check payment status',
        'message' => $e->getMessage()
    ]);
}
?>
