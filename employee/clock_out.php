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


// Retrieve today's open attendance record
$stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = :employee_id
    AND attendance_date = :attendance_date
    AND clock_in IS NOT NULL
    AND clock_out IS NULL
    LIMIT 1
");

$stmt->execute([
    ":employee_id" => $employeeId,
    ":attendance_date" => $today
]);

$attendance = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$attendance) {

    header("Location: attendance.php");
    exit;
}


$clockOut = date("Y-m-d H:i:s");

$clockInTime = new DateTime(
    $attendance["clock_in"]
);

$clockOutTime = new DateTime(
    $clockOut
);

$secondsWorked =
    $clockOutTime->getTimestamp()
    -
    $clockInTime->getTimestamp();


if ($secondsWorked < 0) {
    $secondsWorked = 0;
}


$totalHours =
    round(
        $secondsWorked / 3600,
        2
    );


// Update attendance record
$stmt = $pdo->prepare("
    UPDATE attendance
    SET
        clock_out = :clock_out,
        total_hours = :total_hours,
        status = 'Present'
    WHERE attendance_id = :attendance_id
");

$stmt->execute([
    ":clock_out" => $clockOut,
    ":total_hours" => $totalHours,
    ":attendance_id" => $attendance["attendance_id"]
]);


header("Location: attendance.php");
exit;