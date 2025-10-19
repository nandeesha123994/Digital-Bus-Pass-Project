<?php
// Function to process rewards when a pass is approved
function processRewards($con, $userId, $passTypeId, $applicationId) {
    try {
        $con->begin_transaction();
        
        // Get pass type details
        $passTypeQuery = "SELECT type_name FROM bus_pass_types WHERE id = ?";
        $stmt = $con->prepare($passTypeQuery);
        $stmt->bind_param("i", $passTypeId);
        $stmt->execute();
        $passTypeResult = $stmt->get_result();
        $passType = $passTypeResult->fetch_assoc();
        
        if (!$passType) {
            throw new Exception("Pass type not found");
        }
        
        // Get reward points for this pass type
        $rulesQuery = "SELECT points_awarded FROM rewards_rules WHERE pass_type = ? AND is_active = 1";
        $stmt = $con->prepare($rulesQuery);
        $stmt->bind_param("s", $passType['type_name']);
        $stmt->execute();
        $rulesResult = $stmt->get_result();
        $rule = $rulesResult->fetch_assoc();
        
        if (!$rule) {
            throw new Exception("No reward rule found for this pass type");
        }
        
        $points = $rule['points_awarded'];
        
        // Update user's reward points and pass count
        $updateUser = "UPDATE users 
                      SET reward_points = reward_points + ?, 
                          pass_count = pass_count + 1 
                      WHERE id = ?";
        $stmt = $con->prepare($updateUser);
        $stmt->bind_param("ii", $points, $userId);
        $stmt->execute();
        
        // Record the transaction
        $insertTransaction = "INSERT INTO rewards_transactions 
                            (user_id, pass_type, points_earned, application_id, description) 
                            VALUES (?, ?, ?, ?, ?)";
        $description = "Points earned for " . $passType['type_name'];
        $stmt = $con->prepare($insertTransaction);
        $stmt->bind_param("isiss", $userId, $passType['type_name'], $points, $applicationId, $description);
        $stmt->execute();
        
        $con->commit();
        return true;
        
    } catch (Exception $e) {
        $con->rollback();
        error_log("Error processing rewards: " . $e->getMessage());
        return false;
    }
}

// Function to get user's rewards information
function getUserRewards($con, $userId) {
    try {
        // Get user's total points and pass count
        $userQuery = "SELECT reward_points, pass_count FROM users WHERE id = ?";
        $stmt = $con->prepare($userQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userResult = $stmt->get_result();
        $userData = $userResult->fetch_assoc();
        
        // Get recent transactions
        $transactionsQuery = "SELECT * FROM rewards_transactions 
                            WHERE user_id = ? 
                            ORDER BY transaction_date DESC 
                            LIMIT 5";
        $stmt = $con->prepare($transactionsQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $transactionsResult = $stmt->get_result();
        $transactions = [];
        while ($row = $transactionsResult->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        // Determine badge level
        $badge = "Bronze";
        if ($userData['reward_points'] >= 1000) {
            $badge = "Diamond";
        } elseif ($userData['reward_points'] >= 500) {
            $badge = "Platinum";
        } elseif ($userData['reward_points'] >= 250) {
            $badge = "Gold";
        } elseif ($userData['reward_points'] >= 100) {
            $badge = "Silver";
        }
        
        return [
            'points' => $userData['reward_points'],
            'pass_count' => $userData['pass_count'],
            'badge' => $badge,
            'recent_transactions' => $transactions
        ];
        
    } catch (Exception $e) {
        error_log("Error getting user rewards: " . $e->getMessage());
        return false;
    }
}

// Function to get top users by reward points
function getTopUsers($con, $limit = 10) {
    try {
        $query = "SELECT id, full_name, reward_points, pass_count 
                 FROM users 
                 WHERE reward_points > 0 
                 ORDER BY reward_points DESC 
                 LIMIT ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            // Determine badge level
            $badge = "Bronze";
            if ($row['reward_points'] >= 1000) {
                $badge = "Diamond";
            } elseif ($row['reward_points'] >= 500) {
                $badge = "Platinum";
            } elseif ($row['reward_points'] >= 250) {
                $badge = "Gold";
            } elseif ($row['reward_points'] >= 100) {
                $badge = "Silver";
            }
            
            $row['badge'] = $badge;
            $users[] = $row;
        }
        
        return $users;
        
    } catch (Exception $e) {
        error_log("Error getting top users: " . $e->getMessage());
        return false;
    }
}
?> 