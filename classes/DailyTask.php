<?php
/**
 * SOPHEA - Daily Task Management Class
 * 
 * Handles all daily task operations (CRUD)
 */

require_once __DIR__ . '/Database.php';

class DailyTask {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new daily task
     */
    public function createTask($data) {
        try {
            $sql = "INSERT INTO daily_tasks 
                    (task_name, task_description, task_type, related_client_id, related_service_id,
                     due_date, due_time, priority, created_by) 
                    VALUES 
                    (:task_name, :task_description, :task_type, :related_client_id, :related_service_id,
                     :due_date, :due_time, :priority, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':task_name' => $data['task_name'],
                ':task_description' => $data['task_description'] ?? null,
                ':task_type' => $data['task_type'] ?? 'follow_up',
                ':related_client_id' => $data['related_client_id'] ?? null,
                ':related_service_id' => $data['related_service_id'] ?? null,
                ':due_date' => $data['due_date'],
                ':due_time' => $data['due_time'] ?? null,
                ':priority' => $data['priority'] ?? 'normal',
                ':created_by' => $data['created_by'] ?? null
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error creating daily task: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a daily task
     */
    public function updateTask($id, $data) {
        try {
            $sql = "UPDATE daily_tasks SET 
                    task_name = :task_name,
                    task_description = :task_description,
                    task_type = :task_type,
                    related_client_id = :related_client_id,
                    related_service_id = :related_service_id,
                    due_date = :due_date,
                    due_time = :due_time,
                    priority = :priority
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':id' => $id,
                ':task_name' => $data['task_name'],
                ':task_description' => $data['task_description'] ?? null,
                ':task_type' => $data['task_type'] ?? 'follow_up',
                ':related_client_id' => $data['related_client_id'] ?? null,
                ':related_service_id' => $data['related_service_id'] ?? null,
                ':due_date' => $data['due_date'],
                ':due_time' => $data['due_time'] ?? null,
                ':priority' => $data['priority'] ?? 'normal'
            ]);
            
        } catch (PDOException $e) {
            error_log("Error updating daily task: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark task as completed
     */
    public function markAsCompleted($id) {
        try {
            $sql = "UPDATE daily_tasks SET 
                    is_completed = 1,
                    completed_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
            
        } catch (PDOException $e) {
            error_log("Error marking task as completed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark task as incomplete
     */
    public function markAsIncomplete($id) {
        try {
            $sql = "UPDATE daily_tasks SET 
                    is_completed = 0,
                    completed_at = NULL
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
            
        } catch (PDOException $e) {
            error_log("Error marking task as incomplete: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get task by ID
     */
    public function getTaskById($id) {
        try {
            $sql = "SELECT t.*, c.company_name, s.service_name 
                    FROM daily_tasks t
                    LEFT JOIN clients c ON t.related_client_id = c.id
                    LEFT JOIN services s ON t.related_service_id = s.id
                    WHERE t.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching task: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all tasks with optional filters
     */
    public function getAllTasks($filters = []) {
        try {
            $sql = "SELECT t.*, c.company_name, s.service_name 
                    FROM daily_tasks t
                    LEFT JOIN clients c ON t.related_client_id = c.id
                    LEFT JOIN services s ON t.related_service_id = s.id
                    WHERE 1=1";
            $params = [];
            
            // Completed filter
            if (isset($filters['is_completed'])) {
                $sql .= " AND t.is_completed = :is_completed";
                $params[':is_completed'] = $filters['is_completed'] ? 1 : 0;
            }
            
            // Priority filter
            if (isset($filters['priority']) && !empty($filters['priority'])) {
                $sql .= " AND t.priority = :priority";
                $params[':priority'] = $filters['priority'];
            }
            
            // Date filter
            if (isset($filters['due_date'])) {
                $sql .= " AND t.due_date = :due_date";
                $params[':due_date'] = $filters['due_date'];
            }
            
            // Date range filter
            if (isset($filters['date_from'])) {
                $sql .= " AND t.due_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to'])) {
                $sql .= " AND t.due_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            // Client filter
            if (isset($filters['client_id'])) {
                $sql .= " AND t.related_client_id = :client_id";
                $params[':client_id'] = $filters['client_id'];
            }
            
            // Order by
            $orderBy = $filters['order_by'] ?? 't.due_date, t.due_time';
            $orderDir = $filters['order_dir'] ?? 'ASC';
            $sql .= " ORDER BY {$orderBy} {$orderDir}";
            
            // Limit and offset
            if (isset($filters['limit'])) {
                $sql .= " LIMIT :limit";
                $params[':limit'] = (int)$filters['limit'];
                
                if (isset($filters['offset'])) {
                    $sql .= " OFFSET :offset";
                    $params[':offset'] = (int)$filters['offset'];
                }
            }
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching tasks: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get tasks for today
     */
    public function getTodayTasks() {
        return $this->getAllTasks(['due_date' => date('Y-m-d')]);
    }
    
    /**
     * Get upcoming tasks (next 7 days)
     */
    public function getUpcomingTasks($days = 7) {
        return $this->getAllTasks([
            'date_from' => date('Y-m-d'),
            'date_to' => date('Y-m-d', strtotime("+{$days} days")),
            'is_completed' => false
        ]);
    }
    
    /**
     * Get urgent tasks
     */
    public function getUrgentTasks() {
        return $this->getAllTasks([
            'priority' => 'urgent',
            'is_completed' => false
        ]);
    }
    
    /**
     * Delete a task
     */
    public function deleteTask($id) {
        try {
            $sql = "DELETE FROM daily_tasks WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting task: " . $e->getMessage());
            return false;
        }
    }
}

