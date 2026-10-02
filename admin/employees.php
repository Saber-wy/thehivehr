<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../includes/csrf.php";

$stmt = $pdo->query("
    SELECT *
    FROM employees
    ORDER BY employee_id DESC
");

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Employees - The Hive HR
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

    <div class="page-title">

        <div class="page-title-row">

            <div>
                <h2>
                    Employee Management
                </h2>

                <p>
                    Create, review and manage employee records.
                </p>
            </div>

            <a
                class="btn btn-primary"
                href="employee_add.php"
            >
                Add Employee
            </a>

        </div>

    </div>


    <?php if (isset($_GET["status_changed"])): ?>

        <div class="message message-success">
            Employee status updated successfully.
        </div>

    <?php endif; ?>


    <div class="table-container">

        <div class="table-header">

            <div>

                <h3>
                    Employee Records
                </h3>

                <p>
                    Total employees:
                    <strong>
                        <?php echo count($employees); ?>
                    </strong>
                </p>

            </div>

        </div>


        <table>

            <thead>

                <tr>

                    <th>
                        Employee Code
                    </th>

                    <th>
                        Name
                    </th>

                    <th>
                        Email
                    </th>

                    <th>
                        Position
                    </th>

                    <th>
                        Hourly Rate
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php if (count($employees) > 0): ?>

                <?php foreach ($employees as $employee): ?>

                    <tr>

                        <td>
                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $employee["employee_code"]
                                );
                                ?>
                            </strong>
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
                                $employee["position"] ?? ""
                            );
                            ?>
                        </td>

                        <td>
                            RM
                            <?php
                            echo number_format(
                                $employee["hourly_rate"],
                                2
                            );
                            ?>
                        </td>

                        <td>

                            <?php if ($employee["employment_status"] === "Active"): ?>

                                <span class="status status-active">
                                    Active
                                </span>

                            <?php else: ?>

                                <span class="status status-inactive">
                                    Inactive
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <div class="action-group">

                                <a
                                    class="btn btn-secondary btn-small"
                                    href="employee_edit.php?id=<?php
                                        echo (int) $employee["employee_id"];
                                    ?>"
                                >
                                    Edit
                                </a>


                                <?php if ($employee["employment_status"] === "Active"): ?>

                                    <form
                                        method="POST"
                                        action="employee_status.php"
                                        class="inline-form"
                                        onsubmit="return confirm(
                                            'Are you sure you want to deactivate this employee?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="employee_id"
                                            value="<?php
                                                echo (int) $employee["employee_id"];
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php
                                                echo htmlspecialchars(
                                                    generateCsrfToken()
                                                );
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-small"
                                        >
                                            Deactivate
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <form
                                        method="POST"
                                        action="employee_status.php"
                                        class="inline-form"
                                        onsubmit="return confirm(
                                            'Are you sure you want to activate this employee?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="employee_id"
                                            value="<?php
                                                echo (int) $employee["employee_id"];
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php
                                                echo htmlspecialchars(
                                                    generateCsrfToken()
                                                );
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-primary btn-small"
                                        >
                                            Activate
                                        </button>

                                    </form>

                                <?php endif; ?>


                                <a
                                    class="btn btn-small"
                                    href="employee_account.php?id=<?php
                                        echo (int) $employee["employee_id"];
                                    ?>"
                                >
                                    Account
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="7"
                        class="empty-state"
                    >
                        No employee records found.
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