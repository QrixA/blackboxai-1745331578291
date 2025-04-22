<?php
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['count' => 0]);
    exit;
}

$cart = new CartController();
$items = $cart->getCart($_SESSION['user_id']);
$count = count($items);

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?>
