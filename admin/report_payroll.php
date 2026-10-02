<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


$payrollMonth =
    trim($_GET["payroll_month"] ?? "");


$sql = "
    SELECT
        p.payroll_month,
        p.regular_hours,
        p.overtime_hours,
        p.basic_pay,
        p.overtime_pay,
        p.deductions,
        p.net_pay,

        e.employee_code,
        e.first_name,
        e.last_name

    FROM payroll p

    INNER JOIN employees e
        ON p.employee_id = e.employee_id

    WHERE 1 = 1
";

$params = [];


if ($payrollMonth !== "") {

    $monthStart =
        $payrollMonth . "-01";

    $sql .= "
        AND p.payroll_month = :payroll_month
    ";

    $params[":payroll_month"] =
        $monthStart;
}


$sql .= "
    ORDER BY
        p.payroll_month DESC,
        e.employee_code ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$records =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Report totals
|--------------------------------------------------------------------------
*/

$totalBasicPay = 0;
$totalOvertimePay = 0;
$totalDeductions = 0;
$totalNetPay = 0;


foreach ($records as $record) {

    $totalBasicPay +=
        (float) $record["basic_pay"];

    $totalOvertimePay +=
        (float) $record["overtime_pay"];

    $totalDeductions +=
        (float) $record["deductions"];

    $totalNetPay +=
        (float) $record["net_pay"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Payroll Report - The Hive HR
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>


<header class="topbar">

    <h1>The Hive HR</h1>

    <div class="no-print">

        <a href="reports.php">
            Back to Reports
        </a>

    </div>

</header>


<main class="content">

    <div class="page-title">

        <h2>Payroll Report</h2>

        <p>
            Payroll expenditure and employee payment information.
        </p>

    </div>


    <div class="form-container no-print">

        <form method="GET">

            <div class="form-group">

                <label>
                    Payroll Month
                </label>

                <input
                    type="month"
                    name="payroll_month"
                    value="<?php
                    echo htmlspecialchars(
                        $payrollMonth
                    );
                    ?>"
                >

            </div>


            <button
                type="submit"
                class="btn"
            >
                Generate Report
            </button>


            <a
                href="report_payroll.php"
                class="btn btn-secondary"
            >
                Clear
            </a>


            <button
                type="button"
                class="btn"
                onclick="window.print()"
            >
                Print Report
            </button>

        </form>

    </div>


    <br>


    <div class="cards">

        <div class="card">

            <h3>Total Basic Pay</h3>

            <p>
                RM
                <?php
                echo number_format(
                    $totalBasicPay,
                    2
                );
                ?>
            </p>

        </div>


        <div class="card">

            <h3>Total Overtime Pay</h3>

            <p>
                RM
                <?php
                echo number_format(
                    $totalOvertimePay,
                    2
                );
                ?>
            </p>

        </div>


        <div class="card">

            <h3>Total Deductions</h3>

            <p>
                RM
                <?php
                echo number_format(
                    $totalDeductions,
                    2
                );
                ?>
            </p>

        </div>


        <div class="card">

            <h3>Total Net Payroll</h3>

            <p>
                RM
                <?php
                echo number_format(
                    $totalNetPay,
                    2
                );
                ?>
            </p>

        </div>

    </div>


    <br>


    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Employee</th>
                    <th>Month</th>
                    <th>Regular Hours</th>
                    <th>OT Hours</th>
                    <th>Basic Pay</th>
                    <th>OT Pay</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>

                </tr>

            </thead>


            <tbody>

            <?php if (count($records) > 0): ?>

                <?php foreach ($records as $record): ?>

                    <tr>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $record["employee_code"]
                                . " - "
                                . $record["first_name"]
                                . " "
                                . $record["last_name"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                date(
                                    "F Y",
                                    strtotime(
                                        $record["payroll_month"]
                                    )
                                )
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo number_format(
                                (float) $record["regular_hours"],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo number_format(
                                (float) $record["overtime_hours"],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            RM
                            <?php
                            echo number_format(
                                (float) $record["basic_pay"],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            RM
                            <?php
                            echo number_format(
                                (float) $record["overtime_pay"],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            RM
                            <?php
                            echo number_format(
                                (float) $record["deductions"],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <strong>
                                RM
                                <?php
                                echo number_format(
                                    (float) $record["net_pay"],
                                    2
                                );
                                ?>
                            </strong>
                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td colspan="8">
                        No payroll records found.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>


</body>

</html>