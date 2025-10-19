<?php
/**
 * Get Route Information API
 * Returns route data based on source and destination
 */

header('Content-Type: application/json');
include('includes/dbconnection.php');

try {
    // Check if routes table exists
    $tableCheck = "SHOW TABLES LIKE 'routes'";
    $tableResult = $con->query($tableCheck);

    if ($tableResult->num_rows == 0) {
        echo json_encode([
            'success' => false,
            'route' => null,
            'message' => 'Routes table not found. Please run setup first.',
            'setup_url' => 'setup-routes-table.php'
        ]);
        exit();
    }

    $response = ['success' => false, 'route' => null, 'message' => ''];

    // Get parameters
    $source = isset($_GET['source']) ? trim($_GET['source']) : '';
    $destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
    $action = isset($_GET['action']) ? $_GET['action'] : 'find_route';

    switch ($action) {
        case 'get_sources':
            // Get all unique sources
            $query = "SELECT DISTINCT source FROM routes WHERE is_active = 1 ORDER BY source";
            $result = $con->query($query);
            
            $sources = [];
            while ($row = $result->fetch_assoc()) {
                $sources[] = $row['source'];
            }
            
            echo json_encode([
                'success' => true,
                'sources' => $sources
            ]);
            break;

        case 'get_destinations':
            // Get destinations based on source (or all if no source specified)
            if (!empty($source)) {
                $query = "SELECT DISTINCT destination FROM routes WHERE source = ? AND is_active = 1 ORDER BY destination";
                $stmt = $con->prepare($query);
                $stmt->bind_param("s", $source);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $query = "SELECT DISTINCT destination FROM routes WHERE is_active = 1 ORDER BY destination";
                $result = $con->query($query);
            }
            
            $destinations = [];
            while ($row = $result->fetch_assoc()) {
                $destinations[] = $row['destination'];
            }
            
            echo json_encode([
                'success' => true,
                'destinations' => $destinations,
                'source' => $source
            ]);
            break;

        case 'find_route':
        default:
            // Find route based on source and destination
            if (empty($source) || empty($destination)) {
                $response['message'] = 'Source and destination are required';
                echo json_encode($response);
                break;
            }

            // Look for exact match first
            $query = "SELECT * FROM routes WHERE source = ? AND destination = ? AND is_active = 1 LIMIT 1";
            $stmt = $con->prepare($query);
            $stmt->bind_param("ss", $source, $destination);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $route = $result->fetch_assoc();
                $response['success'] = true;
                $response['route'] = [
                    'route_id' => $route['route_id'],
                    'route_name' => $route['route_name'],
                    'source' => $route['source'],
                    'destination' => $route['destination'],
                    'distance_km' => $route['distance_km'],
                    'estimated_duration' => $route['estimated_duration']
                ];
                $response['message'] = 'Route found successfully';
            } else {
                // Try reverse route (destination to source)
                $reverseQuery = "SELECT * FROM routes WHERE source = ? AND destination = ? AND is_active = 1 LIMIT 1";
                $reverseStmt = $con->prepare($reverseQuery);
                $reverseStmt->bind_param("ss", $destination, $source);
                $reverseStmt->execute();
                $reverseResult = $reverseStmt->get_result();

                if ($reverseResult->num_rows > 0) {
                    $route = $reverseResult->fetch_assoc();
                    $response['success'] = true;
                    $response['route'] = [
                        'route_id' => $route['route_id'],
                        'route_name' => $route['route_name'] . ' (Reverse)',
                        'source' => $destination, // Swap for display
                        'destination' => $source, // Swap for display
                        'distance_km' => $route['distance_km'],
                        'estimated_duration' => $route['estimated_duration']
                    ];
                    $response['message'] = 'Reverse route found';
                } else {
                    $response['message'] = 'No matching route found for this source and destination';
                    
                    // Suggest similar routes
                    $suggestionQuery = "SELECT * FROM routes WHERE 
                                       (source LIKE ? OR destination LIKE ? OR 
                                        source LIKE ? OR destination LIKE ?) 
                                       AND is_active = 1 
                                       LIMIT 3";
                    $likeSource = "%$source%";
                    $likeDestination = "%$destination%";
                    $suggestionStmt = $con->prepare($suggestionQuery);
                    $suggestionStmt->bind_param("ssss", $likeSource, $likeSource, $likeDestination, $likeDestination);
                    $suggestionStmt->execute();
                    $suggestionResult = $suggestionStmt->get_result();
                    
                    $suggestions = [];
                    while ($suggestion = $suggestionResult->fetch_assoc()) {
                        $suggestions[] = [
                            'route_id' => $suggestion['route_id'],
                            'route_name' => $suggestion['route_name'],
                            'source' => $suggestion['source'],
                            'destination' => $suggestion['destination']
                        ];
                    }
                    
                    $response['suggestions'] = $suggestions;
                }
            }

            echo json_encode($response);
            break;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'route' => null,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$con->close();
?>
