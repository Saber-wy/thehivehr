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

$employeeId = (int) $_SESSION["employee_id"];


/*
|--------------------------------------------------------------------------
| Retrieve employee information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        employee_code,
        first_name,
        last_name,
        email,
        position,
        employment_status
    FROM employees
    WHERE employee_id = :employee_id
    LIMIT 1
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

$employee = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$employee) {
    die("Employee record not found.");
}


/*
|--------------------------------------------------------------------------
| Today's attendance
|--------------------------------------------------------------------------
*/

$today = date("Y-m-d");

$stmt = $pdo->prepare("
    SELECT
        clock_in,
        clock_out,
        total_hours,
        status
    FROM attendance
    WHERE employee_id = :employee_id
    AND attendance_date = :attendance_date
    LIMIT 1
");

$stmt->execute([
    ":employee_id" => $employeeId,
    ":attendance_date" => $today
]);

$todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Total attendance records
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM attendance
    WHERE employee_id = :employee_id
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

$totalAttendance = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Total payroll records
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM payroll
    WHERE employee_id = :employee_id
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

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
        Employee Dashboard - The Hive HR
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
require "../includes/employee_sidebar.php";
?>


    <main class="content">

    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <div class="page-title">

        <h2>
            Employee Dashboard
        </h2>

        <p>
            Welcome back,
            <strong>
                <?php
                echo htmlspecialchars(
                    $employee["first_name"]
                    . " "
                    . $employee["last_name"]
                );
                ?>
            </strong>.
            Here is an overview of your employee account.
        </p>

    </div>


    <!-- =====================================================
         EMPLOYEE SUMMARY
         ===================================================== -->

    <div class="cards">

        <div class="card dashboard-stat">

            <div class="stat-label">
                Employee Code
            </div>

            <div class="stat-value employee-stat-text">
                <?php
                echo htmlspecialchars(
                    $employee["employee_code"]
                );
                ?>
            </div>

            <div class="stat-description">
                Your employee identification code
            </div>

        </div>


        <div class="card dashboard-stat">

            <div class="stat-label">
                Position
            </div>

            <div class="stat-value employee-stat-text">
                <?php
                echo htmlspecialchars(
                    $employee["position"] ?? "Not Assigned"
                );
                ?>
            </div>

            <div class="stat-description">
                Current position
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
                Your recorded attendance entries
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
                Your processed payroll records
            </div>

        </div>

    </div>


    <!-- =====================================================
         TODAY'S ATTENDANCE
         ===================================================== -->

    <div class="dashboard-section">

        <div class="section-heading">

            <div>

                <h3>
                    Today's Attendance
                </h3>

                <p>
                    <?php echo date("d F Y"); ?>
                </p>

            </div>


            <a
                href="attendance.php"
                class="btn btn-primary btn-small"
            >
                My Attendance
            </a>

        </div>


        <?php if (!$todayAttendance): ?>

            <!-- No attendance record today -->

            <div class="attendance-state">

                <div>

                    <span class="status status-pending">
                        Not Clocked In
                    </span>

                    <h4>
                        No attendance recorded today
                    </h4>

                    <p>
                        You have not clocked in for today.
                    </p>

                </div>

                <a
                    href="attendance.php"
                    class="btn btn-primary"
                >
                    Go to Attendance
                </a>

            </div>


        <?php elseif (
            $todayAttendance["clock_in"] !== null
            &&
            $todayAttendance["clock_out"] === null
        ): ?>

            <!-- Clocked in but not clocked out -->

            <div class="attendance-state">

                <div>

                    <span class="status status-incomplete">
                        Incomplete
                    </span>

                    <h4>
                        You are currently clocked in
                    </h4>

                    <p>
                        Remember to clock out when your work period is complete.
                    </p>

                </div>

                <a
                    href="attendance.php"
                    class="btn btn-primary"
                >
                    Go to Attendance
                </a>

            </div>


            <div class="attendance-details">

                <div class="attendance-detail">

                    <span>
                        Clock In
                    </span>

                    <strong>
                        <?php
                        echo date(
                            "h:i A",
                            strtotime(
                                $todayAttendance["clock_in"]
                            )
                        );
                        ?>
                    </strong>

                </div>


                <div class="attendance-detail">

                    <span>
                        Clock Out
                    </span>

                    <strong class="muted-text">
                        —
                    </strong>

                </div>


                <div class="attendance-detail">

                    <span>
                        Status
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $todayAttendance["status"]
                        );
                        ?>
                    </strong>

                </div>

            </div>


        <?php else: ?>

            <!-- Completed attendance -->

            <div class="attendance-state">

                <div>

                    <span class="status status-present">
                        Present
                    </span>

                    <h4>
                        Attendance completed
                    </h4>

                    <p>
                        Your attendance for today has been recorded.
                    </p>

                </div>

            </div>


            <div class="attendance-details">

                <div class="attendance-detail">

                    <span>
                        Clock In
                    </span>

                    <strong>
                        <?php
                        echo date(
                            "h:i A",
                            strtotime(
                                $todayAttendance["clock_in"]
                            )
                        );
                        ?>
                    </strong>

                </div>


                <div class="attendance-detail">

                    <span>
                        Clock Out
                    </span>

                    <strong>
                        <?php
                        echo date(
                            "h:i A",
                            strtotime(
                                $todayAttendance["clock_out"]
                            )
                        );
                        ?>
                    </strong>

                </div>


                <div class="attendance-detail">

                    <span>
                        Total Hours
                    </span>

                    <strong>
                        <?php
                        echo number_format(
                            (float) $todayAttendance["total_hours"],
                            2
                        );
                        ?>
                        hrs
                    </strong>

                </div>


                <div class="attendance-detail">

                    <span>
                        Status
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $todayAttendance["status"]
                        );
                        ?>
                    </strong>

                </div>

            </div>

        <?php endif; ?>

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
                    Access your employee services.
                </p>

            </div>

        </div>


        <div class="quick-actions">

            <a
                href="attendance.php"
                class="quick-action"
            >

                <strong>
                    My Attendance
                </strong>

                <span>
                    Clock in, clock out and review your attendance history.
                </span>

            </a>


            <a
                href="profile.php"
                class="quick-action"
            >

                <strong>
                    My Profile
                </strong>

                <span>
                    Review your employee information and employment details.
                </span>

            </a>


            <a
                href="payslips.php"
                class="quick-action"
            >

                <strong>
                    My Payslips
                </strong>

                <span>
                    View your generated payroll payslips.
                </span>

            </a>


            <a
                href="performance.php"
                class="quick-action"
            >

                <strong>
                    My Performance
                </strong>

                <span>
                    Review your performance evaluation history.
                </span>

            </a>

        </div>

    </div>

</main>

</div>


</body>

</html>