<?php
if (!isLoggedIn()) {
    redirect('index.php?page=login');
}
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Order Services</h1>
    <p class="text-gray-600">Choose from our range of cloud hosting solutions.</p>
</div>

<?php $flash = getFlashMessage(); ?>
<?php if ($flash): ?>
    <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>" role="alert">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Categories Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
    <div class="p-6 border-b border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800">Select Category</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($categories as $category): ?>
                <a href="?page=services/order&category=<?= $category['id'] ?>" 
                   class="flex items-center p-4 rounded-lg border <?= ($selectedCategory == $category['id']) ? 
                          'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-purple-500 hover:bg-purple-50' ?> 
                          transition-colors duration-200">
                    <div class="mr-4">
                        <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-server text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-base font-medium text-gray-800"><?= htmlspecialchars($category['name']) ?></h3>
                        <p class="text-sm text-gray-500"><?= $category['product_count'] ?> products</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Products Section -->
<?php if ($selectedCategory): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <?= htmlspecialchars($product['name']) ?>
                        </h3>
                        <span class="px-3 py-1 text-sm text-purple-700 bg-purple-100 rounded-full">
                            Available
                        </span>
                    </div>

                    <!-- Specifications -->
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-memory w-5 text-gray-400"></i>
                            <span class="ml-2"><?= htmlspecialchars($product['ram']) ?> RAM</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-microchip w-5 text-gray-400"></i>
                            <span class="ml-2"><?= htmlspecialchars($product['cpu']) ?> CPU</span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-hdd w-5 text-gray-400"></i>
                            <span class="ml-2"><?= htmlspecialchars($product['disk']) ?> Storage</span>
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="mb-6">
                        <div class="text-3xl font-bold text-gray-800">
                            Rp <?= number_format($product['price'], 0, ',', '.') ?>
                        </div>
                        <div class="text-sm text-gray-500">per month</div>
                    </div>

                    <!-- Order Button -->
                    <form method="POST" action="index.php?page=services/order" 
                          onsubmit="return confirm('Are you sure you want to order this service?');">
                        <input type="hidden" name="action" value="create_order">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <button type="submit" 
                                class="w-full flex justify-center items-center px-4 py-2 border border-transparent 
                                       rounded-lg shadow-sm text-sm font-medium text-white bg-purple-600 
                                       hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 
                                       focus:ring-purple-500 transition-colors duration-200">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Order Now
                        </button>
                    </form>
                </div>

                <!-- Features List -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <h4 class="text-sm font-medium text-gray-800 mb-3">Features included:</h4>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 w-4"></i>
                            <span class="ml-2">Full Root Access</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 w-4"></i>
                            <span class="ml-2">DDoS Protection</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 w-4"></i>
                            <span class="ml-2">24/7 Support</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 w-4"></i>
                            <span class="ml-2">99.9% Uptime</span>
                        </li>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- Category Selection Prompt -->
    <div class="text-center text-gray-500 py-8">
        <p>Please select a category to view available products.</p>
    </div>
<?php endif; ?>

<script>
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
