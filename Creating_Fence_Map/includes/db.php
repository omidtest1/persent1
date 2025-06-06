<?php
/**
 * اتصال به پایگاه داده و توابع مشترک
 * Database connection and common functions
 */

//require_once 'config.php';
 $adf=  __DIR__ . '/config.php';
if(file_exists($adf)) { require_once $adf; }else{echo "file NOT exists = ".$adf;die();exit();}
 
class Database {
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $port = 3307;
    private $dbname = 'meeting_system';
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
			
			$this->conn->exec( "set names utf8" );
			
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}

// تابعی برای دریافت لیست جلسات فعال
function getActiveMeetings() {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT id, title, start_time 
        FROM meetings 
        WHERE status = 'active' AND is_deleted = 0
        ORDER BY start_time DESC
    ");
    $stmt->execute();
    
    return $stmt->fetchAll();
}
?>
