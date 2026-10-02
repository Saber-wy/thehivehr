<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../includes/csrf.php";

$error = "";


if (
    !isset($_GET["id"])
    ||
    !is_numeric($_GET["id"])
) {

    header("Location: performance.php");
    exit;
}


$performanceId =
    (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Retrieve evaluation
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        pf.performance_id,
        pf.employee_id,
        pf.rating,
        pf.remarks,
        pf.evaluation_date,

        e.employee_code,
        e.first_name,
        e.last_name

    FROM performance pf

    INNER JOIN employees e
        ON pf.employee_id = e.employee_id

    WHERE pf.performance_id = :performance_id

    LIMIT 1
");

$stmt->execute([
    ":performance_id" => $performanceId
]);

$performance =
    $stmt->fetch(PDO::FETCH_ASSOC);


if (!$performance) {
    die("Performance evaluation not found.");
}


/*
|--------------------------------------------------------------------------
| Update evaluation
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

if (
    !verifyCsrfToken(
        $_POST["csrf_token"] ?? ""
    )
) {
    die("Invalid security token.");
}

    $rating =
        $_POST["rating"] ?? "";

    $remarks =
        trim($_POST["remarks"] ?? "");

    $evaluationDate =
        $_POST["evaluation_date"] ?? "";


    if (
        !is_numeric($rating)
        ||
        $remarks === ""
        ||
        $evaluationDate === ""
    ) {

        $error =
            "Please complete all required fields.";

    } elseif (
        (int) $rating < 1
        ||
        (int) $rating > 5
    ) {

        $error =
            "Performance rating must be between 1 and 5.";

    } else {

        $rating = (int) $rating;


        $dateObject =
            DateTime::createFromFormat(
                "Y-m-d",
                $evaluationDate
            );


        if (
            !$dateObject
            ||
            $dateObject->format("Y-m-d")
                !== $evaluationDate
        ) {

            $error =
                "Please enter a valid evaluation date.";

        } else {

            $stmt = $pdo->prepare("
                UPDATE performance

                SET
                    rating = :rating,
                    remarks = :remarks,
                    evaluation_date = :evaluation_date

                WHERE performance_id = :performance_id
            ");

            $stmt->execute([
                ":rating" => $rating,
                ":remarks" => $remarks,
                ":evaluation_date" => $evaluationDate,
                ":performance_id" => $performanceId
            ]);


            header(
                "Location: performance.php?updated=1"
            );

            exit;
        }
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
        Edit Performance Evaluation - The Hive HR
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

        <a href="performance.php">
            Back to Performance
        </a>

    </div>

</header>


<main class="content">

    <div class="page-title">

        <h2>
            Edit Performance Evaluation
        </h2>

        <p>

            Employee:

            <strong>

                <?php
                echo htmlspecialchars(
                    $performance["employee_code"]
                    . " - "
                    . $performance["first_name"]
                    . " "
                    . $performance["last_name"]
                );
                ?>

            </strong>

        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="message">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <div class="form-container">

        <form method="POST">

        <input
    type="hidden"
    name="csrf_token"
    value="<?php
        echo htmlspecialchars(
            generateCsrfToken()
        );
    ?>"
>

            <div class="form-group">

                <label>
                    Rating *
                </label>

                <select
                    name="rating"
                    required
                >

                    <?php for ($rating = 1; $rating <= 5; $rating++): ?>

                        <option
                            value="<?php echo $rating; ?>"

                            <?php
                            if (
                                (int) $performance["rating"]
                                === $rating
                            ) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php echo $rating; ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Evaluation Date *
                </label>

                <input
                    type="date"
                    name="evaluation_date"
                    value="<?php
                    echo htmlspecialchars(
                        $performance["evaluation_date"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Remarks *
                </label>

                <textarea
                    name="remarks"
                    rows="6"
                    required
                ><?php
                echo htmlspecialchars(
                    $performance["remarks"]
                );
                ?></textarea>

            </div>


            <button
                type="submit"
                class="btn"
            >
                Update Evaluation
            </button>

            <a
                href="performance.php"
                class="btn btn-secondary"
            >
                Cancel
            </a>

        </form>

    </div>

</main>


</body>

</html>