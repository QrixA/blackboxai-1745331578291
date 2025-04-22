<?php
if (!isAdmin()) {
    redirect('index.php?page=dashboard');
}
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Manage Orders</h1>
    <p class="text-gray-600">Review and process pending orders.</p>
</div>

<?php $flash = getFlashMessage(); ?>
<?php if ($flash): ?>
    <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>" role="alert">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Orders List -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-6 border-b border-gray-100">
        <h2 class="text-lg font-semibold text-gray-800">Pending Orders</h2>
    </div>

    <?php if (empty($pendingOrders)): ?>
        <div class="p-6 text-center">
            <div class="mb-4">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-gray-400 text-2xl"></i>
                </div>
            </div>
            <h3 class="text-gray-500 text-lg font-medium">No Pending Orders</h3>
            <p class="text-gray-400 mt-1">All orders have been processed</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Order Details
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Product
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($pendingOrders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($order['order_number']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= date('M d, Y H:i', strtotime($order['created_at'])) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?= htmlspecialchars($order['username']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?= htmlspecialchars($order['product_name']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    Rp <?= number_format($order['total_amount'], 0, ',', '.') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                           bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="index.php?page=admin/orders" 
                                      onsubmit="return confirm('Are you sure you want to confirm this order?');" 
                                      class="inline-block">
                                    <input type="hidden" name="action" value="confirm_order">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    
                                    <button type="submit" 
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm 
                                                   font-medium rounded-md shadow-sm text-white bg-green-600 
                                                   hover:bg-green-700 focus:outline-none focus:ring-2 
                                                   focus:ring-offset-2 focus:ring-green-500">
                                        <i class="fas fa-check mr-1.5"></i>
                                        Confirm
                                    </button>
                                </form>
                                
                                <button onclick="showOrderDetails('<?= $order['id'] ?>')"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm 
                                               font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 
                                               focus:outline-none focus:ring-2 focus:ring-offset-2 
                                               focus:ring-purple-500 ml-2">
                                    <i class="fas fa-eye mr-1.5"></i>
                                    Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Order Details Modal (Hidden by default) -->
<div id="orderDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden" style="z-index: 50;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full mx-auto">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Order Details</h3>
                <button onclick="closeOrderDetails()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="orderDetailsContent" class="p-6">
                <!-- Content will be loaded dynamically -->
                <div class="animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to show order details modal
    function showOrderDetails(orderId) {
        const modal = document.getElementById('orderDetailsModal');
        const content = document.getElementById('orderDetailsContent');
        
        // Show modal
        modal.classList.remove('hidden');
        
        // Example content (you can enhance this with AJAX calls to get real data)
        content.innerHTML = `
            <div class="space-y-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Order ID</h4>
                    <p class="mt-1 text-sm text-gray-900">${orderId}</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Additional Details</h4>
                    <p class="mt-1 text-sm text-gray-900">
                        Additional order details will be displayed here.
                    </p>
                </div>
            </div>
        `;
    }

    // Function to close order details modal
    function closeOrderDetails() {
        const modal = document.getElementById('orderDetailsModal');
        modal.classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeOrderDetails();
        }
    });

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
