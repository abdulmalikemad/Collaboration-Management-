<?php
require_once __DIR__ . '/../NotificationAndCommunicationModule/NotificationManager.php';

class TaskManager {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // إنشاء مهمة جديدة وتعيين طالبين فقط
  public function createTask($title, $start_date, $due_date, $project_id, $assigned_students) {
    if (count($assigned_students) !== 2) return false;

    try {
      $this->conn->begin_transaction();

      $stmt = $this->conn->prepare("
        INSERT INTO tasks (title, start_date, due_date, project_id, status)
        VALUES (?, ?, ?, ?, 'جديدة')
      ");
      $stmt->bind_param("sssi", $title, $start_date, $due_date, $project_id);
      if (!$stmt->execute()) throw new Exception("فشل في إدخال المهمة.");

      $task_id = $this->conn->insert_id;
      $assignStmt = $this->conn->prepare("INSERT INTO task_assignments (task_id, student_id) VALUES (?, ?)");
      if (!$assignStmt) throw new Exception("فشل في تجهيز استعلام ربط الطالب.");

      $notifier = new NotificationManager($this->conn);

      foreach ($assigned_students as $student_id) {
        $assignStmt->bind_param("ii", $task_id, $student_id);
        if (!$assignStmt->execute()) throw new Exception("فشل في ربط الطالب بالمهمة.");
        $notifier->sendTaskAssignmentNotification($student_id, $task_id, $title);
      }

      $this->conn->commit();
      return $task_id;

    } catch (Exception $e) {
      $this->conn->rollback();
      error_log("Task Creation Error: " . $e->getMessage());
      return false;
    }
  }

  // التحقق هل الطالب معين في هذه المهمة
  public function isStudentAssigned($task_id, $student_id) {
    $stmt = $this->conn->prepare("SELECT id FROM task_assignments WHERE task_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $task_id, $student_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
  }

  // المهام التي يشرف عليها الدكتور مع تفاصيل
  public function getAllTasksWithDetailsForSupervisor($supervisor_id) {
    $stmt = $this->conn->prepare("
      SELECT 
        t.id AS task_id, t.title, t.start_date, t.due_date, p.title AS project_title,
        u.id AS student_id, u.name AS student_name,
        f.file_path, f.upload_date,
        c.comment, c.created_at AS comment_time, c.user_id AS commenter_id,
        cu.name AS commenter_name, c.role AS commenter_role
      FROM tasks t
      JOIN projects p ON t.project_id = p.id
      JOIN task_assignments ta ON ta.task_id = t.id
      JOIN users u ON ta.student_id = u.id
      LEFT JOIN task_files f ON f.task_id = t.id AND f.student_id = u.id
      LEFT JOIN task_comments c ON c.task_id = t.id
      LEFT JOIN users cu ON cu.id = c.user_id
      WHERE p.supervisor_id = ?
      ORDER BY t.due_date ASC, f.upload_date DESC, c.created_at ASC
    ");
    $stmt->bind_param("i", $supervisor_id);
    $stmt->execute();
    return $stmt->get_result();
  }

  // إضافة تعليق
  public function addComment($task_id, $user_id, $role, $comment) {
    $stmt = $this->conn->prepare("
      INSERT INTO task_comments (task_id, user_id, role, comment, created_at)
      VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiss", $task_id, $user_id, $role, $comment);
    return $stmt->execute();
  }

  // المهام حسب المشرف (مع إمكانية الفلترة بالحالة)
  public function getTasksForSupervisor($supervisor_id, $status = 'all') {
    try {
      $query = "
        SELECT t.*, p.title AS project_title
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        WHERE p.supervisor_id = ?
      ";
      if ($status !== 'all') {
        $query .= " AND t.status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $supervisor_id, $status);
      } else {
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $supervisor_id);
      }

      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("getTasksForSupervisor Error: " . $e->getMessage());
      return false;
    }
  }

  // جلب المهام حسب مشروع (مع الفلترة بالحالة)
  public function getTasksByProject($project_id, $status = 'all') {
    try {
      $query = "
        SELECT t.*, p.title AS project_title
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        WHERE t.project_id = ?
      ";
      if ($status !== 'all') {
        $query .= " AND t.status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $project_id, $status);
      } else {
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $project_id);
      }

      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("getTasksByProject Error: " . $e->getMessage());
      return false;
    }
  }

  // تفاصيل مهمة محددة
  public function getTaskDetails($task_id) {
    $stmt = $this->conn->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  // الطلاب المكلفين بمهمة
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

  // الملفات الخاصة بمهمة معينة
  public function getTaskFiles($task_id) {
    $stmt = $this->conn->prepare("
      SELECT tf.file_path, tf.upload_date, u.name AS student_name
      FROM task_files tf
      JOIN users u ON tf.student_id = u.id
      WHERE tf.task_id = ?
    ");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  // الطلاب وملفاتهم بنفس الاستعلام
  public function getAssignedStudentsWithFiles($task_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT u.name, f.file_path, f.upload_date 
        FROM task_assignments ta 
        JOIN users u ON ta.student_id = u.id 
        LEFT JOIN task_files f ON f.task_id = ta.task_id AND f.student_id = u.id
        WHERE ta.task_id = ?
      ");
      $stmt->bind_param("i", $task_id);
      $stmt->execute();
      return $stmt->get_result();
    } catch (Exception $e) {
      error_log("getAssignedStudentsWithFiles Error: " . $e->getMessage());
      return false;
    }
  }

  // هل يمكن رفع ملف؟ (قبل موعد التسليم فقط)
  public function canUploadFile($task_id, $student_id) {
    try {
      $stmt = $this->conn->prepare("
        SELECT due_date FROM tasks 
        JOIN task_assignments ON tasks.id = task_assignments.task_id
        WHERE tasks.id = ? AND task_assignments.student_id = ?
      ");
      $stmt->bind_param("ii", $task_id, $student_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();

      if (!$row) return false;

      $due_date = new DateTime($row['due_date']);
      $now = new DateTime();

      return $now <= $due_date;
    } catch (Exception $e) {
      error_log("Upload Check Error: " . $e->getMessage());
      return false;
    }
  }

  // إغلاق المهام المتأخرة تلقائيًا بدون أي رفع
  public function autoCloseOverdueTasks() {
    $today = date('Y-m-d');

    $stmt = $this->conn->prepare("
      SELECT t.id 
      FROM tasks t
      WHERE t.due_date < ? AND t.status != 'مغلقة'
    ");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($task = $result->fetch_assoc()) {
      $task_id = $task['id'];

      $check = $this->conn->prepare("
        SELECT COUNT(*) AS uploads
        FROM task_files
        WHERE task_id = ?
      ");
      $check->bind_param("i", $task_id);
      $check->execute();
      $uploads = $check->get_result()->fetch_assoc()['uploads'];

      if ((int)$uploads === 0) {
        $update = $this->conn->prepare("UPDATE tasks SET status = 'مغلقة' WHERE id = ?");
        $update->bind_param("i", $task_id);
        $update->execute();
      }
    }
  }

  // تفاصيل مهمة لطالب (يتحقق من أنه مشارك أو قائد المشروع)
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

  // جلب كل المهام المرتبطة بطالب (عضو أو قائد)
  public function getTasksForStudent($student_id) {
    $stmt = $this->conn->prepare("
      SELECT t.*, p.title AS project_title
      FROM tasks t
      JOIN projects p ON t.project_id = p.id
      WHERE p.id IN (
        SELECT project_id FROM project_members WHERE student_id = ?
        UNION
        SELECT id FROM projects WHERE leader_id = ?
      )
      ORDER BY t.due_date ASC
    ");
    $stmt->bind_param("ii", $student_id, $student_id);
    $stmt->execute();
    return $stmt->get_result();
  }
  // تحديث حالة المهمة إلى "جارية" إذا تم رفع أي ملف لها وهي لا تزال "جديدة"
public function updateTaskStatusToInProgressIfNeeded($task_id) {
  try {
    // تحقق من حالة المهمة الحالية
    $stmt = $this->conn->prepare("SELECT status FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();

    if (!$task || $task['status'] !== 'جديدة') return; // لا حاجة للتحديث

    // تحقق هل هناك أي ملفات مرفوعة
    $check = $this->conn->prepare("SELECT COUNT(*) AS file_count FROM task_files WHERE task_id = ?");
    $check->bind_param("i", $task_id);
    $check->execute();
    $files = $check->get_result()->fetch_assoc();

    if ($files && $files['file_count'] > 0) {
      // حدّث الحالة إلى "جارية"
      $update = $this->conn->prepare("UPDATE tasks SET status = 'جارية' WHERE id = ?");
      $update->bind_param("i", $task_id);
      $update->execute();
    }
  } catch (Exception $e) {
    error_log("updateTaskStatusToInProgressIfNeeded Error: " . $e->getMessage());
  }
}
// ✅ تحديث حالة المهمة إلى "مكتملة" إذا رفع كل الطلاب المكلفين ملفاتهم
public function updateTaskStatusToCompletedIfNeeded($task_id) {
  try {
    // 1. احصل على كل الطلاب المكلفين
    $stmt = $this->conn->prepare("SELECT student_id FROM task_assignments WHERE task_id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $assigned = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (count($assigned) === 0) return;

    foreach ($assigned as $row) {
      $student_id = $row['student_id'];

      // 2. تحقق إن كل طالب رفع ملف واحد على الأقل
      $check = $this->conn->prepare("SELECT COUNT(*) AS total FROM task_files WHERE task_id = ? AND student_id = ?");
      $check->bind_param("ii", $task_id, $student_id);
      $check->execute();
      $count = $check->get_result()->fetch_assoc();

      if ((int)$count['total'] === 0) {
        // طالب لم يرفع → لا تحدث الحالة
        return;
      }
    }

    // 3. كل الطلاب رفعوا → حدّث الحالة إلى مكتملة
    $update = $this->conn->prepare("UPDATE tasks SET status = 'مكتملة' WHERE id = ?");
    $update->bind_param("i", $task_id);
    $update->execute();

  } catch (Exception $e) {
    error_log("updateTaskStatusToCompletedIfNeeded Error: " . $e->getMessage());
  }
}

}
?>
