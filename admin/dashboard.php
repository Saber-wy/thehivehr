<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Dashboard statistics
|--------------------------------------------------------------------------
*/

// Total employees
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM employees
");

$totalEmployees = $stmt->fetchColumn();


// Active employees
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM employees
    WHERE employment_status = 'Active'
");

$activeEmployees = $stmt->fetchColumn();


// Total attendance records
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM attendance
");

$totalAttendance = $stmt->fetchColumn();


// Total payroll records
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM payroll
");

$totalPayroll = $stmt->fetchColumn();

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
        Admin Dashboard - The Hive HR
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<header class="topbar">

    <h1>
        The Hive HR
    </h1>

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

    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <div class="page-title">

        <h2>
            Admin Dashboard
        </h2>

        <p>
            Welcome back,
            <?php echo htmlspecialchars($_SESSION["username"]); ?>.
            Here is an overview of The Hive HR workforce system.
        </p>

    </div>


    <!-- =====================================================
         DASHBOARD STATISTICS
         ===================================================== -->

    <div class="cards">

        <div class="card dashboard-stat">

            <div class="stat-label">
                Total Employees
            </div>

            <div class="stat-value">
                <?php echo (int) $totalEmployees; ?>
            </div>

            <div class="stat-description">
                Registered employee records
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

    </div>


    <!-- =====================================================
         QUICK ACTIONS
         ===================================================== -->

    <div class="dashboard-section">

        <div class="section-heading">

            <div>

                <h3>
                    Quick Actions
                </h3>

                <p>
                    Access frequently used administrative functions.
                </p>

            </div>

        </div>


        <div class="quick-actions">

            <a
                href="employees.php"
                class="quick-action"
            >

                <strong>
                    Employee Management
                </strong>

                <span>
                    Add, edit and manage employee records.
                </span>

            </a>


            <a
                href="attendance.php"
                class="quick-action"
            >

                <strong>
                    Attendance
                </strong>

                <span>
                    Review employee attendance records.
                </span>

            </a>


            <a
                href="payroll.php"
                class="quick-action"
            >

                <strong>
                    Payroll
                </strong>

                <span>
                    Process and review employee payroll.
                </span>

            </a>


            <a
                href="performance.php"
                class="quick-action"
            >

                <strong>
                    Performance
                </strong>

                <span>
                    Manage employee performance evaluations.
                </span>

            </a>


            <a
                href="reports.php"
                class="quick-action"
            >

                <strong>
                    Reports
                </strong>

                <span>
                    View workforce and payroll reports.
                </span>

            </a>

        </div>

    </div>

</main>

</div>

</body>

</html>