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

$employeeId = (int) $_SESSION["employee_id"];


$stmt = $pdo->prepare("
    SELECT
        employee_code,
        first_name,
        last_name,
        email,
        phone,
        position,
        hourly_rate,
        employment_status,
        created_at
    FROM employees
    WHERE employee_id = :employee_id
    LIMIT 1
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

$employee = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$employee) {
    die("Employee record not found.");
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
        My Profile - The Hive HR
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
            My Profile
        </h2>

        <p>
            Review your personal and employment information.
        </p>

    </div>


    <!-- =====================================================
         PROFILE SUMMARY
         ===================================================== -->

    <div class="dashboard-section">

        <div class="profile-summary">

            <div class="profile-avatar">

                <?php
                echo htmlspecialchars(
                    strtoupper(
                        substr(
                            $employee["first_name"],
                            0,
                            1
                        )
                        .
                        substr(
                            $employee["last_name"],
                            0,
                            1
                        )
                    )
                );
                ?>

            </div>


            <div class="profile-summary-info">

                <h3>

                    <?php
                    echo htmlspecialchars(
                        $employee["first_name"]
                        . " "
                        . $employee["last_name"]
                    );
                    ?>

                </h3>

                <p>

                    <?php
                    echo htmlspecialchars(
                        $employee["employee_code"]
                    );
                    ?>

                    <?php if (!empty($employee["position"])): ?>

                        &nbsp;•&nbsp;

                        <?php
                        echo htmlspecialchars(
                            $employee["position"]
                        );
                        ?>

                    <?php endif; ?>

                </p>


                <?php
                $statusClass =
                    strtolower(
                        $employee["employment_status"]
                    );
                ?>

                <span
                    class="status status-<?php
                    echo htmlspecialchars(
                        $statusClass
                    );
                    ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $employee["employment_status"]
                    );
                    ?>

                </span>

            </div>

        </div>

    </div>


    <!-- =====================================================
         PROFILE INFORMATION
         ===================================================== -->

    <div class="profile-grid">


        <!-- Personal Information -->

        <div class="dashboard-section">

            <div class="section-heading">

                <div>

                    <h3>
                        Personal Information
                    </h3>

                    <p>
                        Your registered contact details.
                    </p>

                </div>

            </div>


            <div class="profile-info-list">

                <div class="profile-info-row">

                    <span>
                        Employee Code
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $employee["employee_code"]
                        );
                        ?>
                    </strong>

                </div>


                <div class="profile-info-row">

                    <span>
                        Full Name
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $employee["first_name"]
                            . " "
                            . $employee["last_name"]
                        );
                        ?>
                    </strong>

                </div>


                <div class="profile-info-row">

                    <span>
                        Email
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $employee["email"]
                        );
                        ?>
                    </strong>

                </div>


                <div class="profile-info-row">

                    <span>
                        Phone
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $employee["phone"]
                            ?: "Not Provided"
                        );
                        ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- Employment Information -->

        <div class="dashboard-section">

            <div class="section-heading">

                <div>

                    <h3>
                        Employment Information
                    </h3>

                    <p>
                        Your current employment details.
                    </p>

                </div>

            </div>


            <div class="profile-info-list">

                <div class="profile-info-row">

                    <span>
                        Position
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $employee["position"]
                            ?: "Not Assigned"
                        );
                        ?>
                    </strong>

                </div>


                <div class="profile-info-row">

                    <span>
                        Hourly Rate
                    </span>

                    <strong>

                        RM
                        <?php
                        echo number_format(
                            (float) $employee["hourly_rate"],
                            2
                        );
                        ?>
                        / hour

                    </strong>

                </div>


                <div class="profile-info-row">

                    <span>
                        Employment Status
                    </span>

                    <strong>

                        <span
                            class="status status-<?php
                            echo htmlspecialchars(
                                $statusClass
                            );
                            ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $employee["employment_status"]
                            );
                            ?>

                        </span>

                    </strong>

                </div>


                <div class="profile-info-row">

                    <span>
                        Employee Record Created
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            date(
                                "d F Y",
                                strtotime(
                                    $employee["created_at"]
                                )
                            )
                        );
                        ?>

                    </strong>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         READ-ONLY NOTICE
         ===================================================== -->

    <div class="info-box">

        <strong>
            Profile information is read-only.
        </strong>

        <p>
            Contact the administrator if any of your employee information
            needs to be updated.
        </p>

    </div>

</main>

</div>


</body>

</html>