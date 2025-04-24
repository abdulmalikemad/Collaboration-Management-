<?php
class Task {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // ✅ إنشاء مهمة بشرط طالبين فقط
  public function createTask($title, $start_date, $due_date, $project_id, $assigned_students) {
    if (count($assigned_students) !== 2) return false;

    try {
      $this->conn->begin_transaction();

      $stmt = $this->conn->prepare("INSERT INTO tasks (title, start_date, due_date, project_id, status) VALUES (?, ?, ?, ?, 'جديدة')");
      $stmt->bind_param("sssi", $title, $start_date, $due_date, $project_id);
      if (!$stmt->execute()) throw new Exception("فشل في إدخال المهمة.");

      $task_id = $this->conn->insert_id;

      $assignStmt = $this->conn->prepare("INSERT INTO task_assignments (task_id, student_id) VALUES (?, ?)");
      foreach ($assigned_students as $student_id) {
        $assignStmt->bind_param("ii", $task_id, $student_id);
        if (!$assignStmt->execute()) throw new Exception("فشل في ربط الطالب بالمهمة.");
      }

      $this->conn->commit();
      return true;
    } catch (Exception $e) {
      $this->conn->rollback();
      error_log("Task Creation Error: " . $e->getMessage());
      return false;
    }
  }

  // ✅ جلب كل المهام الخاصة بمشروع
  public function getTasksByProject($project_id) {
    $stmt = $this->conn->prepare("
      SELECT t.*, GROUP_CONCAT(u.name SEPARATOR ', ') AS assigned_to, p.title AS project_title
      FROM tasks t
      LEFT JOIN task_assignments ta ON t.id = ta.task_id
      LEFT JOIN users u ON ta.student_id = u.id
      JOIN projects p ON t.project_id = p.id
      WHERE t.project_id = ?
      GROUP BY t.id
      ORDER BY t.due_date ASC
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    return $stmt->get_result();
  }

  // ✅ جلب المهام المكلف بها طالب
  public function getTasksForStudent($student_id) {
    $stmt = $this->conn->prepare("
      SELECT t.*, p.title AS project_title, GROUP_CONCAT(u.name SEPARATOR ', ') AS assigned_to
      FROM tasks t
      JOIN task_assignments ta ON t.id = ta.task_id
      JOIN projects p ON t.project_id = p.id
      LEFT JOIN task_assignments ta2 ON t.id = ta2.task_id
      LEFT JOIN users u ON ta2.student_id = u.id
      WHERE ta.student_id = ?
      GROUP BY t.id
      ORDER BY t.due_date ASC
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result();
  }

  // ✅ التحقق من أن الطالب يستطيع الدخول لصفحة مهمة (ولو لم يكن مكلف لكن ضمن الفريق)
  public function getTaskDetailsForStudent($task_id, $student_id) {
    $stmt = $this->conn->prepare("
      SELECT t.*
      FROM tasks t
      JOIN projects p ON t.project_id = p.id
      WHERE t.id = ? AND (
        EXISTS (
          SELECT 1 FROM project_members pm WHERE pm.project_id = p.id AND pm.student_id = ?
        ) OR p.leader_id = ?
      )
    ");
    $stmt->bind_param("iii", $task_id, $student_id, $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  // ✅ رفع ملف للمهمة
  public function uploadFile($task_id, $student_id, $file_path) {
    $stmt = $this->conn->prepare("
      INSERT INTO task_files (task_id, student_id, file_path, upload_date)
      VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iis", $task_id, $student_id, $file_path);
    return $stmt->execute();
  }

  // ✅ حذف ملف مرفوع
  public function deleteFile($file_id, $student_id) {
    $check = $this->conn->prepare("SELECT file_path FROM task_files WHERE id = ? AND student_id = ?");
    $check->bind_param("ii", $file_id, $student_id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    if ($res && file_exists($res['file_path'])) unlink($res['file_path']);

    $stmt = $this->conn->prepare("DELETE FROM task_files WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $file_id, $student_id);
    return $stmt->execute();
  }

  // ✅ جلب الملفات الخاصة بالطالب
  public function getFilesForStudent($task_id, $student_id) {
    $stmt = $this->conn->prepare("SELECT * FROM task_files WHERE task_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $task_id, $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  // ✅ إغلاق المهام تلقائيًا إن لم يتم رفع ملف قبل تاريخ الانتهاء
  public function autoCloseOverdueTasks() {
    $stmt = $this->conn->prepare("
      SELECT t.id
      FROM tasks t
      LEFT JOIN task_files f ON t.id = f.task_id
      WHERE t.due_date < NOW() AND t.status = 'جديدة'
      GROUP BY t.id
      HAVING COUNT(f.id) = 0
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
      $update = $this->conn->prepare("UPDATE tasks SET status = 'مغلقة' WHERE id = ?");
      $update->bind_param("i", $row['id']);
      $update->execute();
    }
  }

  // ✅ جلب الطلاب المكلفين بمهمة
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

  // ✅ جلب الملفات لجميع الطلبة
  public function getTaskFiles($task_id) {
    $stmt = $this->conn->prepare("
      SELECT f.*, u.name AS student_name
      FROM task_files f
      JOIN users u ON f.student_id = u.id
      WHERE f.task_id = ?
      ORDER BY f.upload_date ASC
    ");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  // ✅ جلب المهام حسب المشرف
  public function getTasksForSupervisor($supervisor_id) {
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
  }
}
?>
