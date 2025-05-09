<?php
class NotificationManager {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // إرسال إشعار عند تعيين مهمة جديدة للطالب
  public function sendTaskAssignmentNotification($studentId, $taskId, $taskTitle) {
    $msg = "📌 تم تعيينك في المهمة: $taskTitle";
    $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'task_assignment', ?, ?)");
    $stmt->bind_param("iis", $studentId, $taskId, $msg);
    return $stmt->execute();
  }

  // إرسال إشعار تذكيري بالموعد النهائي للمهمة
  public function sendDeadlineReminder($studentId, $taskId, $taskTitle, $dueDate) {
    $msg = "⏰ تذكير: غدًا هو آخر موعد لتسليم المهمة: $taskTitle (الموعد: $dueDate)";
    $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'deadline_reminder', ?, ?)");
    $stmt->bind_param("iis", $studentId, $taskId, $msg);
    return $stmt->execute();
  }

  // جلب الإشعارات لمستخدم معين
  public function getNotificationsForUser($userId) {
    $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
  }
}
?>
