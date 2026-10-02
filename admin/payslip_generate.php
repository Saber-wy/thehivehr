<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../includes/csrf.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;


/*
|--------------------------------------------------------------------------
| Only allow POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: payroll.php");
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
| Validate Payroll ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST["payroll_id"])
    ||
    !is_numeric($_POST["payroll_id"])
) {
    header("Location: payroll.php");
    exit;
}

$payrollId = (int) $_POST["payroll_id"];

/*
|--------------------------------------------------------------------------
| Retrieve payroll and employee information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.payroll_id,
        p.employee_id,
        p.payroll_month,
        p.regular_hours,
        p.overtime_hours,
        p.basic_pay,
        p.overtime_pay,
        p.deductions,
        p.net_pay,
        p.created_at,

        e.employee_code,
        e.first_name,
        e.last_name,
        e.email,
        e.position,
        e.hourly_rate

    FROM payroll p

    INNER JOIN employees e
        ON p.employee_id = e.employee_id

    WHERE p.payroll_id = :payroll_id

    LIMIT 1
");

$stmt->execute([
    ":payroll_id" => $payrollId
]);

$payroll = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$payroll) {
    die("Payroll record not found.");
}


/*
|--------------------------------------------------------------------------
| Check whether payslip already exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        payslip_id,
        payslip_file
    FROM payslips
    WHERE payroll_id = :payroll_id
    LIMIT 1
");

$stmt->execute([
    ":payroll_id" => $payrollId
]);

$existingPayslip = $stmt->fetch(PDO::FETCH_ASSOC);


if ($existingPayslip) {

    header("Location: payslips.php?exists=1");
    exit;
}


/*
|--------------------------------------------------------------------------
| Payslip information
|--------------------------------------------------------------------------
*/

$employeeName =
    $payroll["first_name"]
    . " "
    . $payroll["last_name"];

$payrollMonth =
    date(
        "F Y",
        strtotime($payroll["payroll_month"])
    );

$grossPay =
    (float) $payroll["basic_pay"]
    +
    (float) $payroll["overtime_pay"];

$fileName =
    "payslip_"
    . $payroll["employee_code"]
    . "_"
    . date(
        "Y_m",
        strtotime($payroll["payroll_month"])
    )
    . ".pdf";

$relativeFilePath =
    "generated/payslips/" . $fileName;

$absoluteFilePath =
    __DIR__
    . "/../generated/payslips/"
    . $fileName;


/*
|--------------------------------------------------------------------------
| Payslip HTML
|--------------------------------------------------------------------------
*/

$html = '
<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        color: #222;
        padding: 20px;
    }

    .header {
        text-align: center;
        margin-bottom: 30px;
    }

    .header h1 {
        margin-bottom: 5px;
        font-size: 24px;
    }

    .header p {
        margin: 0;
    }

    .section {
        margin-bottom: 25px;
    }

    .employee-table,
    .pay-table {
        width: 100%;
        border-collapse: collapse;
    }

    .employee-table td {
        padding: 6px;
    }

    .pay-table th,
    .pay-table td {
        padding: 10px;
        border: 1px solid #cccccc;
    }

    .pay-table th {
        background: #eeeeee;
        text-align: left;
    }

    .amount {
        text-align: right;
    }

    .net-pay {
        font-weight: bold;
        font-size: 14px;
    }

    .footer {
        margin-top: 40px;
        font-size: 10px;
        text-align: center;
        color: #555555;
    }

</style>

</head>

<body>


<div class="header">

    <h1>The Hive HR</h1>

    <p>Employee Payslip</p>

    <p>'
    . htmlspecialchars($payrollMonth)
    . '</p>

</div>


