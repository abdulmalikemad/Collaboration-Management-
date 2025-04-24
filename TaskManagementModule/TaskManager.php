<?php
class TaskManager {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  public function getAllTasksWithDetailsForSupervisor($supervisor_id) {
    $stmt = $this->conn->prepare("
      SELECT 
        t.id AS task_id, 
        t.title, t.start_date, t.due_date, p.title AS project_title,
        u.id AS student_id, u.name AS student_name,
        f.file_path, f.upload_date,
        c.comment, c.created_at AS comment_time, c.user_id AS commenter_id,
        cu.name AS commenter_name, c.role AS commenter_role
      FROM tasks t
      JOIN projects p ON t.project_id = p.id
      JOIN task_assignments ta ON ta.task_id = t.id
      JOIN users u ON ta.student_id = u.id
      LEFT JOIN task_files f ON f.task_id = t.id AND f.student_id = u.id
      LEFT JOIN task_comments c ON c.task_id = t.id
      LEFT JOIN users cu ON cu.id = c.user_id
      WHERE p.supervisor_id = ?
      ORDER BY t.due_date ASC, f.upload_date DESC, c.created_at ASC
    ");

    $stmt->bind_param("i", $supervisor_id);
    $stmt->execute();
    return $stmt->get_result();
  }

  public function addComment($task_id, $user_id, $role, $comment) {
    $stmt = $this->conn->prepare("
      INSERT INTO task_comments (task_id, user_id, role, comment, created_at)
      VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiss", $task_id, $user_id, $role, $comment);
    return $stmt->execute();
  }
  // ✅ جلب كل المهام المرتبطة بمشرف معين
public function getTasksForSupervisor($supervisor_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT t.*, p.title AS project_title
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        WHERE p.supervisor_id = ?
        ORDER BY t.due_date ASC
      ");
      $stmt->bind_param("i", $supervisor_id);
      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("getTasksForSupervisor Error: " . $e->getMessage());
      return false;
    }
  }
  public function getTaskDetails($task_id) {
    $stmt = $this->conn->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }
  
  public function getAssignedStudents($task_id) {
    $stmt = $this->conn->prepare("
      SELECT u.name FROM task_assignments ta
      JOIN users u ON ta.student_id = u.id
      WHERE ta.task_id = ?
    ");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }
  
  public function getTaskFiles($task_id) {
    $stmt = $this->conn->prepare("
      SELECT tf.file_path, tf.upload_date, u.name AS student_name
      FROM task_files tf
      JOIN users u ON tf.student_id = u.id
      WHERE tf.task_id = ?
    ");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }
  public function getAssignedStudentsWithFiles($task_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT u.name, f.file_path, f.upload_date 
        FROM task_assignments ta 
        JOIN users u ON ta.student_id = u.id 
        LEFT JOIN task_files f ON f.task_id = ta.task_id AND f.student_id = u.id
        WHERE ta.task_id = ?
      ");
      $stmt->bind_param("i", $task_id);
      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("getAssignedStudentsWithFiles Error: " . $e->getMessage());
      return false;
    }
  }
  public function getTasksByProject($project_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT t.*, p.title AS project_title
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        WHERE t.project_id = ?
        ORDER BY t.due_date ASC
      ");
      $stmt->bind_param("i", $project_id);
      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("getTasksByProject Error: " . $e->getMessage());
      return false;
    }
  }
  public function canUploadFile($task_id, $student_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT due_date FROM tasks 
        JOIN task_assignments ON tasks.id = task_assignments.task_id
        WHERE tasks.id = ? AND task_assignments.student_id = ?
      ");
      $stmt->bind_param("ii", $task_id, $student_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
  
      if (!$row) return false;
  
      $due_date = new DateTime($row['due_date']);
      $now = new DateTime();
  
      return $now <= $due_date; // ✅ يقدر يرفع فقط إذا التاريخ لم ينتهِ
    } catch (Exception $e) {
      error_log("Upload Check Error: " . $e->getMessage());
      return false;
    }
  }
  public function autoCloseOverdueTasks() {
    $today = date('Y-m-d');
  
    $stmt = $this->conn->prepare("
      SELECT t.id 
      FROM tasks t
      WHERE t.due_date < ? AND t.status != 'مغلقة'
    ");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
  
    while ($task = $result->fetch_assoc()) {
      $task_id = $task['id'];
  
      // تحقق إذا جميع الطلاب ما رفعوش ملفات
      $check = $this->conn->prepare("
        SELECT COUNT(*) AS uploads
        FROM task_files
        WHERE task_id = ?
      ");
      $check->bind_param("i", $task_id);
      $check->execute();
      $uploads = $check->get_result()->fetch_assoc()['uploads'];
  
      if ((int)$uploads === 0) {
        // قفل المهمة
        $update = $this->conn->prepare("UPDATE tasks SET status = 'مغلقة' WHERE id = ?");
        $update->bind_param("i", $task_id);
        $update->execute();
      }
    }
  }
  
}
?>
