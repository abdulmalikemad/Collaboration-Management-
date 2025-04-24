<?php
class TaskComment {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  public function add($task_id, $user_id, $role, $comment) {
    $stmt = $this->conn->prepare("
      INSERT INTO task_comments (task_id, user_id, role, comment, created_at)
      VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiss", $task_id, $user_id, $role, $comment);
    return $stmt->execute();
  }

  public function getByTask($task_id) {
    $stmt = $this->conn->prepare("
      SELECT c.*, u.name FROM task_comments c 
      JOIN users u ON c.user_id = u.id
      WHERE c.task_id = ?
      ORDER BY c.created_at ASC
    ");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    return $stmt->get_result();
  }
}

