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

$employeeId =
    (int) $_SESSION["employee_id"];


/*
|--------------------------------------------------------------------------
| Retrieve only this employee's payslips
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        ps.payslip_id,
        ps.generated_date,
        ps.payslip_file,

        p.payroll_month,
        p.regular_hours,
        p.overtime_hours,
        p.basic_pay,
        p.overtime_pay,
        p.deductions,
        p.net_pay

    FROM payslips ps

    INNER JOIN payroll p
        ON ps.payroll_id = p.payroll_id

    WHERE p.employee_id = :employee_id

    ORDER BY
        p.payroll_month DESC
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

$payslips =
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
        My Payslips - The Hive HR
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
require "../includes/employee_sidebar.php";
?>


    <main class="content">

    <div class="page-title">

        <h2>
            My Payslips
        </h2>

        <p>
            Review your payroll information and access your generated payslips.
        </p>

    </div>


    <!-- =====================================================
         PAYSLIP SUMMARY
         ===================================================== -->

    <div class="dashboard-section">

        <div class="section-heading">

            <div>

                <h3>
                    Payslip Summary
                </h3>

                <p>
                    Overview of your available payroll documents.
                </p>

            </div>

        </div>


        <div class="payslip-summary-grid">

            <div class="payslip-summary-item">

                <span>
                    Available Payslips
                </span>

                <strong>
                    <?php echo count($payslips); ?>
                </strong>

            </div>


            <div class="payslip-summary-item">

                <span>
                    Latest Payroll
                </span>

                <strong>

                    <?php if (count($payslips) > 0): ?>

                        <?php
                        echo htmlspecialchars(
                            date(
                                "F Y",
                                strtotime(
                                    $payslips[0]["payroll_month"]
                                )
                            )
                        );
                        ?>

                    <?php else: ?>

                        Not Available

                    <?php endif; ?>

                </strong>

            </div>


            <div class="payslip-summary-item">

                <span>
                    Latest Net Pay
                </span>

                <strong>

                    <?php if (count($payslips) > 0): ?>

                        RM
                        <?php
                        echo number_format(
                            (float) $payslips[0]["net_pay"],
                            2
                        );
                        ?>

                    <?php else: ?>

                        —

                    <?php endif; ?>

                </strong>

            </div>

        </div>

    </div>


    <!-- =====================================================
         PAYSLIP HISTORY
         ===================================================== -->

    <div class="table-container">

        <div class="table-header">

            <div>

                <h3>
                    Payslip History
                </h3>

                <p>
                    Total payslips:
                    <strong>
                        <?php echo count($payslips); ?>
                    </strong>
                </p>

            </div>

        </div>


        <table class="payroll-table">

            <thead>

                <tr>

                    <th>
                        Payroll Month
                    </th>

                    <th>
                        Regular Hours
                    </th>

                    <th>
                        OT Hours
                    </th>

                    <th>
                        Deductions
                    </th>

                    <th>
                        Net Pay
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php if (count($payslips) > 0): ?>

                <?php foreach ($payslips as $payslip): ?>

                    <tr>

                        <td>

                            <strong class="payroll-month">

                                <?php
                                echo htmlspecialchars(
                                    date(
                                        "F Y",
                                        strtotime(
                                            $payslip["payroll_month"]
                                        )
                                    )
                                );
                                ?>

                            </strong>

                        </td>


                        <td>

                            <?php
                            echo number_format(
                                (float) $payslip["regular_hours"],
                                2
                            );
                            ?>
                            hrs

                        </td>


                        <td>

                            <?php
                            echo number_format(
                                (float) $payslip["overtime_hours"],
                                2
                            );
                            ?>
                            hrs

                        </td>


                        <td>

                            <?php if (
                                (float) $payslip["deductions"] > 0
                            ): ?>

                                <span class="deduction-value">

                                    - RM
                                    <?php
                                    echo number_format(
                                        (float) $payslip["deductions"],
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


                        <td>

                            <strong class="net-pay">

                                RM
                                <?php
                                echo number_format(
                                    (float) $payslip["net_pay"],
                                    2
                                );
                                ?>

                            </strong>

                        </td>


                        <td>

                            <a
                                class="btn btn-small"
                                href="../download_payslip.php?id=<?php
                                    echo (int) $payslip["payslip_id"];
                                ?>"
                                target="_blank"
                            >
                                View Payslip
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td
                        colspan="6"
                        class="empty-state"
                    >
                        No payslips are currently available.
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