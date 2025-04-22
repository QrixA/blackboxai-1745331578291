<?php
// Disable direct access
if (!defined('BASE_URL')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data) {
    error_log("Invalid callback data received");
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Invalid input']));
}

try {
    // Initialize payment controller
    $payment = new PaymentController();
    
    // Process callback
    $result = $payment->handleCallback($data);
    
    if ($result) {
        // Log success
        error_log("Payment callback processed successfully: " . $input);
        
        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Callback processed successfully'
        ]);
    } else {
        // Log failure
        error_log("Failed to process payment callback: " . $input);
        
        // Return error response
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to process callback'
        ]);
    }
} catch (Exception $e) {
    // Log error
    error_log("Error processing payment callback: " . $e->getMessage());
    error_log("Callback data: " . $input);
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]);
}
?>
