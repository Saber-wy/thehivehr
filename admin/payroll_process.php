<?php

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../includes/csrf.php";

$error = "";


// Retrieve active employees
$stmt = $pdo->query("
    SELECT
        employee_id,
        employee_code,
        first_name,
        last_name,
        hourly_rate
    FROM employees
    WHERE employment_status = 'Active'
    ORDER BY first_name, last_name
");

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Process payroll
if ($_SERVER["REQUEST_METHOD"] === "POST") {

if (
        !verifyCsrfToken(
            $_POST["csrf_token"] ?? ""
        )
    ) {
        die("Invalid security token.");
    }

    $employeeId = $_POST["employee_id"] ?? "";
    $payrollMonth = $_POST["payroll_month"] ?? "";
    $deductions = $_POST["deductions"] ?? "0";


    if (
        !is_numeric($employeeId)
        ||
        $payrollMonth === ""
    ) {

        $error =
            "Please select an employee and payroll month.";

    } elseif (
        !is_numeric($deductions)
        ||
        (float) $deductions < 0
    ) {

        $error =
            "Deductions must be a valid positive amount.";

    } else {

        $employeeId = (int) $employeeId;
        $deductions = round((float) $deductions, 2);


        /*
        |--------------------------------------------------------------------------
        | Validate payroll month
        |--------------------------------------------------------------------------
        */

        $monthObject =
            DateTime::createFromFormat(
                "Y-m",
                $payrollMonth
            );

        if (
            !$monthObject
            ||
            $monthObject->format("Y-m") !== $payrollMonth
        ) {

            $error = "Invalid payroll month.";

        } else {

            $monthStart =
                $payrollMonth . "-01";

            $monthEndObject =
                clone $monthObject;

            $monthEnd =
                $monthEndObject
                    ->modify("last day of this month")
                    ->format("Y-m-d");


            /*
            |--------------------------------------------------------------------------
            | Retrieve employee
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    employee_id,
                    employee_code,
                    first_name,
                    last_name,
                    hourly_rate,
                    employment_status
                FROM employees
                WHERE employee_id = :employee_id
                LIMIT 1
            ");

            $stmt->execute([
                ":employee_id" => $employeeId
            ]);

            $employee =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (
                !$employee
                ||
                $employee["employment_status"] !== "Active"
            ) {

                $error =
                    "The selected employee is not available for payroll processing.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Check duplicate payroll
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    SELECT payroll_id
                    FROM payroll
                    WHERE employee_id = :employee_id
                    AND payroll_month = :payroll_month
                    LIMIT 1
                ");

                $stmt->execute([
                    ":employee_id" => $employeeId,
                    ":payroll_month" => $monthStart
                ]);

                $existingPayroll =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                if ($existingPayroll) {

                    $error =
                        "Payroll has already been processed for this employee and month.";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Retrieve completed attendance
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare("
                        SELECT
                            attendance_date,
                            total_hours
                        FROM attendance
                        WHERE employee_id = :employee_id
                        AND attendance_date
                            BETWEEN :month_start AND :month_end
                        AND status = 'Present'
                        AND clock_in IS NOT NULL
                        AND clock_out IS NOT NULL
                        ORDER BY attendance_date
                    ");

                    $stmt->execute([
                        ":employee_id" => $employeeId,
                        ":month_start" => $monthStart,
                        ":month_end" => $monthEnd
                    ]);

                    $attendanceRecords =
                        $stmt->fetchAll(PDO::FETCH_ASSOC);


                    if (count($attendanceRecords) === 0) {

                        $error =
                            "No completed attendance records were found for the selected employee and month.";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Calculate regular and overtime hours
                        |--------------------------------------------------------------------------
                        */

                        $regularHours = 0.00;
                        $overtimeHours = 0.00;


                        foreach ($attendanceRecords as $attendance) {

                            $hours =
                                (float) $attendance["total_hours"];

                            if ($hours <= 8) {

                                $regularHours += $hours;

                            } else {

                                $regularHours += 8;

                                $overtimeHours +=
                                    ($hours - 8);
                            }
                        }


                        $regularHours =
                            round($regularHours, 2);

                        $overtimeHours =
                            round($overtimeHours, 2);


                        /*
                        |--------------------------------------------------------------------------
                        | Calculate pay
                        |--------------------------------------------------------------------------
                        */

                        $hourlyRate =
                            (float) $employee["hourly_rate"];

                        $basicPay =
                            round(
                                $regularHours
                                *
                                $hourlyRate,
                                2
                            );

                        $overtimeRate =
                            $hourlyRate * 1.5;

                        $overtimePay =
                            round(
                                $overtimeHours
                                *
                                $overtimeRate,
                                2
                            );


                        $netPay =
                            round(
                                $basicPay
                                +
                                $overtimePay
                                -
                                $deductions,
                                2
                            );


                        if ($netPay < 0) {

                            $error =
                                "Deductions cannot exceed the employee's calculated earnings.";

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | Save payroll
                            |--------------------------------------------------------------------------
                            */

                            try {

                                $stmt = $pdo->prepare("
                                    INSERT INTO payroll
                                    (
                                        employee_id,
                                        payroll_month,
                                        regular_hours,
                                        overtime_hours,
                                        basic_pay,
                                        overtime_pay,
                                        deductions,
                                        net_pay
                                    )
                                    VALUES
                                    (
                                        :employee_id,
                                        :payroll_month,
                                        :regular_hours,
                                        :overtime_hours,
                                        :basic_pay,
                                        :overtime_pay,
                                        :deductions,
                                        :net_pay
                                    )
                                ");

                                $stmt->execute([
                                    ":employee_id" => $employeeId,
                                    ":payroll_month" => $monthStart,
                                    ":regular_hours" => $regularHours,
                                    ":overtime_hours" => $overtimeHours,
                                    ":basic_pay" => $basicPay,
                                    ":overtime_pay" => $overtimePay,
                                    ":deductions" => $deductions,
                                    ":net_pay" => $netPay
                                ]);


                                header(
                                    "Location: payroll.php?processed=1"
                                );

                                exit;


                            } catch (PDOException $e) {

                                if ($e->getCode() === "23000") {

                                    $error =
                                        "Payroll already exists for this employee and month.";

                                } else {

                                    $error =
                                        "Unable to save the payroll record.";
                                }
                            }
                        }
                    }
                }
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
        Process Payroll - The Hive HR
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>


<header class="topbar">

    <h1>The Hive HR</h1>

    <a href="payroll.php">
        Back to Payroll
    </a>

</header>


<main class="content">

    <div class="page-title">

        <h2>Process Payroll</h2>

        <p>
            Calculate payroll from completed attendance records.
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
                                . " (RM"
                                . number_format(
                                    (float) $employee["hourly_rate"],
                                    2
                                )
                                . "/hour)"
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Payroll Month *
                </label>

                <input
                    type="month"
                    name="payroll_month"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Deductions (RM)
                </label>

                <input
                    type="number"
                    name="deductions"
                    min="0"
                    step="0.01"
                    value="0.00"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn"
            >
                Calculate and Process Payroll
            </button>

        </form>

    </div>

</main>

</body>

</html>