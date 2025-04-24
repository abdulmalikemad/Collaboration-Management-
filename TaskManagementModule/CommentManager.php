<?php
class CommentManager {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // ✅ إضافة تعليق على مهمة
  public function addComment($task_id, $user_id, $role, $comment) {
    try {
      $stmt = $this->conn->prepare("
        INSERT INTO task_comments (task_id, user_id, role, comment, created_at)
        VALUES (?, ?, ?, ?, NOW())
      ");
      $stmt->bind_param("iiss", $task_id, $user_id, $role, $comment);
      return $stmt->execute();
    } catch (Exception $e) {
      error_log("AddComment Error: " . $e->getMessage());
      return false;
    }
  }

  // ✅ جلب التعليقات لمهمة معينة
  public function getCommentsByTask($task_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT c.comment, c.created_at, u.name AS commenter_name, c.role
        FROM task_comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.task_id = ?
        ORDER BY c.created_at ASC
      ");
      $stmt->bind_param("i", $task_id);
      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("GetComments Error: " . $e->getMessage());
      return false;
    }
  }

  // ✅ جلب كل التعليقات المرتبطة بمشرف لجميع المهامه
  public function getAllCommentsBySupervisor($supervisor_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT 
          c.task_id, c.comment, c.created_at, u.name AS commenter_name, c.role, 
          t.title AS task_title, p.title AS project_title
        FROM task_comments c
        JOIN tasks t ON c.task_id = t.id
        JOIN projects p ON t.project_id = p.id
        JOIN users u ON u.id = c.user_id
        WHERE p.supervisor_id = ?
        ORDER BY c.created_at DESC
      ");
      $stmt->bind_param("i", $supervisor_id);
      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("GetAllSupervisorComments Error: " . $e->getMessage());
      return false;
    }
  }
}
?>
