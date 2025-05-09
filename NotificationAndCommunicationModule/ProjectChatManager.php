<?php
class ProjectChatManager {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // إرسال رسالة في دردشة المشروع
  public function sendProjectMessage($projectId, $senderId, $senderRole, $message) {
    try {
      $sql = "INSERT INTO project_messages (project_id, sender_id, sender_role, message, created_at)
              VALUES (?, ?, ?, ?, NOW())";
      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
        throw new Exception("فشل في تجهيز الاستعلام: " . $this->conn->error);
      }
      $stmt->bind_param("iiss", $projectId, $senderId, $senderRole, $message);
      if (!$stmt->execute()) {
        throw new Exception("فشل في تنفيذ الاستعلام: " . $stmt->error);
      }
      return true;
    } catch (Exception $e) {
      error_log("خطأ في sendProjectMessage: " . $e->getMessage());
      return false;
    }
  }

  // جلب الرسائل المخصصة لمشروع
  public function getProjectMessages($projectId) {
    try {
      $sql = "SELECT pm.*, u.name AS sender_name
              FROM project_messages pm
              JOIN users u ON pm.sender_id = u.id
              WHERE pm.project_id = ?
              ORDER BY pm.created_at ASC";
      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
        throw new Exception("فشل في تجهيز الاستعلام: " . $this->conn->error);
      }
      $stmt->bind_param("i", $projectId);
      if (!$stmt->execute()) {
        throw new Exception("فشل في تنفيذ الاستعلام: " . $stmt->error);
      }
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("خطأ في getProjectMessages: " . $e->getMessage());
      return false;
    }
  }
}
