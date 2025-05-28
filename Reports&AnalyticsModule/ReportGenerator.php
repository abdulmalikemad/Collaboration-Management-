<?php
class ReportGenerator {
    private static $instance = null; // النسخة الوحيدة
    private $conn;

    // المُنشئ خاص لمنع الإنشاء المباشر من برا الكلاس
    private function __construct($conn) {
        $this->conn = $conn;
    }

    // دالة ثابتة ترجع نفس النسخة الوحيدة من الكلاس
    public static function getInstance($conn) {
        if (self::$instance === null) {
            self::$instance = new ReportGenerator($conn);
        }
        return self::$instance;
    }

    // 🔹 توليد تقرير تقدم المشروع
    public function generateProjectProgressReport($projectId) {
        try {
            $totalTasks = $this->countTasks($projectId);
            $completedTasks = $this->countTasksByStatus($projectId, 'مكتملة');
            $fileCount = $this->countFiles($projectId);
            $progressRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;

            return [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'progress_rate' => $progressRate . '%',
                'files_uploaded' => $fileCount
            ];
        } catch (Exception $e) {
            return [
                'error' => 'حدث خطأ أثناء توليد التقرير: ' . $e->getMessage()
            ];
        }
    }

    // 🔹 دالة لحساب إجمالي المهام في المشروع
    private function countTasks($projectId) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            return $count;
        } catch (Exception $e) {
            return 0;
        }
    }

    // 🔹 دالة لحساب عدد المهام حسب الحالة
    private function countTasksByStatus($projectId, $status) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ? AND status = ?");
            $stmt->bind_param("is", $projectId, $status);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            return $count;
        } catch (Exception $e) {
            return 0;
        }
    }

    // 🔹 دالة لحساب عدد الملفات
    private function countFiles($projectId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM task_files
                WHERE task_id IN (
                    SELECT id FROM tasks WHERE project_id = ?
                )
            ");
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            return $count;
        } catch (Exception $e) {
            return 0;
        }
    }
}
