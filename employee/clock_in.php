<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Employee") {
    header("Location: ../auth/login.php");
    exit;
}

if (empty($_SESSION["employee_id"])) {
    die("Employee account is not linked to an employee record.");
}

require_once "../config/database.php";
require_once "../includes/csrf.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: attendance.php");
    exit;
}

if (
    !verifyCsrfToken(
        $_POST["csrf_token"] ?? ""
    )
) {
    die("Invalid security token.");
}

$employeeId = (int) $_SESSION["employee_id"];

$today = date("Y-m-d");
$clockIn = date("Y-m-d H:i:s");


// Check whether an attendance record already exists today
$stmt = $pdo->prepare("
    SELECT attendance_id
    FROM attendance
    WHERE employee_id = :employee_id
    AND attendance_date = :attendance_date
    LIMIT 1
");

$stmt->execute([
    ":employee_id" => $employeeId,
    ":attendance_date" => $today
]);

$existingAttendance = $stmt->fetch(PDO::FETCH_ASSOC);


if ($existingAttendance) {

    header("Location: attendance.php");
    exit;
}


// Create attendance record
$stmt = $pdo->prepare("
    INSERT INTO attendance
    (
        employee_id,
        attendance_date,
        clock_in,
        total_hours,
        status
    )
    VALUES
    (
        :employee_id,
        :attendance_date,
        :clock_in,
        0.00,
        'Incomplete'
    )
");

$stmt->execute([
    ":employee_id" => $employeeId,
    ":attendance_date" => $today,
    ":clock_in" => $clockIn
]);


header("Location: attendance.php");
exit;

