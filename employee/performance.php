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
| Retrieve only this employee's evaluations
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        performance_id,
        rating,
        remarks,
        evaluation_date,
        created_at

    FROM performance

    WHERE employee_id = :employee_id

    ORDER BY
        evaluation_date DESC,
        performance_id DESC
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

$performanceRecords =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        My Performance - The Hive HR
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
            My Performance
        </h2>

        <p>
            Review your performance evaluation history and ratings.
        </p>

    </div>


    <!-- =====================================================
         PERFORMANCE SUMMARY
         ===================================================== -->

    <div class="dashboard-section">

        <div class="section-heading">

            <div>

                <h3>
                    Performance Summary
                </h3>

                <p>
                    Overview of your recorded evaluations.
                </p>

            </div>

        </div>


        <div class="performance-summary-grid">

            <div class="performance-summary-item">

                <span>
                    Total Evaluations
                </span>

                <strong>
                    <?php echo count($performanceRecords); ?>
                </strong>

            </div>


            <div class="performance-summary-item">

                <span>
                    Latest Rating
                </span>

                <strong>

                    <?php if (count($performanceRecords) > 0): ?>

                        <?php
                        echo htmlspecialchars(
                            $performanceRecords[0]["rating"]
                            . "/5"
                        );
                        ?>

                    <?php else: ?>

                        —

                    <?php endif; ?>

                </strong>

            </div>


            <div class="performance-summary-item">

                <span>
                    Latest Evaluation
                </span>

                <strong>

                    <?php if (count($performanceRecords) > 0): ?>

                        <?php
                        echo htmlspecialchars(
                            date(
                                "d M Y",
                                strtotime(
                                    $performanceRecords[0]["evaluation_date"]
                                )
                            )
                        );
                        ?>

                    <?php else: ?>

                        Not Available

                    <?php endif; ?>

                </strong>

            </div>

        </div>

    </div>


    <!-- =====================================================
         PERFORMANCE HISTORY
         ===================================================== -->

    <div class="table-container">

        <div class="table-header">

            <div>

                <h3>
                    Evaluation History
                </h3>

                <p>
                    Total evaluations:
                    <strong>
                        <?php echo count($performanceRecords); ?>
                    </strong>
                </p>

            </div>

        </div>


        <table>

            <thead>

                <tr>
                    <th>Evaluation Date</th>
                    <th>Rating</th>
                    <th>Remarks</th>
                </tr>

            </thead>


            <tbody>

            <?php if (count($performanceRecords) > 0): ?>

                <?php foreach ($performanceRecords as $record): ?>

                    <?php
                    $rating = (int) $record["rating"];

                    $ratingClass = "rating-average";

                    if ($rating <= 2) {
                        $ratingClass = "rating-low";
                    } elseif ($rating >= 4) {
                        $ratingClass = "rating-high";
                    }
                    ?>

                    <tr>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                date(
                                    "d M Y",
                                    strtotime(
                                        $record["evaluation_date"]
                                    )
                                )
                            );
                            ?>

                        </td>


                        <td>

                            <span
                                class="rating-badge <?php
                                echo $ratingClass;
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $rating
                                    . "/5 - "
                                    . getRatingLabel($rating)
                                );
                                ?>

                            </span>

                        </td>


                        <td class="remarks-cell">

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

                    <td
                        colspan="3"
                        class="empty-state"
                    >
                        No performance evaluations are currently available.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- =====================================================
         READ-ONLY NOTICE
         ===================================================== -->

    <div class="info-box">

        <strong>
            Performance evaluations are read-only.
        </strong>

        <p>
            Evaluations are recorded and maintained by the administrator.
        </p>

    </div>

</main>

</div>


</body>

</html>