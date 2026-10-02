<?php

session_start();

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        $query = "
            SELECT
                u.user_id,
                u.employee_id,
                u.username,
                u.password,
                u.role,
                e.employment_status

            FROM users u

            LEFT JOIN employees e
                ON u.employee_id = e.employee_id

            WHERE u.username = :username

            LIMIT 1
        ";

        $statement = $pdo->prepare($query);

        $statement->execute([
            ":username" => $username
        ]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {

            /*
            |--------------------------------------------------------------------------
            | Block inactive Employee accounts
            |--------------------------------------------------------------------------
            */

            if (
                $user["role"] === "Employee"
                &&
                $user["employment_status"] !== "Active"
            ) {

                $error =
                    "Your employee account is inactive. Please contact the administrator.";

            } else {

                session_regenerate_id(true);

                $_SESSION["user_id"] =
                    $user["user_id"];

                $_SESSION["employee_id"] =
                    $user["employee_id"];

                $_SESSION["username"] =
                    $user["username"];

                $_SESSION["role"] =
                    $user["role"];


                if ($user["role"] === "Admin") {

                    header(
                        "Location: ../admin/dashboard.php"
                    );

                    exit;

                } elseif ($user["role"] === "Employee") {

                    header(
                        "Location: ../employee/dashboard.php"
                    );

                    exit;
                }
            }

        } else {

            $error =
                "Invalid username or password.";
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
        The Hive HR - Login
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body class="login-page">

    <main class="login-wrapper">

        <div class="login-container">


            <!-- =================================================
                 BRANDING
                 ================================================= -->

            <div class="login-brand">

                <div class="login-brand-mark">
                    H
                </div>

                <h1>
                    The Hive HR
                </h1>

                <p>
                    Employee Management System
                </p>

            </div>


            <!-- =================================================
                 LOGIN CARD
                 ================================================= -->

            <div class="login-card">

                <div class="login-heading">

                    <h2>
                        Welcome Back
                    </h2>

                    <p>
                        Sign in to access your account.
                    </p>

                </div>


                <?php if ($error !== ""): ?>

                    <div
                        class="message message-error login-error"
                        role="alert"
                    >

                        <?php
                        echo htmlspecialchars(
                            $error
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    class="login-form"
                >


                    <div class="form-group">

                        <label for="username">
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?php
                            echo htmlspecialchars(
                                $username ?? ""
                            );
                            ?>"
                            autocomplete="username"
                            required
                            autofocus
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary login-button"
                    >
                        Login
                    </button>

                </form>


                <div class="login-footer">

                    <p>
                        Authorized users only.
                    </p>

                </div>

            </div>

        </div>

    </main>

</body>

</html>