<?php
class DashboardController {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function getStats() {
        try {
            // Get total users
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM users");
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get total orders
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM orders");
            $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get total pendapatan (revenue)
            $stmt = $this->conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'confirmed'");
            $totalPendapatan = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get total donasi tersalurkan (distributed donations)
            // Assuming 10% of confirmed orders goes to donation
            $stmt = $this->conn->query("SELECT COALESCE(SUM(total_amount * 0.1), 0) as total FROM orders WHERE status = 'confirmed'");
            $totalDonasi = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            return [
                'total_users' => $totalUsers,
                'total_orders' => $totalOrders,
                'total_pendapatan' => $totalPendapatan,
                'total_donasi' => $totalDonasi
            ];
        } catch (PDOException $e) {
            // Log error and return default values
            error_log("Error fetching dashboard stats: " . $e->getMessage());
            return [
                'total_users' => 0,
                'total_orders' => 0,
                'total_pendapatan' => 0,
                'total_donasi' => 0
            ];
        }
    }

    public function getRecentOrders() {
        try {
            $stmt = $this->conn->query("
                SELECT o.*, u.username, p.name as product_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                JOIN products p ON o.product_id = p.id 
                ORDER BY o.created_at DESC 
                LIMIT 5
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching recent orders: " . $e->getMessage());
            return [];
        }
    }

    public function getActiveServices() {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.*, p.name as product_name 
                FROM services s 
                JOIN products p ON s.product_id = p.id 
                WHERE s.user_id = ? AND s.status = 1 
                ORDER BY s.created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching active services: " . $e->getMessage());
            return [];
        }
    }
}

// Initialize controller
$dashboard = new DashboardController();
$stats = $dashboard->getStats();
$recentOrders = $dashboard->getRecentOrders();
$activeServices = $dashboard->getActiveServices();
?>
