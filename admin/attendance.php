<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


$selectedDate = trim(
    $_GET["date"] ?? ""
);

$selectedEmployee = trim(
    $_GET["employee_id"] ?? ""
);


// Employee list for filter
$stmt = $pdo->query("
    SELECT
        employee_id,
        employee_code,
        first_name,
        last_name
    FROM employees
    ORDER BY first_name, last_name
");

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Build attendance query
$sql = "
    SELECT
        a.attendance_id,
        a.attendance_date,
        a.clock_in,
        a.clock_out,
        a.total_hours,
        a.status,
        e.employee_id,
        e.employee_code,
        e.first_name,
        e.last_name
    FROM attendance a
    INNER JOIN employees e
        ON a.employee_id = e.employee_id
    WHERE 1 = 1
";

$params = [];


if ($selectedDate !== "") {

    $sql .= "
        AND a.attendance_date = :attendance_date
    ";

    $params[":attendance_date"] =
        $selectedDate;
}


if (
    $selectedEmployee !== ""
    &&
    is_numeric($selectedEmployee)
) {

    $sql .= "
        AND a.employee_id = :employee_id
    ";

    $params[":employee_id"] =
        (int) $selectedEmployee;
}


$sql .= "
    ORDER BY
        a.attendance_date DESC,
        e.first_name ASC
";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$records =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Attendance Management - The Hive HR
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

    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <div class="page-title">

        <h2>
            Attendance Management
        </h2>

        <p>
            Review and filter employee attendance records.
        </p>

    </div>


    <!-- =====================================================
         ATTENDANCE FILTERS
         ===================================================== -->

    <div class="filter-panel">

        <div class="filter-heading">

            <h3>
                Filter Attendance
            </h3>

            <p>
                Search attendance records by employee, date,
                or both.
            </p>

        </div>


        <form
            method="GET"
            action="attendance.php"
        >

            <div class="filter-grid">

                <div class="form-group">

                    <label for="employee_id">
                        Employee
                    </label>

                    <select
                        name="employee_id"
                        id="employee_id"
                    >

                        <option value="">
                            All Employees
                        </option>

                        <?php foreach ($employees as $employee): ?>

                            <option
                                value="<?php
                                    echo (int) $employee["employee_id"];
                                ?>"
                                <?php
                                if (
                                    (string) $employee["employee_id"]
                                    ===
                                    (string) $selectedEmployee
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

                    <label for="date">
                        Attendance Date
                    </label>

                    <input
                        type="date"
                        name="date"
                        id="date"
                        value="<?php
                            echo htmlspecialchars(
                                $selectedDate
                            );
                        ?>"
                    >

                </div>

            </div>


            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Apply Filter
                </button>

                <a
                    href="attendance.php"
                    class="btn btn-secondary"
                >
                    Clear Filter
                </a>

            </div>

        </form>

    </div>


    <!-- =====================================================
         ATTENDANCE RECORDS
         ===================================================== -->

    <div class="table-container">

        <div class="table-header">

            <div>

                <h3>
                    Attendance Records
                </h3>

                <p>
                    Records found:
                    <strong>
                        <?php echo count($records); ?>
                    </strong>
                </p>

            </div>

        </div>


        <table>

            <thead>

                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Total Hours</th>
                    <th>Status</th>
                </tr>

            </thead>


            <tbody>

            <?php if (count($records) > 0): ?>

                <?php foreach ($records as $record): ?>

                    <tr>

                        <td>

                            <div class="employee-cell">

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $record["first_name"]
                                        . " "
                                        . $record["last_name"]
                                    );
                                    ?>
                                </strong>

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $record["employee_code"]
                                    );
                                    ?>
                                </span>

                            </div>

                        </td>


                        <td>

                            <?php
                            echo date(
                                "d M Y",
                                strtotime(
                                    $record["attendance_date"]
                                )
                            );
                            ?>

                        </td>


                        <td>

                            <?php if (!empty($record["clock_in"])): ?>

                                <?php
                                echo date(
                                    "h:i A",
                                    strtotime(
                                        $record["clock_in"]
                                    )
                                );
                                ?>

                            <?php else: ?>

                                <span class="muted-text">
                                    —
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            <?php if (!empty($record["clock_out"])): ?>

                                <?php
                                echo date(
                                    "h:i A",
                                    strtotime(
                                        $record["clock_out"]
                                    )
                                );
                                ?>

                            <?php else: ?>

                                <span class="muted-text">
                                    —
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            <strong>
                                <?php
                                echo number_format(
                                    (float) $record["total_hours"],
                                    2
                                );
                                ?>
                            </strong>

                            <span class="muted-text">
                                hrs
                            </span>

                        </td>


                        <td>

                            <?php if ($record["status"] === "Present"): ?>

                                <span class="status status-present">
                                    Present
                                </span>

                            <?php elseif ($record["status"] === "Absent"): ?>

                                <span class="status status-absent">
                                    Absent
                                </span>

                            <?php else: ?>

                                <span class="status status-incomplete">
                                    <?php
                                    echo htmlspecialchars(
                                        $record["status"]
                                    );
                                    ?>
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td
                        colspan="6"
                        class="empty-state"
                    >
                        No attendance records match the selected filters.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</div>

</body>

</html>