<div class="section">

    <table class="employee-table">

        <tr>

            <td>
                <strong>Employee Code:</strong>
            </td>

            <td>'
            . htmlspecialchars($payroll["employee_code"])
            . '</td>

            <td>
                <strong>Employee Name:</strong>
            </td>

            <td>'
            . htmlspecialchars($employeeName)
            . '</td>

        </tr>


        <tr>

            <td>
                <strong>Position:</strong>
            </td>

            <td>'
            . htmlspecialchars($payroll["position"] ?? "-")
            . '</td>

            <td>
                <strong>Email:</strong>
            </td>

            <td>'
            . htmlspecialchars($payroll["email"])
            . '</td>

        </tr>


        <tr>

            <td>
                <strong>Hourly Rate:</strong>
            </td>

            <td>
                RM '
                . number_format(
                    (float) $payroll["hourly_rate"],
                    2
                )
                . '
            </td>

            <td>
                <strong>Payroll Month:</strong>
            </td>

            <td>'
            . htmlspecialchars($payrollMonth)
            . '</td>

        </tr>

    </table>

</div>


<div class="section">

    <table class="pay-table">

        <thead>

            <tr>

                <th>Description</th>
                <th>Hours</th>
                <th class="amount">Amount (RM)</th>

            </tr>

        </thead>


        <tbody>

            <tr>

                <td>Regular Pay</td>

                <td>'
                . number_format(
                    (float) $payroll["regular_hours"],
                    2
                )
                . '</td>

                <td class="amount">'
                . number_format(
                    (float) $payroll["basic_pay"],
                    2
                )
                . '</td>

            </tr>


            <tr>

                <td>Overtime Pay</td>

                <td>'
                . number_format(
                    (float) $payroll["overtime_hours"],
                    2
                )
                . '</td>

                <td class="amount">'
                . number_format(
                    (float) $payroll["overtime_pay"],
                    2
                )
                . '</td>

            </tr>


            <tr>

                <td colspan="2">
                    <strong>Gross Pay</strong>
                </td>

                <td class="amount">
                    <strong>'
                    . number_format($grossPay, 2)
                    . '</strong>
                </td>

            </tr>


            <tr>

                <td colspan="2">
                    Deductions
                </td>

                <td class="amount">
                    - '
                    . number_format(
                        (float) $payroll["deductions"],
                        2
                    )
                    . '
                </td>

            </tr>


            <tr class="net-pay">

                <td colspan="2">
                    Net Pay
                </td>

                <td class="amount">
                    RM '
                    . number_format(
                        (float) $payroll["net_pay"],
                        2
                    )
                    . '
                </td>

            </tr>

        </tbody>

    </table>

</div>


<div class="footer">

    <p>
        This payslip was generated electronically
        by The Hive HR.
    </p>

</div>


</body>

</html>
';


/*
|--------------------------------------------------------------------------
| Configure Dompdf
|--------------------------------------------------------------------------
*/

$options = new Options();

$options->set(
    "isRemoteEnabled",
    false
);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper(
    "A4",
    "portrait"
);

$dompdf->render();


/*
|--------------------------------------------------------------------------
| Store PDF
|--------------------------------------------------------------------------
*/

$pdfOutput = $dompdf->output();

$result = file_put_contents(
    $absoluteFilePath,
    $pdfOutput
);


if ($result === false) {
    die("Unable to save payslip PDF.");
}


/*
|--------------------------------------------------------------------------
| Save payslip database record
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        INSERT INTO payslips
        (
            payroll_id,
            payslip_file
        )
        VALUES
        (
            :payroll_id,
            :payslip_file
        )
    ");

    $stmt->execute([
        ":payroll_id" => $payrollId,
        ":payslip_file" => $relativeFilePath
    ]);


    header(
        "Location: payslips.php?generated=1"
    );

    exit;


} catch (PDOException $e) {

    /*
     * The file was generated but database insertion failed.
     * Remove the orphaned PDF.
     */

    if (file_exists($absoluteFilePath)) {
        unlink($absoluteFilePath);
    }

    if ($e->getCode() === "23000") {

        header(
            "Location: payslips.php?exists=1"
        );

        exit;
    }

    die("Unable to save payslip record.");
}