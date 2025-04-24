<?php
class TaskFile {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  public function upload($task_id, $student_id, $file_path) {
    $stmt = $this->conn->prepare("
      INSERT INTO task_files (task_id, student_id, file_path, upload_date) 
      VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iis", $task_id, $student_id, $file_path);
    return $stmt->execute();
  }

  public function get($task_id, $student_id) {
    $stmt = $this->conn->prepare("SELECT file_path, upload_date FROM task_files WHERE task_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $task_id, $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }
}
