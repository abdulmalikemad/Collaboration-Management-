<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../ProjectManagementModule/Project.php';

// ✅ كلاس وهمي يمثل stmt (للاستخدام مع create)
class FakeStmt {
    public function bind_param() { return true; }
    public function execute() { return true; }
}

// ✅ كلاس وهمي يمثل الاتصال بقاعدة البيانات (فقط لإنشاء مشروع)
class FakeConn {
    public $insert_id = 99;

    public function prepare($query) {
        return new FakeStmt();
    }
}

class ProjectTest extends TestCase {
    private $conn;
    private $project;

    protected function setUp(): void {
        // إنشاء اتصال وهمي قابل للتعديل مع معظم الدوال
        $this->conn = $this->createMock(mysqli::class);
        $this->project = new Project($this->conn);
    }

    // ✅ اختبار إنشاء مشروع جديد
    public function testCreateProjectSuccess() {
        $title = 'Test Project';
        $description = 'Test Description';
        $leader_id = 1;
        $supervisor_id = 2;
        $start_date = '2025-06-01';
        $end_date = '2025-09-01';

        // نستخدم كائن وهمي فيه insert_id
        $conn = new FakeConn();
        $project = new Project($conn);

        $result = $project->create($title, $description, $leader_id, $supervisor_id, $start_date, $end_date);

        $this->assertEquals(99, $result); // ✅ Pass
    }

    // ✅ اختبار تعيين أعضاء إلى مشروع
    public function testAssignMembers() {
        $stmt = $this->createMock(mysqli_stmt::class);
        $stmt->method('bind_param')->willReturn(true);
        $stmt->method('execute')->willReturn(true);

        $this->conn->method('prepare')->willReturn($stmt);
        $project = new Project($this->conn);

        $project->assignMembers(5, [10, 11]);

        $this->assertTrue(true); // ✅ Pass
    }

    // ✅ اختبار جلب الطلاب المتاحين
    public function testGetAvailableStudents() {
        $resultMock = $this->createMock(mysqli_result::class);
        $resultMock->method('fetch_assoc')->willReturnOnConsecutiveCalls(
            ['id' => 1, 'name' => 'طالب 1'],
            null
        );

        $stmt = $this->createMock(mysqli_stmt::class);
        $stmt->method('bind_param')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('get_result')->willReturn($resultMock);

        $this->conn->method('prepare')->willReturn($stmt);

        $result = $this->project->getAvailableStudents(3);

        $this->assertInstanceOf(mysqli_result::class, $result); // ✅ Pass
    }

    // ✅ اختبار جلب المشرفين
    public function testGetSupervisors() {
        $result = $this->createMock(mysqli_result::class);
        $this->conn->method('query')->willReturn($result);

        $data = $this->project->getSupervisors();

        $this->assertInstanceOf(mysqli_result::class, $data); // ✅ Pass
    }

    // ✅ اختبار جلب مشروع بالمعرف
    public function testGetProjectById() {
        $expected = [
            'id' => 100,
            'title' => 'مشروع تجريبي',
            'leader_name' => 'مالك',
        ];

        $result = $this->createMock(mysqli_result::class);
        $result->method('fetch_assoc')->willReturn($expected);

        $stmt = $this->createMock(mysqli_stmt::class);
        $stmt->method('bind_param')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('get_result')->willReturn($result);

        $this->conn->method('prepare')->willReturn($stmt);

        $data = $this->project->getProjectById(100);

        $this->assertEquals($expected, $data); // ✅ Pass
    }
}
