<?php
class RewardController {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    /**
     * Process AFK reward
     */
    public function processAfkReward($userId, $duration) {
        try {
            $this->conn->beginTransaction();

            // Get required minutes and reward amount from settings
            $stmt = $this->conn->prepare("
                SELECT key_name, value FROM settings 
                WHERE key_name IN ('afk_required_minutes', 'afk_reward_amount')
            ");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $requiredMinutes = intval($settings['afk_required_minutes']);
            $rewardAmount = floatval($settings['afk_reward_amount']);

            // Check if duration meets requirement
            if ($duration < $requiredMinutes) {
                throw new Exception("AFK duration not met");
            }

            // Check last reward time
            $stmt = $this->conn->prepare("
                SELECT created_at 
                FROM afk_rewards 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $lastReward = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lastReward) {
                $lastRewardTime = strtotime($lastReward['created_at']);
                $cooldownPeriod = 3600; // 1 hour cooldown
                
                if (time() - $lastRewardTime < $cooldownPeriod) {
                    throw new Exception("Reward cooldown period not met");
                }
            }

            // Record AFK reward
            $stmt = $this->conn->prepare("
                INSERT INTO afk_rewards (
                    user_id, duration_minutes, reward_amount
                ) VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $duration, $rewardAmount]);

            // Update user balance
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET balance = balance + ? 
                WHERE id = ?
            ");
            $stmt->execute([$rewardAmount, $userId]);

            // Record balance history
            $stmt = $this->conn->prepare("
                INSERT INTO balance_history (
                    user_id, type, amount, description
                ) VALUES (?, 'afk_reward', ?, ?)
            ");
            $stmt->execute([
                $userId,
                $rewardAmount,
                "AFK reward for {$duration} minutes"
            ]);

            $this->conn->commit();
            return [
                'reward_amount' => $rewardAmount,
                'duration' => $duration
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error processing AFK reward: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process Ad watch reward
     */
    public function processAdReward($userId, $duration) {
        try {
            $this->conn->beginTransaction();

            // Get required seconds and reward amount from settings
            $stmt = $this->conn->prepare("
                SELECT key_name, value FROM settings 
                WHERE key_name IN ('ad_required_seconds', 'ad_reward_amount')
            ");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $requiredSeconds = intval($settings['ad_required_seconds']);
            $rewardAmount = floatval($settings['ad_reward_amount']);

            // Check if duration meets requirement
            if ($duration < $requiredSeconds) {
                throw new Exception("Ad watch duration not met");
            }

            // Check daily limit
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM ad_watches 
                WHERE user_id = ? 
                AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$userId]);
            $dailyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $dailyLimit = 10; // Maximum 10 ad rewards per day
            if ($dailyCount >= $dailyLimit) {
                throw new Exception("Daily ad watch limit reached");
            }

            // Record ad watch
            $stmt = $this->conn->prepare("
                INSERT INTO ad_watches (
                    user_id, ad_duration_seconds, reward_amount
                ) VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $duration, $rewardAmount]);

            // Update user balance
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET balance = balance + ? 
                WHERE id = ?
            ");
            $stmt->execute([$rewardAmount, $userId]);

            // Record balance history
            $stmt = $this->conn->prepare("
                INSERT INTO balance_history (
                    user_id, type, amount, description
                ) VALUES (?, 'ad_reward', ?, 'Ad watch reward')
            ");
            $stmt->execute([$userId, $rewardAmount]);

            $this->conn->commit();
            return [
                'reward_amount' => $rewardAmount,
                'daily_watches' => $dailyCount + 1,
                'remaining_watches' => $dailyLimit - ($dailyCount + 1)
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error processing ad reward: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get user's reward history
     */
    public function getRewardHistory($userId) {
        try {
            // Get AFK rewards
            $stmt = $this->conn->prepare("
                SELECT 'afk' as type,
                       duration_minutes as duration,
                       reward_amount,
                       created_at
                FROM afk_rewards 
                WHERE user_id = ?
                UNION ALL
                SELECT 'ad' as type,
                       ad_duration_seconds as duration,
                       reward_amount,
                       created_at
                FROM ad_watches 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting reward history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get reward settings
     */
    public function getRewardSettings() {
        try {
            $stmt = $this->conn->prepare("
                SELECT key_name, value 
                FROM settings 
                WHERE key_name IN (
                    'afk_required_minutes',
                    'afk_reward_amount',
                    'ad_required_seconds',
                    'ad_reward_amount'
                )
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            error_log("Error getting reward settings: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update reward settings (admin only)
     */
    public function updateRewardSettings($settings) {
        try {
            $this->conn->beginTransaction();

            foreach ($settings as $key => $value) {
                if (!in_array($key, [
                    'afk_required_minutes',
                    'afk_reward_amount',
                    'ad_required_seconds',
                    'ad_reward_amount'
                ])) {
                    continue;
                }

                $stmt = $this->conn->prepare("
                    UPDATE settings 
                    SET value = ? 
                    WHERE key_name = ?
                ");
                $stmt->execute([$value, $key]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error updating reward settings: " . $e->getMessage());
            throw $e;
        }
    }
}

// Initialize controller if accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reward = new RewardController();
    
    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not authenticated");
        }

        switch ($_POST['action']) {
            case 'afk_reward':
                if (!isset($_POST['duration'])) {
                    throw new Exception("Duration is required");
                }
                $result = $reward->processAfkReward(
                    $_SESSION['user_id'],
                    intval($_POST['duration'])
                );
                echo json_encode($result);
                break;

            case 'ad_reward':
                if (!isset($_POST['duration'])) {
                    throw new Exception("Duration is required");
                }
                $result = $reward->processAdReward(
                    $_SESSION['user_id'],
                    intval($_POST['duration'])
                );
                echo json_encode($result);
                break;

            case 'get_history':
                $history = $reward->getRewardHistory($_SESSION['user_id']);
                echo json_encode($history);
                break;

            case 'update_settings':
                if (!isAdmin()) {
                    throw new Exception("Unauthorized");
                }
                $result = $reward->updateRewardSettings($_POST['settings']);
                echo json_encode(['success' => $result]);
                break;

            default:
                throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
