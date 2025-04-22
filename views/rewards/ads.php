<?php
if (!isLoggedIn()) {
    redirect('index.php?page=login');
}

$reward = new RewardController();
$settings = $reward->getRewardSettings();
?>

<!-- Ad Reward Page -->
<div class="min-h-screen bg-gray-100 py-12">
    <div class="max-w-3xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <!-- Header -->
            <div class="p-6 bg-purple-600 text-white">
                <h1 class="text-2xl font-bold">Watch Ads & Earn</h1>
                <p class="mt-2 text-purple-100">
                    Watch ads to earn instant rewards!
                </p>
            </div>

            <!-- Ad Section -->
            <div class="p-6">
                <div id="ad-container" class="mb-6">
                    <!-- Ad iframe will be inserted here -->
                    <div class="aspect-video bg-gray-100 rounded-lg flex items-center justify-center" id="ad-placeholder">
                        <button onclick="startAd()" 
                                class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                                id="start-button">
                            <i class="fas fa-play mr-2"></i>
                            Watch Ad
                        </button>
                    </div>
                </div>

                <!-- Timer -->
                <div class="text-center mb-6 hidden" id="timer-container">
                    <div class="text-4xl font-bold text-gray-800 mb-2" id="timer">
                        <?= $settings['ad_required_seconds'] ?>
                    </div>
                    <p class="text-gray-600">Seconds Remaining</p>
                </div>

                <!-- Info -->
                <div class="space-y-4 text-center">
                    <p class="text-gray-600">
                        Watch for <span class="font-semibold"><?= $settings['ad_required_seconds'] ?> seconds</span> to earn
                        <span class="font-semibold">Rp <?= number_format($settings['ad_reward_amount'], 0, ',', '.') ?></span>
                    </p>
                    <p class="text-sm text-gray-500" id="daily-limit">
                        <!-- Will be updated by JavaScript -->
                    </p>
                </div>
            </div>

            <!-- History Section -->
            <div class="border-t border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Recent Rewards</h2>
                <div class="space-y-4" id="rewards-history">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ad Complete Modal -->
<div id="reward-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 50;">
    <div class="bg-white rounded-xl shadow-lg max-w-md w-full mx-auto m-4 p-6 text-center">
        <div class="mb-4">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <i class="fas fa-check text-green-500 text-2xl"></i>
            </div>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Reward Earned!</h3>
        <p class="text-gray-600 mb-6">
            You've earned <span class="font-semibold">Rp <?= number_format($settings['ad_reward_amount'], 0, ',', '.') ?></span>
        </p>
        <button onclick="closeRewardModal()" 
                class="w-full px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
            Continue
        </button>
    </div>
</div>

<script>
let adTimer;
let remainingTime = <?= $settings['ad_required_seconds'] ?>;
let isWatching = false;
const requiredSeconds = <?= $settings['ad_required_seconds'] ?>;

// Start ad watching
function startAd() {
    if (isWatching) return;
    isWatching = true;

    // Hide start button and show timer
    document.getElementById('start-button').style.display = 'none';
    document.getElementById('timer-container').classList.remove('hidden');

    // Start countdown
    adTimer = setInterval(() => {
        remainingTime--;
        document.getElementById('timer').textContent = remainingTime;

        if (remainingTime <= 0) {
            completeAd();
        }
    }, 1000);

    // TODO: Load actual ad content
    document.getElementById('ad-placeholder').innerHTML = `
        <div class="text-center text-gray-500">
            <i class="fas fa-video text-4xl mb-2"></i>
            <p>Ad Playing...</p>
        </div>
    `;
}

// Complete ad watching
function completeAd() {
    clearInterval(adTimer);
    isWatching = false;

    fetch('index.php?page=rewards/claim', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'ad_reward',
            duration: requiredSeconds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.reward_amount) {
            showRewardModal();
            updateDailyLimit(data.daily_watches, data.remaining_watches);
            loadRewardHistory();
        }
    })
    .catch(error => console.error('Error:', error));
}

// Show reward modal
function showRewardModal() {
    document.getElementById('reward-modal').classList.remove('hidden');
}

// Close reward modal
function closeRewardModal() {
    document.getElementById('reward-modal').classList.add('hidden');
    resetAd();
}

// Reset ad state
function resetAd() {
    clearInterval(adTimer);
    isWatching = false;
    remainingTime = requiredSeconds;
    
    document.getElementById('timer').textContent = remainingTime;
    document.getElementById('timer-container').classList.add('hidden');
    document.getElementById('start-button').style.display = 'block';
    document.getElementById('ad-placeholder').innerHTML = `
        <button onclick="startAd()" 
                class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                id="start-button">
            <i class="fas fa-play mr-2"></i>
            Watch Ad
        </button>
    `;
}

// Update daily limit display
function updateDailyLimit(watched, remaining) {
    document.getElementById('daily-limit').textContent = 
        `You've watched ${watched}/10 ads today. ${remaining} remaining.`;
}

// Load reward history
function loadRewardHistory() {
    fetch('index.php?page=rewards/history')
        .then(response => response.json())
        .then(data => {
            const historyContainer = document.getElementById('rewards-history');
            historyContainer.innerHTML = '';

            data.filter(item => item.type === 'ad')
                .slice(0, 5)
                .forEach(reward => {
                    const item = document.createElement('div');
                    item.className = 'flex justify-between items-center p-4 bg-gray-50 rounded-lg';
                    item.innerHTML = `
                        <div>
                            <div class="text-sm font-medium text-gray-800">
                                Earned Rp ${reward.reward_amount.toLocaleString()}
                            </div>
                            <div class="text-xs text-gray-500">
                                ${new Date(reward.created_at).toLocaleString()}
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">
                            ${reward.duration} seconds
                        </div>
                    `;
                    historyContainer.appendChild(item);
                });
        })
        .catch(error => console.error('Error:', error));
}

// Page visibility handling
document.addEventListener('visibilitychange', () => {
    if (document.hidden && isWatching) {
        clearInterval(adTimer);
        resetAd();
        alert('Ad watching cancelled. Please keep the page visible.');
    }
});

// Initialize
loadRewardHistory();

// Check daily limit on load
fetch('index.php?page=rewards/status')
    .then(response => response.json())
    .then(data => {
        updateDailyLimit(data.daily_watches, data.remaining_watches);
    })
    .catch(error => console.error('Error:', error));
</script>
