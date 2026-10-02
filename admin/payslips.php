<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


$stmt = $pdo->query("
    SELECT
        ps.payslip_id,
        ps.generated_date,
        ps.payslip_file,

        p.payroll_id,
        p.payroll_month,
        p.net_pay,

        e.employee_code,
        e.first_name,
        e.last_name

    FROM payslips ps

    INNER JOIN payroll p
        ON ps.payroll_id = p.payroll_id

    INNER JOIN employees e
        ON p.employee_id = e.employee_id

    ORDER BY
        ps.generated_date DESC
");

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
        Payslips - The Hive HR
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
            Payslip Management
        </h2>

        <p>
            View and access generated employee payslips.
        </p>

    </div>


    <?php if (isset($_GET["generated"])): ?>

        <div class="message message-success">
            Payslip generated successfully.
        </div>

    <?php endif; ?>


    <?php if (isset($_GET["exists"])): ?>

        <div class="message">
            A payslip already exists for that payroll record.
        </div>

    <?php endif; ?>


    <div class="table-container">

        <div class="table-header">

            <div>

                <h3>
                    Generated Payslips
                </h3>

                <p>
                    Total payslips:
                    <strong>
                        <?php echo count($payslips); ?>
                    </strong>
                </p>

            </div>

        </div>


        <table>

            <thead>

                <tr>

                    <th>
                        Employee
                    </th>

                    <th>
                        Payroll Month
                    </th>

                    <th>
                        Net Pay
                    </th>

                    <th>
                        Generated Date
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

                            <div class="employee-cell">

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $payslip["first_name"]
                                        . " "
                                        . $payslip["last_name"]
                                    );
                                    ?>
                                </strong>

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $payslip["employee_code"]
                                    );
                                    ?>
                                </span>

                            </div>

                        </td>


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

                            <?php
                            echo htmlspecialchars(
                                date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $payslip["generated_date"]
                                    )
                                )
                            );
                            ?>

                        </td>


                        <td>

                            <a
                                class="btn btn-small"
                                href="../download_payslip.php?id=<?php
                                    echo (int) $payslip["payslip_id"];
                                ?>"
                                target="_blank"
                            >
                                View PDF
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td
                        colspan="5"
                        class="empty-state"
                    >
                        No payslips have been generated yet.
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