<?php
class TaskAssignment {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  public function assign($task_id, $students) {
    $stmt = $this->conn->prepare("INSERT INTO task_assignments (task_id, student_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $task_id, $student_id);
    foreach ($students as $student_id) {
      if (!$stmt->execute()) return false;
    }
    return true;
  }

  public function isAssigned($task_id, $student_id) {
    $stmt = $this->conn->prepare("SELECT id FROM task_assignments WHERE task_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $task_id, $student_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
  }
}
