<?php
class CartController {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    /**
     * Add item to cart
     */
    public function addToCart($userId, $productId, $billingCycle = 'monthly', $quantity = 1, $promoCode = null, $affiliateCode = null, $note = null) {
        try {
            // Check if product exists
            $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("Product not found or unavailable");
            }

            // Check if item already in cart
            $stmt = $this->conn->prepare("
                SELECT id FROM cart 
                WHERE user_id = ? AND product_id = ? AND billing_cycle = ?
            ");
            $stmt->execute([$userId, $productId, $billingCycle]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                // Update existing cart item
                $stmt = $this->conn->prepare("
                    UPDATE cart 
                    SET quantity = quantity + ?,
                        promo_code = ?,
                        affiliate_code = ?,
                        note = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$quantity, $promoCode, $affiliateCode, $note, $existingItem['id']]);
            } else {
                // Add new cart item
                $stmt = $this->conn->prepare("
                    INSERT INTO cart (
                        user_id, product_id, billing_cycle, quantity, 
                        promo_code, affiliate_code, note
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId, $productId, $billingCycle, $quantity,
                    $promoCode, $affiliateCode, $note
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error adding to cart: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update cart item
     */
    public function updateCartItem($cartId, $userId, $data) {
        try {
            $updates = [];
            $params = [];

            if (isset($data['quantity'])) {
                $updates[] = "quantity = ?";
                $params[] = max(1, intval($data['quantity']));
            }
            if (isset($data['billing_cycle'])) {
                $updates[] = "billing_cycle = ?";
                $params[] = $data['billing_cycle'];
            }
            if (isset($data['promo_code'])) {
                $updates[] = "promo_code = ?";
                $params[] = $data['promo_code'];
            }
            if (isset($data['affiliate_code'])) {
                $updates[] = "affiliate_code = ?";
                $params[] = $data['affiliate_code'];
            }
            if (isset($data['note'])) {
                $updates[] = "note = ?";
                $params[] = $data['note'];
            }

            if (empty($updates)) {
                return true;
            }

            $updates[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $userId;
            $params[] = $cartId;

            $sql = "UPDATE cart SET " . implode(", ", $updates) . " WHERE user_id = ? AND id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error updating cart item: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($cartId, $userId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            return $stmt->execute([$cartId, $userId]);
        } catch (Exception $e) {
            error_log("Error removing from cart: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get cart contents
     */
    public function getCart($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.*, p.name as product_name, 
                    CASE c.billing_cycle
                        WHEN 'hourly' THEN p.price_hourly
                        WHEN 'monthly' THEN p.price_monthly
                        WHEN 'yearly' THEN p.price_yearly
                    END as unit_price,
                    p.ram, p.cpu, p.disk
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching cart: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate cart totals
     */
    public function calculateTotals($userId) {
        try {
            $cart = $this->getCart($userId);
            $totals = [
                'subtotal' => 0,
                'tax' => 0,
                'discount' => 0,
                'total' => 0
            ];

            if (empty($cart)) {
                return $totals;
            }

            // Get tax percentage from settings
            $stmt = $this->conn->prepare("SELECT value FROM settings WHERE key_name = 'tax_percentage'");
            $stmt->execute();
            $taxPercentage = floatval($stmt->fetchColumn());

            foreach ($cart as $item) {
                $itemSubtotal = $item['unit_price'] * $item['quantity'];
                $totals['subtotal'] += $itemSubtotal;

                // Apply promo code if exists
                if ($item['promo_code']) {
                    $promoDiscount = $this->calculatePromoDiscount($item['promo_code'], $itemSubtotal);
                    $totals['discount'] += $promoDiscount;
                }
            }

            // Calculate tax
            $totals['tax'] = ($totals['subtotal'] - $totals['discount']) * ($taxPercentage / 100);

            // Calculate final total
            $totals['total'] = $totals['subtotal'] - $totals['discount'] + $totals['tax'];

            return $totals;
        } catch (Exception $e) {
            error_log("Error calculating cart totals: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate and calculate promo code discount
     */
    private function calculatePromoDiscount($promoCode, $amount) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM promo_codes 
                WHERE code = ? 
                AND status = 1 
                AND (end_date IS NULL OR end_date > CURRENT_TIMESTAMP)
                AND (max_uses IS NULL OR current_uses < max_uses)
            ");
            $stmt->execute([$promoCode]);
            $promo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$promo) {
                return 0;
            }

            if ($promo['type'] === 'percentage') {
                return $amount * ($promo['value'] / 100);
            } else {
                return min($promo['value'], $amount);
            }
        } catch (Exception $e) {
            error_log("Error calculating promo discount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear user's cart
     */
    public function clearCart($userId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            throw $e;
        }
    }
}

// Initialize controller if accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $cart = new CartController();
    
    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not authenticated");
        }

        switch ($_POST['action']) {
            case 'add':
                $result = $cart->addToCart(
                    $_SESSION['user_id'],
                    $_POST['product_id'],
                    $_POST['billing_cycle'] ?? 'monthly',
                    $_POST['quantity'] ?? 1,
                    $_POST['promo_code'] ?? null,
                    $_POST['affiliate_code'] ?? null,
                    $_POST['note'] ?? null
                );
                echo json_encode(['success' => $result]);
                break;

            case 'update':
                $result = $cart->updateCartItem(
                    $_POST['cart_id'],
                    $_SESSION['user_id'],
                    $_POST
                );
                echo json_encode(['success' => $result]);
                break;

            case 'remove':
                $result = $cart->removeFromCart(
                    $_POST['cart_id'],
                    $_SESSION['user_id']
                );
                echo json_encode(['success' => $result]);
                break;

            case 'clear':
                $result = $cart->clearCart($_SESSION['user_id']);
                echo json_encode(['success' => $result]);
                break;

            case 'get_totals':
                $totals = $cart->calculateTotals($_SESSION['user_id']);
                echo json_encode($totals);
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
