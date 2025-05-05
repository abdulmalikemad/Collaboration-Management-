<?php
class FileManager {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  //  1. رفع ملف جديد (uploadTaskFile)
  public function uploadTaskFile($taskId, $studentId, $filePath) {
    $stmt = $this->conn->prepare("INSERT INTO task_files (task_id, student_id, file_path, upload_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $taskId, $studentId, $filePath);
    return $stmt->execute();
  }

  //  2. استبدال ملف موجود (replaceTaskFile)
  public function replaceTaskFile($taskId, $studentId, $newFilePath) {
    // حذف الملف القديم
    $deleteStmt = $this->conn->prepare("DELETE FROM task_files WHERE task_id = ? AND student_id = ?");
    $deleteStmt->bind_param("ii", $taskId, $studentId);
    $deleteStmt->execute();

    // رفع الملف الجديد
    return $this->uploadTaskFile($taskId, $studentId, $newFilePath);
  }
  
}

