<?php
require_once 'config/config.php';

// Get the requested page from URL
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Pages that don't require authentication
$public_pages = ['login', 'register'];

// Check if user needs to be authenticated
if (!in_array($page, $public_pages) && !isLoggedIn()) {
    redirect('index.php?page=login');
}

// Admin only pages
$admin_pages = [
    'admin/orders', 
    'admin/services', 
    'admin/settings',
    'admin/users'
];

// Check if page requires admin access
if (in_array($page, $admin_pages) && !isAdmin()) {
    redirect('index.php?page=dashboard');
}

// Define the header and footer paths
$header = 'views/layouts/header.php';
$footer = 'views/layouts/footer.php';

// Handle AJAX/API endpoints
$api_endpoints = [
    'payment/callback',
    'payment/status',
    'payment/create',
    'cart/update',
    'cart/remove',
    'cart/items',
    'cart/count',
    'rewards/claim',
    'rewards/history',
    'rewards/status'
];

if (in_array($page, $api_endpoints)) {
    require_once "views/{$page}.php";
    exit;
}

// Handle routing
switch ($page) {
    // Auth Routes
    case 'login':
        require_once 'controllers/AuthController.php';
        require_once 'views/auth/login.php';
        break;
        
    case 'register':
        require_once 'controllers/AuthController.php';
        require_once 'views/auth/register.php';
        break;
        
    case 'logout':
        require_once 'controllers/AuthController.php';
        $auth = new AuthController();
        $auth->logout();
        break;

    // Dashboard
    case 'dashboard':
        require_once $header;
        require_once 'controllers/DashboardController.php';
        require_once 'views/dashboard/index.php';
        require_once $footer;
        break;

    // Services
    case 'services/active':
        require_once $header;
        require_once 'controllers/ServiceController.php';
        require_once 'views/services/active.php';
        require_once $footer;
        break;
        
    case 'services/order':
        require_once $header;
        require_once 'controllers/ServiceController.php';
        require_once 'views/services/order.php';
        require_once $footer;
        break;

    // Cart
    case 'cart':
        require_once $header;
        require_once 'controllers/CartController.php';
        require_once 'views/cart/index.php';
        require_once $footer;
        break;

    // User Profile
    case 'user/profile':
        require_once $header;
        require_once 'controllers/UserController.php';
        require_once 'views/user/profile.php';
        require_once $footer;
        break;

    // Rewards
    case 'rewards/afk':
        require_once $header;
        require_once 'controllers/RewardController.php';
        require_once 'views/rewards/afk.php';
        require_once $footer;
        break;

    case 'rewards/ads':
        require_once $header;
        require_once 'controllers/RewardController.php';
        require_once 'views/rewards/ads.php';
        require_once $footer;
        break;

    // Affiliate
    case 'affiliate/info':
        require_once $header;
        require_once 'controllers/AffiliateController.php';
        require_once 'views/affiliate/info.php';
        require_once $footer;
        break;

    // Admin Routes
    case 'admin/orders':
        require_once $header;
        require_once 'controllers/AdminController.php';
        require_once 'views/admin/orders.php';
        require_once $footer;
        break;
        
    case 'admin/services':
        require_once $header;
        require_once 'controllers/AdminController.php';
        require_once 'views/admin/services.php';
        require_once $footer;
        break;
        
    case 'admin/users':
        require_once $header;
        require_once 'controllers/AdminController.php';
        require_once 'views/admin/users.php';
        require_once $footer;
        break;
        
    case 'admin/settings':
        require_once $header;
        require_once 'controllers/AdminController.php';
        require_once 'views/admin/settings.php';
        require_once $footer;
        break;

    // Payment Routes
    case 'payment/return':
        require_once $header;
        require_once 'controllers/PaymentController.php';
        require_once 'views/payment/return.php';
        require_once $footer;
        break;

    // Default Route
    default:
        if (isLoggedIn()) {
            redirect('index.php?page=dashboard');
        } else {
            redirect('index.php?page=login');
        }
        break;
}
?>
