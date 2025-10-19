<?php
/**
 * Get Announcements
 * This file returns announcements data for display on the home page
 */

include('includes/dbconnection.php');

// Function to get active announcements (optimized)
function getActiveAnnouncements($con, $limit = 10) {
    try {
        // Try the optimized query first (assumes table exists with all columns)
        $query = "SELECT * FROM announcements
                  WHERE is_active = 1
                  AND (expiry_date IS NULL OR expiry_date > NOW())
                  ORDER BY display_order ASC, created_at DESC
                  LIMIT ?";

        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $announcements = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure required fields exist with defaults
            if (!isset($row['type']) || empty($row['type'])) $row['type'] = 'info';
            if (!isset($row['icon']) || empty($row['icon'])) $row['icon'] = 'fas fa-info-circle';

            $announcements[] = $row;
        }

        return $announcements;
    } catch (Exception $e) {
        // Fallback: try basic query without optional columns
        try {
            $basicQuery = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT ?";
            $basicStmt = $con->prepare($basicQuery);
            $basicStmt->bind_param("i", $limit);
            $basicStmt->execute();
            $basicResult = $basicStmt->get_result();

            $announcements = [];
            while ($row = $basicResult->fetch_assoc()) {
                if (!isset($row['type']) || empty($row['type'])) $row['type'] = 'info';
                if (!isset($row['icon']) || empty($row['icon'])) $row['icon'] = 'fas fa-info-circle';

                $announcements[] = $row;
            }

            return $announcements;
        } catch (Exception $e2) {
            // If all else fails, return default announcements
            return getDefaultAnnouncements();
        }
    }
}

// Function to return default announcements when database table doesn't exist
function getDefaultAnnouncements() {
    return [
        [
            'id' => 1,
            'title' => 'Service Disruption Notice',
            'content' => 'Bus services will be unavailable on public holidays (December 25, January 1, and January 26). Please plan your travel accordingly.',
            'type' => 'urgent',
            'icon' => 'fas fa-exclamation-triangle',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'title' => 'New Pass Format Coming Soon',
            'content' => 'Starting July 2025, we will introduce a new digital bus pass format with enhanced security features and QR code integration.',
            'type' => 'new',
            'icon' => 'fas fa-star',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 3,
            'title' => 'PhonePe Payment Integration',
            'content' => 'We have successfully integrated PhonePe as our primary payment gateway for faster and more secure transactions.',
            'type' => 'info',
            'icon' => 'fas fa-info-circle',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 4,
            'title' => 'Application ID System Upgrade',
            'content' => 'All new applications now receive a unique Application ID in BPMS format for easier tracking and support.',
            'type' => 'success',
            'icon' => 'fas fa-check-circle',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 5,
            'title' => 'Processing Time Update',
            'content' => 'Due to high demand, bus pass applications may take 3-5 business days to process. We appreciate your patience.',
            'type' => 'warning',
            'icon' => 'fas fa-clock',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 6,
            'title' => 'Mobile-Friendly Interface',
            'content' => 'Our website is now fully optimized for mobile devices. Apply for bus passes easily from your smartphone!',
            'type' => 'info',
            'icon' => 'fas fa-mobile-alt',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}

// Function to render announcements HTML
function renderAnnouncements($announcements) {
    if (empty($announcements)) {
        return '<div class="announcement-item info">
                    <div class="announcement-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="announcement-content">
                        <h3>Welcome to Bus Pass Management System</h3>
                        <p>No announcements at this time. Check back later for important updates and news.</p>
                        <span class="announcement-date">System Message</span>
                    </div>
                </div>';
    }

    $html = '';
    foreach ($announcements as $announcement) {
        // Ensure safe values for display
        $type = isset($announcement['type']) && !empty($announcement['type']) ? $announcement['type'] : 'info';
        $icon = isset($announcement['icon']) && !empty($announcement['icon']) ? $announcement['icon'] : 'fas fa-info-circle';
        $title = isset($announcement['title']) ? $announcement['title'] : 'Announcement';
        $content = isset($announcement['content']) ? $announcement['content'] : 'No content available.';
        $created_at = isset($announcement['created_at']) ? $announcement['created_at'] : date('Y-m-d H:i:s');

        $html .= '<div class="announcement-item ' . htmlspecialchars($type) . '">
                    <div class="announcement-icon">
                        <i class="' . htmlspecialchars($icon) . '"></i>
                    </div>
                    <div class="announcement-content">
                        <h3>' . htmlspecialchars($title) . '</h3>
                        <p>' . htmlspecialchars($content) . '</p>
                        <span class="announcement-date">Posted: ' . date('M d, Y', strtotime($created_at)) . '</span>
                    </div>
                </div>';
    }

    return $html;
}

// If this file is called directly via AJAX, return JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');

    try {
        $announcements = getActiveAnnouncements($con);
        echo json_encode([
            'success' => true,
            'announcements' => $announcements,
            'html' => renderAnnouncements($announcements)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// If included in another file, just provide the functions
// The functions getActiveAnnouncements() and renderAnnouncements() are now available
?>
