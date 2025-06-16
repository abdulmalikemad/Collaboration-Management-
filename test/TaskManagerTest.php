<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../TaskManagementModule/TaskManager.php';

// Fake mysqli class يسمح بتعديل insert_id بسهولة
class FakeMysqli {
    public $insert_id = 99;

    public function begin_transaction() {}
    public function commit() {}
    public function rollback() {}

    public function prepare($query) {
        return new class {
            public function bind_param() { return true; }
            public function execute() { return true; }
            public function get_result() { return null; }
        };
    }
}

class TaskManagerTest extends TestCase {
    private $conn;
    private $taskManager;

    protected function setUp(): void {
        $this->conn = new FakeMysqli();
        $this->taskManager = new TaskManager($this->conn);
    }

    public function testCreateTaskReturnsTaskIdWhenTwoStudentsAssigned() {
        $insertStmt = $this->getMockBuilder(stdClass::class)->addMethods(['bind_param','execute'])->getMock();
        $assignStmt = $this->getMockBuilder(stdClass::class)->addMethods(['bind_param','execute'])->getMock();

        $insertStmt->method('bind_param')->willReturn(true);
        $insertStmt->method('execute')->willReturn(true);

        $assignStmt->method('bind_param')->willReturn(true);
        $assignStmt->method('execute')->willReturn(true);

        $this->conn = $this->getMockBuilder(FakeMysqli::class)->onlyMethods(['prepare'])->getMock();
        $this->conn->insert_id = 123;

        $this->conn->method('prepare')->willReturnCallback(function($sql) use ($insertStmt, $assignStmt) {
            if (strpos($sql, 'INSERT INTO tasks') !== false) return $insertStmt;
            if (strpos($sql, 'INSERT INTO task_assignments') !== false) return $assignStmt;
            return null;
        });

        $taskManager = new TaskManager($this->conn);

        $result = $taskManager->createTask('Test Task', '2025-06-01', '2025-06-10', 1, [101, 102]);
        $this->assertEquals(123, $result);
    }

    public function testCreateTaskFailsIfNotTwoStudents() {
        $result = $this->taskManager->createTask('Test Task', '2025-06-01', '2025-06-10', 1, [101]);
        $this->assertFalse($result);
    }

    public function testIsStudentAssignedReturnsTrue() {
        $stmt = $this->getMockBuilder(stdClass::class)->addMethods(['bind_param','execute','get_result'])->getMock();
        $resultMock = $this->getMockBuilder(stdClass::class)->addMethods(['num_rows'])->getMock();
        $resultMock->num_rows = 1;

        $stmt->method('bind_param')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('get_result')->willReturn($resultMock);

        $this->conn = $this->getMockBuilder(FakeMysqli::class)->onlyMethods(['prepare'])->getMock();
        $this->conn->method('prepare')->willReturn($stmt);

        $taskManager = new TaskManager($this->conn);
        $this->assertTrue($taskManager->isStudentAssigned(1, 101));
    }

    // أضف اختبارات أخرى بنفس النمط حسب الحاجة...
}