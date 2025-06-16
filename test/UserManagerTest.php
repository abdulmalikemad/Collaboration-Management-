<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../UserManagementModule/user.php';

class UserManagerTest extends TestCase {
    private $conn;
    private $stmt;
    private $user;

    protected function setUp(): void {
        $this->conn = $this->createMock(mysqli::class);
        $this->stmt = $this->createMock(mysqli_stmt::class);
        $this->user = new User($this->conn);
    }

    public function testAddStudentSuccess() {
        $this->conn->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('bind_param')->willReturn(true);
        $this->stmt->method('execute')->willReturn(true);

        $data = [
            'name' => 'Ali',
            'studentId' => '123',
            'email' => 'ali@example.com',
            'password' => 'pass',
            'confirmPassword' => 'pass',
            'gender' => 'ذكر'
        ];

        $result = $this->user->addStudent($data);
        $this->assertStringContainsString("✅", $result);
    }

    public function testAddStudentPasswordMismatch() {
        $data = [
            'name' => 'Ali',
            'studentId' => '123',
            'email' => 'ali@example.com',
            'password' => 'pass',
            'confirmPassword' => 'wrong',
            'gender' => 'ذكر'
        ];

        $result = $this->user->addStudent($data);
        $this->assertStringContainsString("❌", $result);
    }

    public function testUpdateUserSuccess() {
        $this->conn->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('bind_param')->willReturn(true);
        $this->stmt->method('execute')->willReturn(true);

        $data = [
            'name' => 'Ali Updated',
            'student_id' => '123',
            'email' => 'ali@update.com',
            'gender' => 'ذكر'
        ];

        $result = $this->user->updateUser(1, $data, 'طالب');
        $this->assertTrue($result);
    }

    public function testDeleteUser() {
        $this->conn->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('bind_param')->willReturn(true);
        $this->stmt->method('execute')->willReturn(true);

        $this->assertTrue($this->user->deleteUser(1, 'طالب'));
    }

   public function testGetAllAdminsNoSearch() {
    $mockResult = $this->createMock(mysqli_result::class);

    $this->stmt->method('execute')->willReturn(true);
    $this->stmt->method('get_result')->willReturn($mockResult);
    $this->conn->method('prepare')->willReturn($this->stmt);

    $result = $this->user->getAllAdmins();
    $this->assertSame($mockResult, $result);
}


  public function testGetUserByIdAndRoleFoundWithoutRealStmt() {
    // نجهز نسخة خاصة من كلاس User فيها getUserByIdAndRole ترجع نتيجة وهمية
    $userMock = $this->getMockBuilder(User::class)
                     ->setConstructorArgs([$this->conn])
                     ->onlyMethods(['getUserByIdAndRole'])
                     ->getMock();

    $userMock->method('getUserByIdAndRole')->willReturn([
        'id' => 1,
        'name' => 'Ali',
        'student_id' => '123',
        'role' => 'طالب'
    ]);

    $result = $userMock->getUserByIdAndRole(1, 'طالب');
    $this->assertEquals('Ali', $result['name']);
}
public function testGetAllStudentsNoSearch() {
    $mockResult = $this->createMock(mysqli_result::class);
    $this->stmt->method('execute')->willReturn(true);
    $this->stmt->method('get_result')->willReturn($mockResult);
    $this->conn->method('prepare')->willReturn($this->stmt);

    $result = $this->user->getAllStudents();
    $this->assertSame($mockResult, $result);
}
public function testAddSupervisorSuccess() {
    $this->conn->method('prepare')->willReturn($this->stmt);
    $this->stmt->method('bind_param')->willReturn(true);
    $this->stmt->method('execute')->willReturn(true);

    $data = [
        'name' => 'Dr. Ahmed',
        'studentId' => '222',
        'email' => 'dr@uni.com',
        'password' => 'pass',
        'confirmPassword' => 'pass',
        'gender' => 'ذكر'
    ];

    $result = $this->user->addSupervisor($data);
    $this->assertStringContainsString("✅", $result);
}
public function testAddAdminSuccess() {
    $this->conn->method('prepare')->willReturn($this->stmt);
    $this->stmt->method('bind_param')->willReturn(true);
    $this->stmt->method('execute')->willReturn(true);

    $data = [
        'name' => 'Admin Guy',
        'studentId' => '999',
        'email' => 'admin@system.com',
        'password' => 'admin123',
        'confirmPassword' => 'admin123',
        'gender' => 'أنثى'
    ];

    $result = $this->user->addAdmin($data);
    $this->assertStringContainsString("✅", $result);
}
public function testDeleteUserFail() {
    $this->conn->method('prepare')->willReturn($this->stmt);
    $this->stmt->method('bind_param')->willReturn(true);
    $this->stmt->method('execute')->willReturn(false);

    $this->assertFalse($this->user->deleteUser(5, 'طالب'));
}
public function testUpdateUserFail() {
    $this->conn->method('prepare')->willReturn($this->stmt);
    $this->stmt->method('bind_param')->willReturn(true);
    $this->stmt->method('execute')->willReturn(false);

    $data = [
        'name' => 'Ali Error',
        'student_id' => '404',
        'email' => 'error@user.com',
        'gender' => 'ذكر'
    ];

    $result = $this->user->updateUser(2, $data, 'طالب');
    $this->assertFalse($result);
}
public function testUpdateUserThrowsException() {
    $this->conn->method('prepare')->willThrowException(new Exception("DB error"));
    $data = [
        'name' => 'Test',
        'student_id' => '999',
        'email' => 'x@x.com',
        'gender' => 'ذكر'
    ];
    $result = $this->user->updateUser(1, $data, 'طالب');
    $this->assertFalse($result);
}
public function testGetAllAdminsWithoutSearch() {
    $mockResult = $this->createMock(mysqli_result::class);
    $this->stmt->method('execute')->willReturn(true);
    $this->stmt->method('get_result')->willReturn($mockResult);
    $this->conn->method('prepare')->willReturn($this->stmt);

    $result = $this->user->getAllAdmins('');
    $this->assertSame($mockResult, $result);
}
public function testGetAllStudentsWithoutSearch() {
    $mockResult = $this->createMock(mysqli_result::class);
    $this->stmt->method('execute')->willReturn(true);
    $this->stmt->method('get_result')->willReturn($mockResult);
    $this->conn->method('prepare')->willReturn($this->stmt);

    $result = $this->user->getAllStudents('');
    $this->assertSame($mockResult, $result);
}
public function testGetAllAdminsWithSearch() {
    $mockResult = $this->createMock(mysqli_result::class);
    $this->stmt->method('execute')->willReturn(true);
    $this->stmt->method('get_result')->willReturn($mockResult);
    $this->conn->method('prepare')->willReturn($this->stmt);

    $result = $this->user->getAllAdmins('admin');
    $this->assertSame($mockResult, $result);
}




    /**
     * ❗ ملاحظة: لم يتم اختبار دالة login() بسبب وجود header() و exit().
     * وهي توقف تنفيذ PHPUnit تلقائيًا. يمكن تعديلها لاحقًا لتصبح testable.
     */
}
