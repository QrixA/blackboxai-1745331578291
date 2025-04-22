<?php
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$cart = new CartController();

try {
    switch ($data['action']) {
        case 'update':
            if (!isset($data['cart_id'])) {
                throw new Exception("Cart ID is required");
            }
            $result = $cart->updateCartItem($data['cart_id'], $_SESSION['user_id'], $data);
            echo json_encode(['success' => $result]);
            break;

        case 'remove':
            if (!isset($data['cart_id'])) {
                throw new Exception("Cart ID is required");
            }
            $result = $cart->removeFromCart($data['cart_id'], $_SESSION['user_id']);
            echo json_encode(['success' => $result]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
