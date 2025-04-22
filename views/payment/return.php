<?php
if (!isLoggedIn()) {
    redirect('index.php?page=login');
}

$reference = $_GET['reference'] ?? null;
$status = $_GET['status'] ?? null;

// Get payment details if reference exists
$payment = null;
if ($reference) {
    $paymentController = new PaymentController();
    try {
        $payment = $paymentController->getPaymentByReference($reference);
    } catch (Exception $e) {
        error_log("Error getting payment details: " . $e->getMessage());
    }
}
?>

<!-- Payment Return Page -->
<div class="min-h-screen bg-gray-100 py-12">
    <div class="max-w-xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <?php if ($status === 'success' && $payment): ?>
            <!-- Success State -->
            <div class="p-6 bg-green-600 text-white text-center">
                <div class="w-16 h-16 bg-white bg-opacity-25 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold mb-2">Payment Successful!</h1>
                <p class="text-green-100">Your payment has been processed successfully.</p>
            </div>
            
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-gray-500">Reference Number</div>
                        <div class="text-lg font-medium text-gray-900"><?= htmlspecialchars($payment['reference']) ?></div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">Amount Paid</div>
                        <div class="text-lg font-medium text-gray-900">
                            Rp <?= number_format($payment['amount'], 0, ',', '.') ?>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">Payment Type</div>
                        <div class="text-lg font-medium text-gray-900">
                            <?= ucfirst($payment['type']) ?>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">Date</div>
                        <div class="text-lg font-medium text-gray-900">
                            <?= date('d M Y H:i', strtotime($payment['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <?php if ($payment['type'] === 'order'): ?>
                <div class="mt-8">
                    <a href="<?= BASE_URL ?>/index.php?page=services/active"
                       class="block w-full text-center px-6 py-3 bg-purple-600 text-white rounded-lg 
                              hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 
                              focus:ring-offset-2 transition-colors">
                        View My Services
                    </a>
                </div>
                <?php else: ?>
                <div class="mt-8">
                    <a href="<?= BASE_URL ?>/index.php?page=user/profile"
                       class="block w-full text-center px-6 py-3 bg-purple-600 text-white rounded-lg 
                              hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 
                              focus:ring-offset-2 transition-colors">
                        Back to Profile
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php elseif ($status === 'pending' && $payment): ?>
            <!-- Pending State -->
            <div class="p-6 bg-yellow-600 text-white text-center">
                <div class="w-16 h-16 bg-white bg-opacity-25 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clock text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold mb-2">Payment Pending</h1>
                <p class="text-yellow-100">Your payment is being processed.</p>
            </div>
            
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-gray-500">Reference Number</div>
                        <div class="text-lg font-medium text-gray-900"><?= htmlspecialchars($payment['reference']) ?></div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-500">Amount</div>
                        <div class="text-lg font-medium text-gray-900">
                            Rp <?= number_format($payment['amount'], 0, ',', '.') ?>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <a href="<?= BASE_URL ?>/index.php?page=dashboard"
                       class="block w-full text-center px-6 py-3 bg-purple-600 text-white rounded-lg 
                              hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 
                              focus:ring-offset-2 transition-colors">
                        Back to Dashboard
                    </a>
                </div>
            </div>

            <?php else: ?>
            <!-- Failed/Error State -->
            <div class="p-6 bg-red-600 text-white text-center">
                <div class="w-16 h-16 bg-white bg-opacity-25 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-times text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold mb-2">Payment Failed</h1>
                <p class="text-red-100">There was a problem processing your payment.</p>
            </div>
            
            <div class="p-6">
                <p class="text-gray-600 text-center mb-8">
                    Please try again or contact support if the problem persists.
                </p>

                <div class="space-x-4 flex justify-center">
                    <a href="<?= BASE_URL ?>/index.php?page=dashboard"
                       class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 
                              focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 
                              transition-colors">
                        Back to Dashboard
                    </a>
                    <a href="https://wa.me/6289628127242" target="_blank"
                       class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 
                              focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 
                              transition-colors">
                        Contact Support
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-refresh for pending payments
<?php if ($status === 'pending' && $payment): ?>
let checkCount = 0;
const maxChecks = 10;
const checkInterval = 5000; // 5 seconds

function checkPaymentStatus() {
    if (checkCount >= maxChecks) {
        clearInterval(statusChecker);
        return;
    }

    fetch('index.php?page=payment/status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reference: '<?= $payment['reference'] ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));

    checkCount++;
}

const statusChecker = setInterval(checkPaymentStatus, checkInterval);
<?php endif; ?>
</script>
