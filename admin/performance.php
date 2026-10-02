<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Retrieve performance records
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        pf.performance_id,
        pf.rating,
        pf.remarks,
        pf.evaluation_date,
        pf.created_at,

        e.employee_code,
        e.first_name,
        e.last_name

    FROM performance pf

    INNER JOIN employees e
        ON pf.employee_id = e.employee_id

    ORDER BY
        pf.evaluation_date DESC,
        pf.performance_id DESC
");

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
        Performance Management - The Hive HR
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

        <div class="page-title-row">

            <div>

                <h2>
                    Performance Management
                </h2>

                <p>
                    Record and review employee performance evaluations.
                </p>

            </div>

            <a
                href="performance_add.php"
                class="btn btn-primary"
            >
                Add Evaluation
            </a>

        </div>

    </div>


    <?php if (isset($_GET["added"])): ?>

        <div class="message message-success">
            Performance evaluation added successfully.
        </div>

    <?php endif; ?>


    <?php if (isset($_GET["updated"])): ?>

        <div class="message message-success">
            Performance evaluation updated successfully.
        </div>

    <?php endif; ?>


    <div class="table-container">

        <div class="table-header">

            <div>

                <h3>
                    Performance Evaluations
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
                    <th>Employee</th>
                    <th>Evaluation Date</th>
                    <th>Rating</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>

            </thead>


            <tbody>

            <?php if (count($performanceRecords) > 0): ?>

                <?php foreach ($performanceRecords as $record): ?>

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

                            <?php
                            $rating = (int) $record["rating"];

                            $ratingClass = "rating-average";

                            if ($rating <= 2) {
                                $ratingClass = "rating-low";
                            } elseif ($rating >= 4) {
                                $ratingClass = "rating-high";
                            }
                            ?>

                            <span
                                class="rating-badge <?php echo $ratingClass; ?>"
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


                        <td>

                            <a
                                class="btn btn-secondary btn-small"
                                href="performance_edit.php?id=<?php
                                    echo (int) $record["performance_id"];
                                ?>"
                            >
                                Edit
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
                        No performance evaluations have been recorded yet.
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