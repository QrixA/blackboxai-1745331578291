<?php
if (!isLoggedIn()) {
    redirect('index.php?page=login');
}
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Active Services</h1>
    <p class="text-gray-600">Manage your active cloud services and access control panels.</p>
</div>

<?php $flash = getFlashMessage(); ?>
<?php if ($flash): ?>
    <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>" role="alert">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<?php if (empty($activeServices)): ?>
    <!-- Empty State -->
    <div class="bg-white rounded-xl shadow-sm p-8 text-center border border-gray-100">
        <div class="mb-4">
            <div class="mx-auto w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-server text-purple-600 text-2xl"></i>
            </div>
        </div>
        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Active Services</h3>
        <p class="text-gray-600 mb-6">You don't have any active services yet. Start by ordering your first service!</p>
        <a href="index.php?page=services/order" 
           class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base 
                  font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 
                  focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
            <i class="fas fa-shopping-cart mr-2"></i>
            Order New Service
        </a>
    </div>
<?php else: ?>
    <!-- Services Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php foreach ($activeServices as $service): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="bg-purple-100 rounded-lg p-2">
                                <i class="fas fa-server text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?= htmlspecialchars($service['server_name']) ?>
                                </h3>
                                <p class="text-sm text-gray-500">
                                    <?= htmlspecialchars($service['product_name']) ?>
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 text-sm text-green-700 bg-green-100 rounded-full">
                            Active
                        </span>
                    </div>

                    <!-- Server Specs -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-sm font-medium text-gray-500 mb-1">RAM</div>
                            <div class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($service['ram']) ?></div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-sm font-medium text-gray-500 mb-1">CPU</div>
                            <div class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($service['cpu']) ?></div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-sm font-medium text-gray-500 mb-1">Disk</div>
                            <div class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($service['disk']) ?></div>
                        </div>
                    </div>

                    <!-- IP Address and Control Panel -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">IP Address</p>
                            <p class="text-base font-medium text-gray-800">
                                <?= htmlspecialchars($service['ip_address'] ?: 'Not assigned') ?>
                            </p>
                        </div>
                        <a href="https://pterod.sakuraid.cloud" target="_blank"
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm 
                                  text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 
                                  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 
                                  transition-colors duration-200">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            Control Panel
                        </a>
                    </div>
                </div>

                <!-- Service Footer -->
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-100">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">
                            Activated on <?= date('M d, Y', strtotime($service['created_at'])) ?>
                        </span>
                        <button onclick="showServiceDetails('<?= $service['id'] ?>')"
                                class="text-purple-600 hover:text-purple-700 font-medium">
                            <i class="fas fa-info-circle mr-1"></i>
                            Details
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Action -->
    <div class="mt-8">
        <a href="index.php?page=services/order" 
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm 
                  text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 
                  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 
                  transition-colors duration-200">
            <i class="fas fa-plus mr-2"></i>
            Add New Service
        </a>
    </div>
<?php endif; ?>

<script>
    // Function to show service details (can be implemented with a modal)
    function showServiceDetails(serviceId) {
        // Implementation for showing service details
        alert('Service details functionality will be implemented here');
    }

    // Add fade out effect to flash messages
    document.addEventListener('DOMContentLoaded', function() {
        const flashMessage = document.querySelector('[role="alert"]');
        if (flashMessage) {
            setTimeout(() => {
                flashMessage.style.transition = 'opacity 1s ease-in-out';
                flashMessage.style.opacity = '0';
                setTimeout(() => flashMessage.remove(), 1000);
            }, 3000);
        }
    });
</script>
