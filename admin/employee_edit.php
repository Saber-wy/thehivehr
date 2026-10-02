<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../includes/csrf.php";

$error = "";


// Check employee ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: employees.php");
    exit;
}

$employeeId = (int) $_GET["id"];


// Retrieve employee
$stmt = $pdo->prepare("
    SELECT *
    FROM employees
    WHERE employee_id = :employee_id
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

$employee = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$employee) {
    header("Location: employees.php");
    exit;
}


// Process update
if ($_SERVER["REQUEST_METHOD"] === "POST") {

if (
    !verifyCsrfToken(
        $_POST["csrf_token"] ?? ""
    )
) {
    die("Invalid security token.");
}

    $employeeCode = trim($_POST["employee_code"] ?? "");
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $position = trim($_POST["position"] ?? "");
    $hourlyRate = trim($_POST["hourly_rate"] ?? "");


    if (
        $employeeCode === "" ||
        $firstName === "" ||
        $lastName === "" ||
        $email === "" ||
        $hourlyRate === ""
    ) {

        $error = "Please complete all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (!is_numeric($hourlyRate) || $hourlyRate < 0) {

        $error = "Hourly rate must be a valid positive number.";

    } else {

        try {

            $sql = "
                UPDATE employees
                SET
                    employee_code = :employee_code,
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    position = :position,
                    hourly_rate = :hourly_rate
                WHERE employee_id = :employee_id
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":employee_code" => $employeeCode,
                ":first_name" => $firstName,
                ":last_name" => $lastName,
                ":email" => $email,
                ":phone" => $phone,
                ":position" => $position,
                ":hourly_rate" => $hourlyRate,
                ":employee_id" => $employeeId
            ]);

            header("Location: employees.php?updated=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $error = "Employee code or email already exists.";
            } else {
                $error = "Unable to update employee record.";
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

    <title>Edit Employee - The Hive HR</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<header class="topbar">

    <h1>The Hive HR</h1>

    <a href="employees.php">
        Back to Employees
    </a>

</header>


<main class="content">

    <div class="page-title">

        <h2>Edit Employee</h2>

        <p>
            Update employee information.
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="message">
            <?php echo htmlspecialchars($error); ?>
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

                <label>Employee Code *</label>

                <input
                    type="text"
                    name="employee_code"
                    value="<?php
                    echo htmlspecialchars(
                        $employee["employee_code"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>First Name *</label>

                <input
                    type="text"
                    name="first_name"
                    value="<?php
                    echo htmlspecialchars(
                        $employee["first_name"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Last Name *</label>

                <input
                    type="text"
                    name="last_name"
                    value="<?php
                    echo htmlspecialchars(
                        $employee["last_name"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Email *</label>

                <input
                    type="email"
                    name="email"
                    value="<?php
                    echo htmlspecialchars(
                        $employee["email"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Phone</label>

                <input
                    type="text"
                    name="phone"
                    value="<?php
                    echo htmlspecialchars(
                        $employee["phone"] ?? ""
                    );
                    ?>"
                >

            </div>


            <div class="form-group">

                <label>Position</label>

                <input
                    type="text"
                    name="position"
                    value="<?php
                    echo htmlspecialchars(
                        $employee["position"] ?? ""
                    );
                    ?>"
                >

            </div>


            <div class="form-group">

                <label>Hourly Rate (RM) *</label>

                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="hourly_rate"
                    value="<?php
                    echo htmlspecialchars(
                        $employee["hourly_rate"]
                    );
                    ?>"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn"
            >
                Update Employee
            </button>

        </form>

    </div>

</main>

</body>

</html>