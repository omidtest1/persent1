<?php
require_once __DIR__ . '/../includes/db.php';

class GeoFence {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function createFence($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO geo_fences 
                (meeting_id, name, coordinates, radius, fence_type, created_at) 
                VALUES 
                (:meeting_id, :name, :coordinates, :radius, :fence_type, NOW(3))
            ");
            
            $stmt->bindParam(':meeting_id', $data['meeting_id'], PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':coordinates', $data['coordinates'], PDO::PARAM_STR);
            $stmt->bindParam(':radius', $data['radius']);
            $stmt->bindParam(':fence_type', $data['fence_type'], PDO::PARAM_STR);
            
            return $stmt->execute() ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("GeoFence create error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getFencesByMeeting($meeting_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, coordinates, radius, fence_type 
                FROM geo_fences 
                WHERE meeting_id = :meeting_id AND is_deleted = 0
            ");
            $stmt->bindParam(':meeting_id', $meeting_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("GeoFence get error: " . $e->getMessage());
            return [];
        }
    }
    
    public function deleteFence($fenceId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE geo_fences 
                SET is_deleted = 1, updated_at = NOW(3)
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $fenceId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("GeoFence delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateFence($data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE geo_fences 
                SET 
                    meeting_id = :meeting_id,
                    name = :name,
                    coordinates = :coordinates,
                    radius = :radius,
                    fence_type = :fence_type,
                    updated_at = NOW(3)
                WHERE id = :id
            ");
            
            $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
            $stmt->bindParam(':meeting_id', $data['meeting_id'], PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':coordinates', $data['coordinates'], PDO::PARAM_STR);
            $stmt->bindParam(':radius', $data['radius']);
            $stmt->bindParam(':fence_type', $data['fence_type'], PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("GeoFence update error: " . $e->getMessage());
            return false;
        }
    }
}
?>