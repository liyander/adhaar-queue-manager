<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "sammy"; 
$password = "password"; 
$dbname = "net_cafe_locator";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
    $radiusKm = 10; 

    if ($lat && $lon) {
        $stmt = $pdo->prepare("SELECT id, name, latitude, longitude, available_tokens,
            (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:lon)) + sin(radians(:lat)) * sin(radians(latitude)))) AS distance
            FROM cafes HAVING distance <= :radius ORDER BY distance");
        $stmt->execute(['lat' => $lat, 'lon' => $lon, 'radius' => $radiusKm]);
    } else {
        $stmt = $pdo->query("SELECT id, name, latitude, longitude, available_tokens FROM cafes");
    }

    $cafes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cafes);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['name'], $input['latitude'], $input['longitude'], $input['available_tokens'])) {
        $name = $input['name'];
        $latitude = $input['latitude'];
        $longitude = $input['longitude'];
        $available_tokens = $input['available_tokens'];

        if ($name && $latitude && $longitude !== null && $available_tokens !== null) {
            try {
                $stmt = $pdo->prepare("INSERT INTO cafes (name, latitude, longitude, available_tokens) 
                                       VALUES (:name, :latitude, :longitude, :available_tokens)");
                $stmt->execute([
                    'name' => $name,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'available_tokens' => $available_tokens
                ]);
                echo json_encode(['status' => 'success', 'message' => 'Cafe added successfully!']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to add cafe: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        }
    }

    elseif (isset($input['cafeId'], $input['action']) && $input['action'] === 'book_token') {
        $cafeId = $input['cafeId'];

        if ($cafeId !== null) {
            try {
                $stmt = $pdo->prepare("SELECT available_tokens FROM cafes WHERE id = :cafeId");
                $stmt->execute(['cafeId' => $cafeId]);
                $cafe = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($cafe && $cafe['available_tokens'] > 0) {
                    $stmt = $pdo->prepare("UPDATE cafes SET available_tokens = available_tokens - 1 WHERE id = :cafeId");
                    $stmt->execute(['cafeId' => $cafeId]);

                    echo json_encode(['status' => 'success', 'message' => 'Token booked successfully!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'No tokens available for this cafe.']);
                }
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to book token: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cafe ID is missing']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields or invalid action']);
    }
}
?>
