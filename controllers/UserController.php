<?php
class UserController {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    /**
     * Get user profile data
     */
    public function getProfile($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, username, email, balance, profile_photo, is_affiliate, affiliate_code, affiliate_commission 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching user profile: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $updates = [];
            $params = [];

            // Handle basic info updates
            if (isset($data['username'])) {
                $updates[] = "username = ?";
                $params[] = $data['username'];
            }
            if (isset($data['email'])) {
                $updates[] = "email = ?";
                $params[] = $data['email'];
            }

            // Handle password update
            if (!empty($data['new_password'])) {
                if (password_verify($data['current_password'], $this->getUserPassword($userId))) {
                    $updates[] = "password = ?";
                    $params[] = password_hash($data['new_password'], PASSWORD_DEFAULT);
                } else {
                    throw new Exception("Current password is incorrect");
                }
            }

            if (empty($updates)) {
                return true;
            }

            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error updating user profile: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle profile photo upload
     */
    public function updateProfilePhoto($userId, $file) {
        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload failed");
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $file['tmp_name']);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Invalid file type");
            }

            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                throw new Exception("File too large");
            }

            $fileName = uniqid() . '_' . basename($file['name']);
            $uploadDir = __DIR__ . '/../public/uploads/profile_photos/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filePath = $uploadDir . $fileName;
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception("Failed to save file");
            }

            $stmt = $this->conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->execute([$fileName, $userId]);

            return $fileName;
        } catch (Exception $e) {
            error_log("Error updating profile photo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Top up user balance
     */
    public function topUpBalance($userId, $amount) {
        try {
            $this->conn->beginTransaction();

            // Update user balance
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET balance = balance + ? 
                WHERE id = ?
            ");
            $stmt->execute([$amount, $userId]);

            // Record balance history
            $stmt = $this->conn->prepare("
                INSERT INTO balance_history (user_id, type, amount, description) 
                VALUES (?, 'topup', ?, 'Manual top-up')
            ");
            $stmt->execute([$userId, $amount]);

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error processing top-up: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Record AFK reward
     */
    public function recordAfkReward($userId, $duration) {
        try {
            $this->conn->beginTransaction();

            // Get reward amount from settings
            $stmt = $this->conn->prepare("SELECT value FROM settings WHERE key_name = 'afk_reward_amount'");
            $stmt->execute();
            $rewardAmount = floatval($stmt->fetchColumn());

            // Record AFK reward
            $stmt = $this->conn->prepare("
                INSERT INTO afk_rewards (user_id, duration_minutes, reward_amount) 
                VALUES (?, ?, ?)
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
                INSERT INTO balance_history (user_id, type, amount, description) 
                VALUES (?, 'afk_reward', ?, 'AFK reward for {$duration} minutes')
            ");
            $stmt->execute([$userId, $rewardAmount]);

            $this->conn->commit();
            return $rewardAmount;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error processing AFK reward: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Record ad watch reward
     */
    public function recordAdWatchReward($userId, $duration) {
        try {
            $this->conn->beginTransaction();

            // Get reward amount from settings
            $stmt = $this->conn->prepare("SELECT value FROM settings WHERE key_name = 'ad_reward_amount'");
            $stmt->execute();
            $rewardAmount = floatval($stmt->fetchColumn());

            // Record ad watch
            $stmt = $this->conn->prepare("
                INSERT INTO ad_watches (user_id, ad_duration_seconds, reward_amount) 
                VALUES (?, ?, ?)
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
                INSERT INTO balance_history (user_id, type, amount, description) 
                VALUES (?, 'ad_reward', ?, 'Ad watch reward')
            ");
            $stmt->execute([$userId, $rewardAmount]);

            $this->conn->commit();
            return $rewardAmount;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error processing ad watch reward: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get user's balance history
     */
    public function getBalanceHistory($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM balance_history 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching balance history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user's password (private helper)
     */
    private function getUserPassword($userId) {
        $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}

// Initialize controller if accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user = new UserController();
    
    try {
        switch ($_POST['action']) {
            case 'update_profile':
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception("Not authenticated");
                }
                $result = $user->updateProfile($_SESSION['user_id'], $_POST);
                echo json_encode(['success' => $result]);
                break;

            case 'upload_photo':
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception("Not authenticated");
                }
                $result = $user->updateProfilePhoto($_SESSION['user_id'], $_FILES['photo']);
                echo json_encode(['success' => true, 'filename' => $result]);
                break;

            case 'topup':
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception("Not authenticated");
                }
                $amount = floatval($_POST['amount']);
                if ($amount <= 0) {
                    throw new Exception("Invalid amount");
                }
                $result = $user->topUpBalance($_SESSION['user_id'], $amount);
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
