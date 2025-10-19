<?php
/**
 * Get Bus Routes API
 * Returns bus routes data in JSON format for the dashboard
 */

header('Content-Type: application/json');
include('includes/dbconnection.php');

try {
    // Get filter parameters
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Build query based on filters
    $whereClause = "WHERE status = 'Active'";
    $params = [];
    $types = "";
    
    if ($category !== 'all') {
        $whereClause .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM bus_routes $whereClause";
    if (!empty($params)) {
        $countStmt = $con->prepare($countQuery);
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
    } else {
        $totalResult = $con->query($countQuery);
    }
    
    $totalCount = $totalResult->fetch_assoc()['total'];
    
    // Get routes data
    $query = "SELECT 
                route_number,
                category,
                source,
                destination,
                departure_time,
                arrival_time,
                duration,
                distance,
                fare,
                bus_type,
                frequency,
                status
              FROM bus_routes 
              $whereClause 
              ORDER BY 
                CASE category 
                    WHEN 'BMTC' THEN 1 
                    WHEN 'KSRTC' THEN 2 
                    WHEN 'MSRTC' THEN 3 
                    ELSE 4 
                END,
                departure_time ASC
              LIMIT ? OFFSET ?";
    
    // Add limit and offset to parameters
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $con->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $routes = [];
    while ($row = $result->fetch_assoc()) {
        // Format times for better display
        $row['departure_time_formatted'] = date('g:i A', strtotime($row['departure_time']));
        $row['arrival_time_formatted'] = date('g:i A', strtotime($row['arrival_time']));
        
        // Add route status indicators
        $currentTime = date('H:i:s');
        $row['is_current'] = ($currentTime >= $row['departure_time'] && $currentTime <= $row['arrival_time']);
        
        // Calculate approximate next departure (simplified)
        $row['next_departure'] = calculateNextDeparture($row['departure_time'], $row['frequency']);
        
        $routes[] = $row;
    }
    
    // Get categories for filter options
    $categoriesQuery = "SELECT DISTINCT category FROM bus_routes WHERE status = 'Active' ORDER BY category";
    $categoriesResult = $con->query($categoriesQuery);
    $categories = [];
    while ($cat = $categoriesResult->fetch_assoc()) {
        $categories[] = $cat['category'];
    }
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'routes' => $routes,
        'total' => $totalCount,
        'categories' => $categories,
        'current_page' => floor($offset / $limit) + 1,
        'total_pages' => ceil($totalCount / $limit),
        'has_more' => ($offset + $limit) < $totalCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'routes' => [],
        'total' => 0
    ]);
}

$con->close();

/**
 * Calculate next departure time based on frequency
 */
function calculateNextDeparture($departureTime, $frequency) {
    $currentTime = new DateTime();
    $depTime = new DateTime($departureTime);
    
    // If departure time has passed today, calculate for tomorrow
    if ($depTime < $currentTime) {
        $depTime->add(new DateInterval('P1D'));
    }
    
    // Parse frequency to determine next departure
    if (strpos($frequency, 'Every') !== false) {
        if (strpos($frequency, 'mins') !== false) {
            preg_match('/(\d+)/', $frequency, $matches);
            $minutes = isset($matches[1]) ? intval($matches[1]) : 30;
            
            // Find next departure based on frequency
            while ($depTime < $currentTime) {
                $depTime->add(new DateInterval('PT' . $minutes . 'M'));
            }
        } elseif (strpos($frequency, 'hour') !== false) {
            preg_match('/(\d+)/', $frequency, $matches);
            $hours = isset($matches[1]) ? intval($matches[1]) : 1;
            
            while ($depTime < $currentTime) {
                $depTime->add(new DateInterval('PT' . $hours . 'H'));
            }
        }
    }
    
    return $depTime->format('g:i A');
}
?>
