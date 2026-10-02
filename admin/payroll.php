<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../includes/csrf.php";

// Retrieve payroll records
$stmt = $pdo->query("
    SELECT
        p.payroll_id,
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
        e.last_name
    FROM payroll p
    INNER JOIN employees e
        ON p.employee_id = e.employee_id
    ORDER BY
        p.payroll_month DESC,
        p.payroll_id DESC
");

$payrollRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Payroll Management - The Hive HR
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

        <div class="page-title-row">

            <div>

                <h2>
                    Payroll Management
                </h2>

                <p>
                    Process and review employee payroll records.
                </p>

            </div>

            <a
                href="payroll_process.php"
                class="btn btn-primary"
            >
                Process Payroll
            </a>

        </div>

    </div>


    <!-- =====================================================
         PAYROLL RECORDS
         ===================================================== -->

    <div class="table-container">

        <div class="table-header">

            <div>

                <h3>
                    Payroll Records
                </h3>

                <p>
                    Processed records:
                    <strong>
                        <?php echo count($payrollRecords); ?>
                    </strong>
                </p>

            </div>

        </div>


        <table class="payroll-table">

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
                    <th>Action</th>
                </tr>

            </thead>


            <tbody>

            <?php if (count($payrollRecords) > 0): ?>

                <?php foreach ($payrollRecords as $record): ?>

                    <tr>

                        <!-- Employee -->

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


                        <!-- Payroll Month -->

                        <td>

                            <strong class="payroll-month">

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

                            </strong>

                        </td>


                        <!-- Regular Hours -->

                        <td>

                            <?php
                            echo number_format(
                                (float) $record["regular_hours"],
                                2
                            );
                            ?>

                            <span class="muted-text">
                                hrs
                            </span>

                        </td>


                        <!-- Overtime Hours -->

                        <td>

                            <?php
                            echo number_format(
                                (float) $record["overtime_hours"],
                                2
                            );
                            ?>

                            <span class="muted-text">
                                hrs
                            </span>

                        </td>


                        <!-- Basic Pay -->

                        <td class="money-cell">

                            RM
                            <?php
                            echo number_format(
                                (float) $record["basic_pay"],
                                2
                            );
                            ?>

                        </td>


                        <!-- Overtime Pay -->

                        <td class="money-cell">

                            RM
                            <?php
                            echo number_format(
                                (float) $record["overtime_pay"],
                                2
                            );
                            ?>

                        </td>


                        <!-- Deductions -->

                        <td>

                            <?php if ((float) $record["deductions"] > 0): ?>

                                <span class="deduction-value">

                                    - RM
                                    <?php
                                    echo number_format(
                                        (float) $record["deductions"],
                                        2
                                    );
                                    ?>

                                </span>

                            <?php else: ?>

                                <span class="muted-text">
                                    RM 0.00
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- Net Pay -->

                        <td>

                            <strong class="net-pay">

                                RM
                                <?php
                                echo number_format(
                                    (float) $record["net_pay"],
                                    2
                                );
                                ?>

                            </strong>

                        </td>


                        <!-- Payslip Action -->

                        <td>

                            <form
                                method="POST"
                                action="payslip_generate.php"
                                class="inline-form"
                                onsubmit="return confirm(
                                    'Are you sure you want to generate this payslip?'
                                );"
                            >

                                <input
                                    type="hidden"
                                    name="payroll_id"
                                    value="<?php
                                        echo (int) $record["payroll_id"];
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
                                    class="btn btn-small"
                                >
                                    Generate Payslip
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td
                        colspan="9"
                        class="empty-state"
                    >
                        No payroll records have been processed yet.
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