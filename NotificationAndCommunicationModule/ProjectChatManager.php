<?php
class ProjectChatManager {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // إرسال رسالة في دردشة المشروع
  public function sendProjectMessage($projectId, $senderId, $senderRole, $message) {
    $sql = "INSERT INTO project_messages (project_id, sender_id, sender_role, message, created_at)
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("iiss", $projectId, $senderId, $senderRole, $message);
    return $stmt->execute();
  }

  // جلب الرسائل المخصصة لمشروع
  public function getProjectMessages($projectId) {
    $sql = "SELECT pm.*, u.name AS sender_name
            FROM project_messages pm
            JOIN users u ON pm.sender_id = u.id
            WHERE pm.project_id = ?
            ORDER BY pm.created_at ASC";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    return $stmt->get_result();
  }
}
