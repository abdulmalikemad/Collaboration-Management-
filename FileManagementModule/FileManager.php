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
  //  3. عرض الملفات لطالب لمهمة معينة
public function getFilesForStudent($taskId, $studentId) {
    $stmt = $this->conn->prepare("SELECT * FROM task_files WHERE task_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $taskId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $files = [];
    while ($row = $result->fetch_assoc()) {
      $files[] = $row;
    }
    return $files;
  }
  
   //  4. عرض كل ملفات مشروع 
public function getFilesByTask($taskId) {
    $stmt = $this->conn->prepare("
      SELECT tf.*, u.name AS student_name 
      FROM task_files tf 
      JOIN users u ON tf.student_id = u.id 
      WHERE tf.task_id = ? 
      ORDER BY tf.upload_date DESC
    ");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
  
    $files = [];
    while ($row = $result->fetch_assoc()) {
      $files[] = $row;
    }
    return $files;
  }
}

