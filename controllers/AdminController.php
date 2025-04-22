<?php
class AdminController {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
        
        // Ensure admin access
        if (!isAdmin()) {
            setFlashMessage('error', 'Unauthorized access');
            redirect('index.php?page=dashboard');
        }
    }

    // Order Management
    public function getPendingOrders() {
        try {
            $stmt = $this->conn->query("
                SELECT o.*, u.username, p.name as product_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                JOIN products p ON o.product_id = p.id 
                WHERE o.status = 'pending' 
                ORDER BY o.created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching pending orders: " . $e->getMessage());
            return [];
        }
    }

    public function confirmOrder($orderId) {
        try {
            $this->conn->beginTransaction();

            // Update order status
            $stmt = $this->conn->prepare("
                UPDATE orders 
                SET status = 'confirmed', payment_status = 1 
                WHERE id = ?
            ");
            $stmt->execute([$orderId]);

            // Activate associated service
            $stmt = $this->conn->prepare("
                UPDATE services s 
                JOIN orders o ON o.product_id = s.product_id AND o.user_id = s.user_id 
                SET s.status = 1, 
                    s.ip_address = CONCAT('10.0.', FLOOR(RAND() * 255), '.', FLOOR(RAND() * 255)) 
                WHERE o.id = ?
            ");
            $stmt->execute([$orderId]);

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error confirming order: " . $e->getMessage());
            return false;
        }
    }

    // Category Management
    public function getCategories() {
        try {
            $stmt = $this->conn->query("
                SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id 
                GROUP BY c.id 
                ORDER BY c.name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }

    public function addCategory($name, $description) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO categories (name, description) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$name, $description]);
        } catch (PDOException $e) {
            error_log("Error adding category: " . $e->getMessage());
            return false;
        }
    }

    public function updateCategory($id, $name, $description, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE categories 
                SET name = ?, description = ?, status = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$name, $description, $status, $id]);
        } catch (PDOException $e) {
            error_log("Error updating category: " . $e->getMessage());
            return false;
        }
    }

    // Product Management
    public function getProducts() {
        try {
            $stmt = $this->conn->query("
                SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                ORDER BY c.name, p.name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return [];
        }
    }

    public function addProduct($categoryId, $name, $description, $ram, $cpu, $disk, $price) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO products (category_id, name, description, ram, cpu, disk, price) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$categoryId, $name, $description, $ram, $cpu, $disk, $price]);
        } catch (PDOException $e) {
            error_log("Error adding product: " . $e->getMessage());
            return false;
        }
    }

    public function updateProduct($id, $categoryId, $name, $description, $ram, $cpu, $disk, $price, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE products 
                SET category_id = ?, name = ?, description = ?, ram = ?, 
                    cpu = ?, disk = ?, price = ?, status = ? 
                WHERE id = ?
            ");
            return $stmt->execute([
                $categoryId, $name, $description, $ram, 
                $cpu, $disk, $price, $status, $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
    }

    // Settings Management
    public function getSettings() {
        try {
            $stmt = $this->conn->query("SELECT * FROM settings");
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("Error fetching settings: " . $e->getMessage());
            return [];
        }
    }

    public function updateSetting($key, $value) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO settings (key_name, value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE value = ?
            ");
            return $stmt->execute([$key, $value, $value]);
        } catch (PDOException $e) {
            error_log("Error updating setting: " . $e->getMessage());
            return false;
        }
    }

    // Process Admin Actions
    public function processAction() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'confirm_order':
                    if (isset($_POST['order_id'])) {
                        if ($this->confirmOrder($_POST['order_id'])) {
                            setFlashMessage('success', 'Order confirmed successfully');
                        } else {
                            setFlashMessage('error', 'Failed to confirm order');
                        }
                    }
                    break;

                case 'add_category':
                    if (isset($_POST['name'])) {
                        if ($this->addCategory($_POST['name'], $_POST['description'] ?? '')) {
                            setFlashMessage('success', 'Category added successfully');
                        } else {
                            setFlashMessage('error', 'Failed to add category');
                        }
                    }
                    break;

                case 'update_category':
                    if (isset($_POST['id'])) {
                        if ($this->updateCategory(
                            $_POST['id'],
                            $_POST['name'],
                            $_POST['description'] ?? '',
                            $_POST['status'] ?? 1
                        )) {
                            setFlashMessage('success', 'Category updated successfully');
                        } else {
                            setFlashMessage('error', 'Failed to update category');
                        }
                    }
                    break;

                case 'add_product':
                    if (isset($_POST['category_id'])) {
                        if ($this->addProduct(
                            $_POST['category_id'],
                            $_POST['name'],
                            $_POST['description'] ?? '',
                            $_POST['ram'],
                            $_POST['cpu'],
                            $_POST['disk'],
                            $_POST['price']
                        )) {
                            setFlashMessage('success', 'Product added successfully');
                        } else {
                            setFlashMessage('error', 'Failed to add product');
                        }
                    }
                    break;

                case 'update_product':
                    if (isset($_POST['id'])) {
                        if ($this->updateProduct(
                            $_POST['id'],
                            $_POST['category_id'],
                            $_POST['name'],
                            $_POST['description'] ?? '',
                            $_POST['ram'],
                            $_POST['cpu'],
                            $_POST['disk'],
                            $_POST['price'],
                            $_POST['status'] ?? 1
                        )) {
                            setFlashMessage('success', 'Product updated successfully');
                        } else {
                            setFlashMessage('error', 'Failed to update product');
                        }
                    }
                    break;

                case 'update_settings':
                    $success = true;
                    foreach ($_POST as $key => $value) {
                        if (strpos($key, 'setting_') === 0) {
                            $settingKey = substr($key, 8); // Remove 'setting_' prefix
                            if (!$this->updateSetting($settingKey, $value)) {
                                $success = false;
                            }
                        }
                    }
                    if ($success) {
                        setFlashMessage('success', 'Settings updated successfully');
                    } else {
                        setFlashMessage('error', 'Failed to update some settings');
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log("Error processing admin action: " . $e->getMessage());
            setFlashMessage('error', 'An error occurred while processing your request');
        }
    }
}

// Initialize controller and process any actions
$admin = new AdminController();
$admin->processAction();

// Get data for views
$pendingOrders = $admin->getPendingOrders();
$categories = $admin->getCategories();
$products = $admin->getProducts();
$settings = $admin->getSettings();
?>
