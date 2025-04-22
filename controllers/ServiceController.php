<?php
class ServiceController {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function getActiveServices() {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.*, p.name as product_name, p.ram, p.disk, p.cpu 
                FROM services s 
                JOIN products p ON s.product_id = p.id 
                WHERE s.user_id = ? AND s.status = 1 
                ORDER BY s.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching active services: " . $e->getMessage());
            return [];
        }
    }

    public function getCategories() {
        try {
            $stmt = $this->conn->query("
                SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id 
                WHERE c.status = 1 
                GROUP BY c.id 
                ORDER BY c.name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }

    public function getProductsByCategory($categoryId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM products 
                WHERE category_id = ? AND status = 1 
                ORDER BY price ASC
            ");
            $stmt->execute([$categoryId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return [];
        }
    }

    public function createOrder($productId) {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Get product details
            $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Product not found");
            }

            // Create order
            $stmt = $this->conn->prepare("
                INSERT INTO orders (user_id, product_id, order_number, total_amount, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $orderNumber = 'ORD-' . strtoupper(uniqid());
            $stmt->execute([
                $_SESSION['user_id'],
                $productId,
                $orderNumber,
                $product['price']
            ]);

            $orderId = $this->conn->lastInsertId();

            // Create service (will be activated after payment confirmation)
            $stmt = $this->conn->prepare("
                INSERT INTO services (user_id, product_id, server_name, status) 
                VALUES (?, ?, ?, 0)
            ");
            $serverName = "srv-" . strtolower(uniqid());
            $stmt->execute([
                $_SESSION['user_id'],
                $productId,
                $serverName
            ]);

            $this->conn->commit();
            return $orderNumber;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error creating order: " . $e->getMessage());
            throw $e;
        }
    }

    public function processOrder() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
            try {
                $productId = (int)$_POST['product_id'];
                $orderNumber = $this->createOrder($productId);
                setFlashMessage('success', 'Order created successfully! Order number: ' . $orderNumber);
                redirect('index.php?page=services/active');
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to create order. Please try again.');
                redirect('index.php?page=services/order');
            }
        }
    }
}

// Initialize controller
$service = new ServiceController();

// Handle order processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_order':
            $service->processOrder();
            break;
    }
}

// Get data for views
$activeServices = $service->getActiveServices();
$categories = $service->getCategories();

// Get products if category is selected
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : null;
$products = $selectedCategory ? $service->getProductsByCategory($selectedCategory) : [];
?>
