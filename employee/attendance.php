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
require_once "../includes/csrf.php";

$employeeId = (int) $_SESSION["employee_id"];
$today = date("Y-m-d");

$stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = :employee_id
    AND attendance_date = :attendance_date
    LIMIT 1
");

$stmt->execute([
    ":employee_id" => $employeeId,
    ":attendance_date" => $today
]);

$attendance = $stmt->fetch(PDO::FETCH_ASSOC);


// Attendance history
$stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = :employee_id
    ORDER BY attendance_date DESC
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Attendance - The Hive HR
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
            My Attendance
        </h2>

        <p>
            Record your working hours and review your attendance history.
        </p>

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
                    <?php
                    echo date(
                        "d F Y",
                        strtotime($today)
                    );
                    ?>
                </p>

            </div>

        </div>


        <?php if (!$attendance): ?>

            <div class="attendance-state">

                <div>

                    <span class="status status-pending">
                        Not Clocked In
                    </span>

                    <h4>
                        Your work session has not started
                    </h4>

                    <p>
                        Clock in when you begin your work period.
                    </p>

                </div>


                <form
                    method="POST"
                    action="clock_in.php"
                    class="inline-form"
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
                        class="btn btn-primary"
                    >
                        Clock In
                    </button>

                </form>

            </div>


        <?php elseif (
            $attendance["clock_in"] !== null
            &&
            $attendance["clock_out"] === null
        ): ?>

            <div class="attendance-state">

                <div>

                    <span class="status status-incomplete">
                        Incomplete
                    </span>

                    <h4>
                        You are currently clocked in
                    </h4>

                    <p>
                        Clock out when your work period is complete.
                    </p>

                </div>


                <form
                    method="POST"
                    action="clock_out.php"
                    class="inline-form"
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
                        class="btn btn-primary"
                    >
                        Clock Out
                    </button>

                </form>

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
                                $attendance["clock_in"]
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
                        Total Hours
                    </span>

                    <strong class="muted-text">
                        Pending
                    </strong>

                </div>


                <div class="attendance-detail">

                    <span>
                        Status
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $attendance["status"]
                        );
                        ?>
                    </strong>

                </div>

            </div>


        <?php else: ?>

            <div class="attendance-state">

                <div>

                    <span class="status status-present">
                        Present
                    </span>

                    <h4>
                        Today's attendance is complete
                    </h4>

                    <p>
                        Your working hours have been recorded successfully.
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
                                $attendance["clock_in"]
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
                                $attendance["clock_out"]
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
                            (float) $attendance["total_hours"],
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
                            $attendance["status"]
                        );
                        ?>
                    </strong>

                </div>

            </div>

        <?php endif; ?>

    </div>


    <!-- =====================================================
         ATTENDANCE HISTORY
         ===================================================== -->

    <div class="table-container">

        <div class="table-header">

            <div>

                <h3>
                    Attendance History
                </h3>

                <p>
                    Total records:
                    <strong>
                        <?php echo count($history); ?>
                    </strong>
                </p>

            </div>

        </div>


        <table>

            <thead>

                <tr>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Total Hours</th>
                    <th>Status</th>
                </tr>

            </thead>


            <tbody>

            <?php if (count($history) > 0): ?>

                <?php foreach ($history as $record): ?>

                    <tr>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                date(
                                    "d M Y",
                                    strtotime(
                                        $record["attendance_date"]
                                    )
                                )
                            );
                            ?>

                        </td>


                        <td>

                            <?php if ($record["clock_in"] !== null): ?>

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

                            <?php if ($record["clock_out"] !== null): ?>

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

                            <?php if (
                                $record["clock_out"] !== null
                            ): ?>

                                <?php
                                echo number_format(
                                    (float) $record["total_hours"],
                                    2
                                );
                                ?>
                                hrs

                            <?php else: ?>

                                <span class="muted-text">
                                    Pending
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            <?php
                            $status =
                                strtolower(
                                    $record["status"]
                                );

                            $statusClass =
                                "status-" . $status;
                            ?>

                            <span
                                class="status <?php
                                echo htmlspecialchars(
                                    $statusClass
                                );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $record["status"]
                                );
                                ?>

                            </span>

                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td
                        colspan="5"
                        class="empty-state"
                    >
                        No attendance records found.
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