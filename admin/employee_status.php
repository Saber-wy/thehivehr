<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../includes/csrf.php";


/*
|--------------------------------------------------------------------------
| Only allow POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: employees.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Verify CSRF token
|--------------------------------------------------------------------------
*/

if (
    !verifyCsrfToken(
        $_POST["csrf_token"] ?? ""
    )
) {
    die("Invalid security token.");
}


/*
|--------------------------------------------------------------------------
| Validate Employee ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST["employee_id"])
    ||
    !is_numeric($_POST["employee_id"])
) {
    header("Location: employees.php");
    exit;
}

$employeeId = (int) $_POST["employee_id"];


/*
|--------------------------------------------------------------------------
| Find current employee status
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT employment_status
    FROM employees
    WHERE employee_id = :employee_id
    LIMIT 1
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

$employee = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$employee) {
    header("Location: employees.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Toggle Employee Status
|--------------------------------------------------------------------------
*/

$newStatus =
    $employee["employment_status"] === "Active"
        ? "Inactive"
        : "Active";


$stmt = $pdo->prepare("
    UPDATE employees
    SET employment_status = :status
    WHERE employee_id = :employee_id
");

$stmt->execute([
    ":status" => $newStatus,
    ":employee_id" => $employeeId
]);


/*
|--------------------------------------------------------------------------
| Return to Employee Management
|--------------------------------------------------------------------------
*/

header("Location: employees.php?status_changed=1");
exit;