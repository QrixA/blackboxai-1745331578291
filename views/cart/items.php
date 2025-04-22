<?php
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['items' => []]);
    exit;
}

$cart = new CartController();
$items = $cart->getCart($_SESSION['user_id']);

header('Content-Type: application/json');
echo json_encode(['items' => $items]);
?>
