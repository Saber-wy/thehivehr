<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Summary statistics
|--------------------------------------------------------------------------
*/

$totalEmployees =
    $pdo->query("
        SELECT COUNT(*)
        FROM employees
    ")->fetchColumn();


$activeEmployees =
    $pdo->query("
        SELECT COUNT(*)
        FROM employees
        WHERE employment_status = 'Active'
    ")->fetchColumn();


$totalAttendance =
    $pdo->query("
        SELECT COUNT(*)
        FROM attendance
    ")->fetchColumn();


$totalPayroll =
    $pdo->query("
        SELECT COUNT(*)
        FROM payroll
    ")->fetchColumn();


$totalPerformance =
    $pdo->query("
        SELECT COUNT(*)
        FROM performance
    ")->fetchColumn();


$totalPayrollAmount =
    $pdo->query("
        SELECT COALESCE(SUM(net_pay), 0)
        FROM payroll
    ")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Reports - The Hive HR
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>


<header class="topbar">

    <h1>The Hive HR</h1>

    <div>

        Welcome,
        <?php echo htmlspecialchars($_SESSION["username"]); ?>

        |

        <a href="../auth/logout.php">
            Logout
        </a>

    </div>

</header>


<div class="layout">

   <?php
require "../includes/admin_sidebar.php";
?>


    <main class="content">

    <div class="page-title">

        <h2>
            Reports
        </h2>

        <p>
            Review system summaries and generate administrative reports.
        </p>

    </div>


    <!-- =====================================================
         REPORT SUMMARY
         ===================================================== -->

    <div class="section-heading">

        <div>

            <h3>
                System Summary
            </h3>

            <p>
                Current overview of workforce and payroll records.
            </p>

        </div>

    </div>


    <div class="cards">

        <div class="card dashboard-stat">

            <div class="stat-label">
                Total Employees
            </div>

            <div class="stat-value">
                <?php echo (int) $totalEmployees; ?>
            </div>

            <div class="stat-description">
                Registered employees
            </div>

        </div>


        <div class="card dashboard-stat">

            <div class="stat-label">
                Active Employees
            </div>

            <div class="stat-value">
                <?php echo (int) $activeEmployees; ?>
            </div>

            <div class="stat-description">
                Currently active employees
            </div>

        </div>


        <div class="card dashboard-stat">

            <div class="stat-label">
                Attendance Records
            </div>

            <div class="stat-value">
                <?php echo (int) $totalAttendance; ?>
            </div>

            <div class="stat-description">
                Recorded attendance entries
            </div>

        </div>


        <div class="card dashboard-stat">

            <div class="stat-label">
                Payroll Records
            </div>

            <div class="stat-value">
                <?php echo (int) $totalPayroll; ?>
            </div>

            <div class="stat-description">
                Processed payroll records
            </div>

        </div>


        <div class="card dashboard-stat">

            <div class="stat-label">
                Performance Evaluations
            </div>

            <div class="stat-value">
                <?php echo (int) $totalPerformance; ?>
            </div>

            <div class="stat-description">
                Recorded performance evaluations
            </div>

        </div>


        <div class="card dashboard-stat">

            <div class="stat-label">
                Total Net Payroll
            </div>

            <div class="stat-value report-payroll-total">

                RM
                <?php
                echo number_format(
                    (float) $totalPayrollAmount,
                    2
                );
                ?>

            </div>

            <div class="stat-description">
                Combined net payroll value
            </div>

        </div>

    </div>


    <!-- =====================================================
         AVAILABLE REPORTS
         ===================================================== -->

    <div class="dashboard-section">

        <div class="section-heading">

            <div>

                <h3>
                    Available Reports
                </h3>

                <p>
                    Select a report to review detailed system records.
                </p>

            </div>

        </div>


        <div class="report-grid">

            <a
                href="report_employees.php"
                class="report-card"
            >

                <strong>
                    Employee Report
                </strong>

                <span>
                    Review employee records and filter by employment status.
                </span>

                <div class="report-link">
                    Open Report
                </div>

            </a>


            <a
                href="report_attendance.php"
                class="report-card"
            >

                <strong>
                    Attendance Report
                </strong>

                <span>
                    Review attendance records by employee and date.
                </span>

                <div class="report-link">
                    Open Report
                </div>

            </a>


            <a
                href="report_payroll.php"
                class="report-card"
            >

                <strong>
                    Payroll Report
                </strong>

                <span>
                    Review processed payroll records and payroll totals.
                </span>

                <div class="report-link">
                    Open Report
                </div>

            </a>


            <a
                href="report_performance.php"
                class="report-card"
            >

                <strong>
                    Performance Report
                </strong>

                <span>
                    Review employee performance evaluations and ratings.
                </span>

                <div class="report-link">
                    Open Report
                </div>

            </a>

        </div>

    </div>

</main>

</div>


</body>

</html>