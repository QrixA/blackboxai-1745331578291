<?php
if (!isLoggedIn()) {
    redirect('index.php?page=login');
}

$user = new UserController();
$profile = $user->getProfile($_SESSION['user_id']);
?>

<!-- Profile Settings -->
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Profile Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <img src="<?= $profile['profile_photo'] ? 'uploads/profile_photos/' . $profile['profile_photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($profile['username']) ?>" 
                         alt="Profile Photo" 
                         class="w-20 h-20 rounded-full object-cover">
                    
                    <!-- Photo Upload Button -->
                    <label for="photo-upload" 
                           class="absolute bottom-0 right-0 bg-purple-600 text-white rounded-full p-2 cursor-pointer hover:bg-purple-700 transition-colors">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" id="photo-upload" name="photo" class="hidden" accept="image/*">
                </div>
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($profile['username']) ?></h1>
                    <p class="text-gray-600"><?= htmlspecialchars($profile['email']) ?></p>
                </div>
            </div>
        </div>

        <!-- Balance Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Balance</h2>
                <span class="text-2xl font-bold text-purple-600">
                    Rp <?= number_format($profile['balance'], 0, ',', '.') ?>
                </span>
            </div>
            
            <div class="flex space-x-4">
                <button onclick="showTopUpModal()" 
                        class="flex-1 bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Top Up
                </button>
                <button onclick="showBalanceHistory()"
                        class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-history mr-2"></i> History
                </button>
            </div>
        </div>

        <!-- Settings Form -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Account Settings</h2>
            
            <form id="settings-form" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($profile['username']) ?>"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($profile['email']) ?>"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
                    
                    <div>
                        <label for="current-password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" id="current-password" name="current_password"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>

                    <div>
                        <label for="new-password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" id="new-password" name="new_password"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>

                    <div>
                        <label for="confirm-password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" id="confirm-password" name="confirm_password"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            class="bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Top Up Modal -->
<div id="topup-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden" style="z-index: 50;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl shadow-lg max-w-md w-full mx-auto">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Up Balance</h3>
                
                <form id="topup-form" class="space-y-4">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (Rp)</label>
                        <input type="number" id="amount" name="amount" min="10000" step="10000"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                               placeholder="Minimum Rp 10.000">
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeTopUpModal()"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            Proceed to Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Balance History Modal -->
<div id="history-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden" style="z-index: 50;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full mx-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Balance History</h3>
                    <button onclick="closeHistoryModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="overflow-y-auto max-h-96">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="history-table-body">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Profile photo upload
document.getElementById('photo-upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action', 'upload_photo');
    formData.append('photo', file);

    fetch('index.php?page=user/profile', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to upload photo');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to upload photo');
    });
});

// Settings form submission
document.getElementById('settings-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'update_profile');

    fetch('index.php?page=user/profile', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update profile');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update profile');
    });
});

// Top Up modal
function showTopUpModal() {
    document.getElementById('topup-modal').classList.remove('hidden');
}

function closeTopUpModal() {
    document.getElementById('topup-modal').classList.add('hidden');
}

document.getElementById('topup-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const amount = document.getElementById('amount').value;
    
    fetch('index.php?page=payment/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'topup',
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.payment_url) {
            window.location.href = data.payment_url;
        } else {
            alert(data.error || 'Failed to create payment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create payment');
    });
});

// Balance history modal
function showBalanceHistory() {
    document.getElementById('history-modal').classList.remove('hidden');
    loadBalanceHistory();
}

function closeHistoryModal() {
    document.getElementById('history-modal').classList.add('hidden');
}

function loadBalanceHistory() {
    fetch('index.php?page=user/balance-history')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('history-table-body');
            tbody.innerHTML = '';

            data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${new Date(item.created_at).toLocaleDateString()}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${formatTransactionType(item.type)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm ${item.amount > 0 ? 'text-green-600' : 'text-red-600'}">
                        ${formatCurrency(item.amount)}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        ${item.description}
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load balance history');
        });
}

function formatTransactionType(type) {
    const types = {
        'topup': 'Top Up',
        'deduction': 'Deduction',
        'afk_reward': 'AFK Reward',
        'ad_reward': 'Ad Reward',
        'affiliate_commission': 'Affiliate Commission'
    };
    return types[type] || type;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(amount);
}
</script>
