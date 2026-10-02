<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


$status =
    trim($_GET["status"] ?? "");


$sql = "
    SELECT
        employee_code,
        first_name,
        last_name,
        email,
        phone,
        position,
        hourly_rate,
        employment_status
    FROM employees
";

$params = [];


if (
    $status === "Active"
    ||
    $status === "Inactive"
) {

    $sql .= "
        WHERE employment_status = :status
    ";

    $params[":status"] = $status;
}


$sql .= "
    ORDER BY employee_code ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$employees =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Employee Report - The Hive HR
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

        <h2>Employee Report</h2>

        <p>
            Employee information and employment status.
        </p>

    </div>


    <div class="form-container no-print">

        <form method="GET">

            <div class="form-group">

                <label>
                    Employment Status
                </label>

                <select name="status">

                    <option value="">
                        All Employees
                    </option>

                    <option
                        value="Active"
                        <?php
                        if ($status === "Active") {
                            echo "selected";
                        }
                        ?>
                    >
                        Active
                    </option>

                    <option
                        value="Inactive"
                        <?php
                        if ($status === "Inactive") {
                            echo "selected";
                        }
                        ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="btn"
            >
                Generate Report
            </button>


            <a
                href="report_employees.php"
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


    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Employee Code</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Position</th>
                    <th>Hourly Rate</th>
                    <th>Status</th>

                </tr>

            </thead>


            <tbody>

            <?php if (count($employees) > 0): ?>

                <?php foreach ($employees as $employee): ?>

                    <tr>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $employee["employee_code"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $employee["first_name"]
                                . " "
                                . $employee["last_name"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $employee["email"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $employee["phone"] ?: "-"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $employee["position"] ?: "-"
                            );
                            ?>
                        </td>

                        <td>
                            RM
                            <?php
                            echo number_format(
                                (float) $employee["hourly_rate"],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $employee["employment_status"]
                            );
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td colspan="7">
                        No employee records found.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>


</body>

</html>