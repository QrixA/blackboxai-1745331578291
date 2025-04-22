<?php
if (!isAdmin()) {
    redirect('index.php?page=dashboard');
}
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">System Settings</h1>
    <p class="text-gray-600">Configure system settings and API integrations.</p>
</div>

<?php $flash = getFlashMessage(); ?>
<?php if ($flash): ?>
    <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>" role="alert">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Settings Form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <form method="POST" action="index.php?page=admin/settings">
        <input type="hidden" name="action" value="update_settings">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <!-- Payment Gateway Settings -->
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Payment Gateway</h2>
            
            <div class="space-y-4">
                <div>
                    <label for="duitkuApiKey" class="block text-sm font-medium text-gray-700">
                        Duitku API Key
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-key text-gray-400"></i>
                        </div>
                        <input type="password" name="setting_duitku_api_key" id="duitkuApiKey"
                               value="<?= htmlspecialchars($settings['duitku_api_key'] ?? '') ?>"
                               class="focus:ring-purple-500 focus:border-purple-500 block w-full pl-10 sm:text-sm 
                                      border-gray-300 rounded-md"
                               placeholder="Enter Duitku API Key">
                        <button type="button" onclick="togglePassword('duitkuApiKey')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye text-gray-400"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Your Duitku API key for payment processing.
                    </p>
                </div>

                <div>
                    <label for="duitkuMerchantId" class="block text-sm font-medium text-gray-700">
                        Duitku Merchant ID
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-store text-gray-400"></i>
                        </div>
                        <input type="text" name="setting_duitku_merchant_id" id="duitkuMerchantId"
                               value="<?= htmlspecialchars($settings['duitku_merchant_id'] ?? '') ?>"
                               class="focus:ring-purple-500 focus:border-purple-500 block w-full pl-10 sm:text-sm 
                                      border-gray-300 rounded-md"
                               placeholder="Enter Merchant ID">
                    </div>
                </div>
            </div>
        </div>

        <!-- Server Control Panel Settings -->
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Server Control Panel</h2>
            
            <div class="space-y-4">
                <div>
                    <label for="pterodactylApiKey" class="block text-sm font-medium text-gray-700">
                        Pterodactyl API Key
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-key text-gray-400"></i>
                        </div>
                        <input type="password" name="setting_pterodactyl_api_key" id="pterodactylApiKey"
                               value="<?= htmlspecialchars($settings['pterodactyl_api_key'] ?? '') ?>"
                               class="focus:ring-purple-500 focus:border-purple-500 block w-full pl-10 sm:text-sm 
                                      border-gray-300 rounded-md"
                               placeholder="Enter Pterodactyl API Key">
                        <button type="button" onclick="togglePassword('pterodactylApiKey')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye text-gray-400"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Your Pterodactyl API key for server management.
                    </p>
                </div>

                <div>
                    <label for="pterodactylUrl" class="block text-sm font-medium text-gray-700">
                        Pterodactyl Panel URL
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-link text-gray-400"></i>
                        </div>
                        <input type="url" name="setting_pterodactyl_url" id="pterodactylUrl"
                               value="<?= htmlspecialchars($settings['pterodactyl_url'] ?? 'https://pterod.sakuraid.cloud') ?>"
                               class="focus:ring-purple-500 focus:border-purple-500 block w-full pl-10 sm:text-sm 
                                      border-gray-300 rounded-md"
                               placeholder="Enter Panel URL">
                    </div>
                </div>
            </div>
        </div>

        <!-- System Settings -->
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">System Settings</h2>
            
            <div class="space-y-4">
                <div>
                    <label for="maintenanceMode" class="block text-sm font-medium text-gray-700">
                        Maintenance Mode
                    </label>
                    <div class="mt-1">
                        <label class="inline-flex items-center">
                            <input type="radio" name="setting_maintenance_mode" value="1" 
                                   <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>
                                   class="form-radio text-purple-600">
                            <span class="ml-2">Enabled</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="setting_maintenance_mode" value="0" 
                                   <?= ($settings['maintenance_mode'] ?? '0') === '0' ? 'checked' : '' ?>
                                   class="form-radio text-purple-600">
                            <span class="ml-2">Disabled</span>
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Enable maintenance mode to prevent new orders.
                    </p>
                </div>

                <div>
                    <label for="orderPrefix" class="block text-sm font-medium text-gray-700">
                        Order Number Prefix
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-hashtag text-gray-400"></i>
                        </div>
                        <input type="text" name="setting_order_prefix" id="orderPrefix"
                               value="<?= htmlspecialchars($settings['order_prefix'] ?? 'ORD') ?>"
                               class="focus:ring-purple-500 focus:border-purple-500 block w-full pl-10 sm:text-sm 
                                      border-gray-300 rounded-md"
                               placeholder="Enter Order Prefix">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Prefix used for order numbers (e.g., ORD-123456).
                    </p>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="p-6 bg-gray-50 rounded-b-xl flex justify-end space-x-3">
            <button type="reset" 
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 
                           bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 
                           focus:ring-purple-500">
                Reset
            </button>
            <button type="submit"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium 
                           text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 
                           focus:ring-offset-2 focus:ring-purple-500">
                Save Changes
            </button>
        </div>
    </form>
</div>

<script>
    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Form submission confirmation
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to save these changes?')) {
            e.preventDefault();
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
