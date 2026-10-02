<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


$employeeId =
    trim($_GET["employee_id"] ?? "");


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


$sql = "
    SELECT
        pf.rating,
        pf.remarks,
        pf.evaluation_date,

        e.employee_code,
        e.first_name,
        e.last_name

    FROM performance pf

    INNER JOIN employees e
        ON pf.employee_id = e.employee_id

    WHERE 1 = 1
";

$params = [];


if (
    $employeeId !== ""
    &&
    is_numeric($employeeId)
) {

    $sql .= "
        AND pf.employee_id = :employee_id
    ";

    $params[":employee_id"] =
        (int) $employeeId;
}


$sql .= "
    ORDER BY
        pf.evaluation_date DESC,
        e.employee_code ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$records =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


$totalRating = 0;

foreach ($records as $record) {

    $totalRating +=
        (int) $record["rating"];
}


$averageRating =
    count($records) > 0
        ? $totalRating / count($records)
        : 0;


function getRatingLabel($rating)
{
    switch ((int) $rating) {

        case 1:
            return "Poor";

        case 2:
            return "Needs Improvement";

        case 3:
            return "Satisfactory";

        case 4:
            return "Good";

        case 5:
            return "Excellent";

        default:
            return "Unknown";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Performance Report - The Hive HR
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

        <h2>Performance Report</h2>

        <p>
            Employee performance evaluation records.
        </p>

    </div>


    <div class="form-container no-print">

        <form method="GET">

            <div class="form-group">

                <label>
                    Employee
                </label>

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


            <button
                type="submit"
                class="btn"
            >
                Generate Report
            </button>


            <a
                href="report_performance.php"
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

        <h3>Average Rating</h3>

        <p>
            <?php
            echo number_format(
                $averageRating,
                2
            );
            ?>
            / 5
        </p>

    </div>


    <br>


    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Employee</th>
                    <th>Evaluation Date</th>
                    <th>Rating</th>
                    <th>Remarks</th>

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
                                    "d F Y",
                                    strtotime(
                                        $record["evaluation_date"]
                                    )
                                )
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $record["rating"]
                                . "/5 - "
                                . getRatingLabel(
                                    $record["rating"]
                                )
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $record["remarks"]
                                )
                            );
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td colspan="4">
                        No performance evaluations found.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>


</body>

</html>