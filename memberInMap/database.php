<?php
// database.php
$host = 'localhost';
$dbname = 'meeting_system';
$username = 'root';
$password = '';
$port = 3307;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
	$pdo->exec( "set names utf8" );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function getGeoFences($pdo) {
    $stmt = $pdo->query("SELECT * FROM geo_fences WHERE is_deleted = 0");
    $fences = $stmt->fetchAll();
    
    // تبدیل داده‌های JSON به آرایه
    foreach ($fences as &$fence) {
        $fence['coordinates'] = json_decode($fence['coordinates'], true);
    }
    
    return $fences;
}
?>