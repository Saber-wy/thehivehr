<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../includes/csrf.php";

$error = "";


/*
|--------------------------------------------------------------------------
| Retrieve active employees
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        employee_id,
        employee_code,
        first_name,
        last_name
    FROM employees
    WHERE employment_status = 'Active'
    ORDER BY first_name, last_name
");

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Add evaluation
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

    $employeeId =
        $_POST["employee_id"] ?? "";

    $rating =
        $_POST["rating"] ?? "";

    $remarks =
        trim($_POST["remarks"] ?? "");

    $evaluationDate =
        $_POST["evaluation_date"] ?? "";


    if (
        !is_numeric($employeeId)
        ||
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

        $employeeId = (int) $employeeId;
        $rating = (int) $rating;


        /*
        |--------------------------------------------------------------------------
        | Verify employee
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT employee_id
            FROM employees
            WHERE employee_id = :employee_id
            AND employment_status = 'Active'
            LIMIT 1
        ");

        $stmt->execute([
            ":employee_id" => $employeeId
        ]);

        $employee =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$employee) {

            $error =
                "The selected employee is not available.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Validate evaluation date
            |--------------------------------------------------------------------------
            */

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

                /*
                |--------------------------------------------------------------------------
                | Save evaluation
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO performance
                    (
                        employee_id,
                        rating,
                        remarks,
                        evaluation_date
                    )
                    VALUES
                    (
                        :employee_id,
                        :rating,
                        :remarks,
                        :evaluation_date
                    )
                ");

                $stmt->execute([
                    ":employee_id" => $employeeId,
                    ":rating" => $rating,
                    ":remarks" => $remarks,
                    ":evaluation_date" => $evaluationDate
                ]);


                header(
                    "Location: performance.php?added=1"
                );

                exit;
            }
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
        Add Performance Evaluation - The Hive HR
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
            Add Performance Evaluation
        </h2>

        <p>
            Record an employee performance evaluation.
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
                    Employee *
                </label>

                <select
                    name="employee_id"
                    required
                >

                    <option value="">
                        Select Employee
                    </option>


                    <?php foreach ($employees as $employee): ?>

                        <option
                            value="<?php
                            echo $employee["employee_id"];
                            ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $employee["employee_code"]
                                . " - "
                                . $employee["first_name"]
                                . " "
                                . $employee["last_name"]
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Rating *
                </label>

                <select
                    name="rating"
                    required
                >

                    <option value="">
                        Select Rating
                    </option>

                    <option value="1">
                        1 - Poor
                    </option>

                    <option value="2">
                        2 - Needs Improvement
                    </option>

                    <option value="3">
                        3 - Satisfactory
                    </option>

                    <option value="4">
                        4 - Good
                    </option>

                    <option value="5">
                        5 - Excellent
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Evaluation Date *
                </label>

                <input
                    type="date"
                    name="evaluation_date"
                    value="<?php echo date("Y-m-d"); ?>"
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
                ></textarea>

            </div>


            <button
                type="submit"
                class="btn"
            >
                Save Evaluation
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