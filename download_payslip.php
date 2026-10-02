<?php

session_start();

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    header("Location: auth/login.php");
    exit;
}

require_once "config/database.php";

if (
    !isset($_GET["id"])
    ||
    !is_numeric($_GET["id"])
) {
    die("Invalid payslip request.");
}

$payslipId = (int) $_GET["id"];


$stmt = $pdo->prepare("
    SELECT
        ps.payslip_id,
        ps.payslip_file,
        p.employee_id

    FROM payslips ps

    INNER JOIN payroll p
        ON ps.payroll_id = p.payroll_id

    WHERE ps.payslip_id = :payslip_id

    LIMIT 1
");

$stmt->execute([
    ":payslip_id" => $payslipId
]);

$payslip = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$payslip) {
    die("Payslip not found.");
}


/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

if ($_SESSION["role"] === "Employee") {

    if (
        empty($_SESSION["employee_id"])
        ||
        (int) $_SESSION["employee_id"]
        !== (int) $payslip["employee_id"]
    ) {
        http_response_code(403);
        die("Access denied.");
    }

} elseif ($_SESSION["role"] !== "Admin") {

    http_response_code(403);
    die("Access denied.");
}


/*
|--------------------------------------------------------------------------
| Validate stored path
|--------------------------------------------------------------------------
*/

$filePath = __DIR__ . "/" . $payslip["payslip_file"];

$realFilePath = realpath($filePath);

$allowedDirectory = realpath(
    __DIR__ . "/generated/payslips"
);


if (
    $realFilePath === false
    ||
    $allowedDirectory === false
    ||
    strpos(
        $realFilePath,
        $allowedDirectory . DIRECTORY_SEPARATOR
    ) !== 0
    ||
    !is_file($realFilePath)
) {
    die("Payslip file not found.");
}


/*
|--------------------------------------------------------------------------
| Stream PDF
|--------------------------------------------------------------------------
*/

header("Content-Type: application/pdf");

header(
    'Content-Disposition: inline; filename="' .
    basename($realFilePath) .
    '"'
);

header(
    "Content-Length: " .
    filesize($realFilePath)
);

readfile($realFilePath);

exit;