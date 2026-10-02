<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


$employeeId =
    trim($_GET["employee_id"] ?? "");

$startDate =
    trim($_GET["start_date"] ?? "");

$endDate =
    trim($_GET["end_date"] ?? "");


/*
|--------------------------------------------------------------------------
| Employees for filter
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        employee_id,
        employee_code,
        first_name,
        last_name
    FROM employees
    ORDER BY employee_code
");

$employees =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Build report query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        a.attendance_date,
        a.clock_in,
        a.clock_out,
        a.total_hours,
        a.status,

        e.employee_code,
        e.first_name,
        e.last_name

    FROM attendance a

    INNER JOIN employees e
        ON a.employee_id = e.employee_id

    WHERE 1 = 1
";

$params = [];


if (
    $employeeId !== ""
    &&
    is_numeric($employeeId)
) {

    $sql .= "
        AND a.employee_id = :employee_id
    ";

    $params[":employee_id"] =
        (int) $employeeId;
}


if ($startDate !== "") {

    $sql .= "
        AND a.attendance_date >= :start_date
    ";

    $params[":start_date"] =
        $startDate;
}


if ($endDate !== "") {

    $sql .= "
        AND a.attendance_date <= :end_date
    ";

    $params[":end_date"] =
        $endDate;
}


$sql .= "
    ORDER BY
        a.attendance_date DESC,
        e.employee_code ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$records =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Calculate report total
|--------------------------------------------------------------------------
*/

$totalHours = 0;

foreach ($records as $record) {

    $totalHours +=
        (float) $record["total_hours"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Attendance Report - The Hive HR
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

        <h2>Attendance Report</h2>

        <p>
            Employee attendance and recorded working hours.
        </p>

    </div>


    <div class="form-container no-print">

        <form method="GET">


            <div class="form-group">

                <label>Employee</label>

                <select name="employee_id">

                    <option value="">
                        All Employees
                    </option>

                    <?php foreach ($employees as $employee): ?>

                        <option
                            value="<?php
                            echo $employee["employee_id"];
                            ?>"

                            <?php
                            if (
                                (string) $employeeId
                                ===
                                (string) $employee["employee_id"]
                            ) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $employee["employee_code"]
                                . " - "
                                . $employee["first_name"]
                                . " "
                                . $employee["last_name"]
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>Start Date</label>

                <input
                    type="date"
                    name="start_date"
                    value="<?php
                    echo htmlspecialchars($startDate);
                    ?>"
                >

            </div>


            <div class="form-group">

                <label>End Date</label>

                <input
                    type="date"
                    name="end_date"
                    value="<?php
                    echo htmlspecialchars($endDate);
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
                href="report_attendance.php"
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


    <div class="card">

        <h3>Total Recorded Hours</h3>

        <p>
            <?php
            echo number_format(
                $totalHours,
                2
            );
            ?>
        </p>

    </div>


    <br>


    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Employee</th>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Hours</th>
                    <th>Status</th>

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
                                $record["attendance_date"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $record["clock_in"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $record["clock_out"] ?? "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo number_format(
                                (float) $record["total_hours"],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $record["status"]
                            );
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td colspan="6">
                        No attendance records found.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>


</body>

</html>