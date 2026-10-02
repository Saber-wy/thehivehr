<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../includes/csrf.php";

$error = "";
$success = "";


// Validate employee ID
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


// Check existing account
$stmt = $pdo->prepare("
    SELECT user_id, username
    FROM users
    WHERE employee_id = :employee_id
    AND role = 'Employee'
");

$stmt->execute([
    ":employee_id" => $employeeId
]);

$existingAccount = $stmt->fetch(PDO::FETCH_ASSOC);


// Create account
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && !$existingAccount
) {

if (
    !verifyCsrfToken(
        $_POST["csrf_token"] ?? ""
    )
) {
    die("Invalid security token.");
}

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";


    if ($username === "" || $password === "") {

        $error = "Username and password are required.";

    } elseif (strlen($password) < 6) {

        $error =
            "Password must contain at least 6 characters.";

    } elseif ($password !== $confirmPassword) {

        $error =
            "Password confirmation does not match.";

    } else {

        try {

            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $stmt = $pdo->prepare("
                INSERT INTO users
                (
                    employee_id,
                    username,
                    password,
                    role
                )
                VALUES
                (
                    :employee_id,
                    :username,
                    :password,
                    'Employee'
                )
            ");

            $stmt->execute([
                ":employee_id" => $employeeId,
                ":username" => $username,
                ":password" => $hashedPassword
            ]);

            $success =
                "Employee login account created successfully.";

            // Refresh account information
            $stmt = $pdo->prepare("
                SELECT user_id, username
                FROM users
                WHERE employee_id = :employee_id
                AND role = 'Employee'
            ");

            $stmt->execute([
                ":employee_id" => $employeeId
            ]);

            $existingAccount =
                $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {

                $error =
                    "The selected username already exists.";

            } else {

                $error =
                    "Unable to create employee account.";
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
        Employee Account - The Hive HR
    </title>

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

        <h2>
            Employee Login Account
        </h2>

        <p>
            <?php
            echo htmlspecialchars(
                $employee["employee_code"]
                . " - "
                . $employee["first_name"]
                . " "
                . $employee["last_name"]
            );
            ?>
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="message">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <?php if ($success !== ""): ?>

        <div class="message">

            <?php echo htmlspecialchars($success); ?>

        </div>

    <?php endif; ?>


    <?php if ($existingAccount): ?>

        <div class="card">

            <h3>
                Account Already Exists
            </h3>

            <br>

            <p>
                Username:
                <strong>
                    <?php
                    echo htmlspecialchars(
                        $existingAccount["username"]
                    );
                    ?>
                </strong>
            </p>

        </div>

    <?php else: ?>

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
                        Username *
                    </label>

                    <input
                        type="text"
                        name="username"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Password *
                    </label>

                    <input
                        type="password"
                        name="password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Confirm Password *
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn"
                >
                    Create Employee Account
                </button>

            </form>

        </div>

    <?php endif; ?>

</main>

</body>

</html>