<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_POST['input_otp'])) {

    header("Location: payment.php");

    exit();
}

$user_id = $_SESSION['user_id'];

$otp = trim($_POST['input_otp']);

$stmt = $conn->prepare("
    SELECT transaction_otp,
           transaction_otp_expiry
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);

$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

/*
|--------------------------------------------------------------------------
| VALID OTP / TAC
|--------------------------------------------------------------------------
*/

if (
    $user['transaction_otp'] == $otp &&
    $user['transaction_otp_expiry'] > date("Y-m-d H:i:s")
) {

    unset($_SESSION['trx_attempt']);

    if (isset($_SESSION['temp_booking'])) {

        $ticket_id = $_SESSION['temp_booking']['ticket_id'];

        $seats = $_SESSION['temp_booking']['seat'];

        /*
        |--------------------------------------------------------------------------
        | REMOVE HOLDS
        |--------------------------------------------------------------------------
        */

        foreach ($seats as $seat) {

            $delete = $conn->prepare("
                DELETE FROM seat_holds
                WHERE ticket_id = ?
                AND seat_number = ?
            ");

            $delete->bind_param(
                "is",
                $ticket_id,
                $seat
            );

            $delete->execute();
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE BOOKING
        |--------------------------------------------------------------------------
        */

        $seat_string = implode(",", $seats);

        $total_price =
            $_SESSION['temp_booking']['total_to_pay'];

        $insurance = isset($_SESSION['temp_booking']['insurance'])
            ? $_SESSION['temp_booking']['insurance']
            : 'no';

        $insurance = ($insurance === 'yes') ? 'yes' : 'no';

        $payment_method =
            $_SESSION['temp_booking']['payment_method'];

        $gate_no = "Gate " . rand(1, 12);

        $insert = $conn->prepare("
            INSERT INTO bookings
            (
                user_id,
                ticket_id,
                seat_numbers,
                total_price,
                status,
                insurance,
                payment_method,
                gate_no
            )
            VALUES
            (?, ?, ?, ?, 'paid', ?, ?, ?)
        ");

        $insert->bind_param(
            "iisdsss",
            $user_id,
            $ticket_id,
            $seat_string,
            $total_price,
            $insurance,
            $payment_method,
            $gate_no
        );

        $insert->execute();

        $booking_id = $insert->insert_id;

        /*
        |--------------------------------------------------------------------------
        | REDUCE AVAILABLE SEATS
        |--------------------------------------------------------------------------
        */

        $seat_count = count($seats);

        $update_seats = $conn->prepare("
            UPDATE bus_tickets
            SET available_seats = GREATEST(available_seats - ?, 0)
            WHERE id = ?
        ");

        $update_seats->bind_param(
            "ii",
            $seat_count,
            $ticket_id
        );

        $update_seats->execute();

        /*
        |--------------------------------------------------------------------------
        | MARK SCHEDULE UNAVAILABLE IF SOLD OUT
        |--------------------------------------------------------------------------
        */

        $sold_out = $conn->prepare("
            UPDATE bus_tickets
            SET status = 'unavailable'
            WHERE id = ?
            AND available_seats <= 0
        ");

        $sold_out->bind_param(
            "i",
            $ticket_id
        );

        $sold_out->execute();

        /*
        |--------------------------------------------------------------------------
        | GET ROUTE
        |--------------------------------------------------------------------------
        */

        $route_query = $conn->prepare("
            SELECT depart_from,
                   arrive_to
            FROM bus_tickets
            WHERE id = ?
        ");

        $route_query->bind_param(
            "i",
            $ticket_id
        );

        $route_query->execute();

        $route =
            $route_query
            ->get_result()
            ->fetch_assoc();

        /*
        |--------------------------------------------------------------------------
        | GET USER INFO FOR EMAIL
        |--------------------------------------------------------------------------
        */

        $user_query = $conn->prepare("
            SELECT fullname, email, otp_email
            FROM users
            WHERE id = ?
        ");

        $user_query->bind_param("i", $user_id);

        $user_query->execute();

        $user_info = $user_query->get_result()->fetch_assoc();

        $send_to = !empty($user_info['otp_email'])
            ? $user_info['otp_email']
            : $user_info['email'];

        /*
        |--------------------------------------------------------------------------
        | SEND BOOKING CONFIRMATION EMAIL
        |--------------------------------------------------------------------------
        */

        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();

            $mail->Host = 'smtp.gmail.com';

            $mail->SMTPAuth = true;

            $mail->Username = 'joeytimmy01@gmail.com';

            $mail->Password = 'cjiehhsdlpshjtmi';

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

            $mail->Port = 465;

            $mail->setFrom(
                'no-reply@kkkl.com.my',
                'KKKL Booking Confirmation'
            );

            $mail->addAddress($send_to);

            $mail->isHTML(true);

            $mail->Subject = 'KKKL Express Booking Confirmation';

            $mail->Body = "
            <div style='max-width:650px;margin:20px auto;font-family:Arial,sans-serif;border:1px solid #ddd;border-radius:10px;overflow:hidden;background:#ffffff;'>

                <div style='background:#bc0000;color:#ffffff;padding:25px;text-align:center;'>
                    <h1 style='margin:0;font-size:28px;letter-spacing:2px;'>KKKL GROUP</h1>
                    <p style='margin:8px 0 0;'>Booking Confirmation</p>
                </div>

                <div style='padding:30px;'>

                    <h2 style='color:#111;margin-top:0;'>Your Ticket Has Been Confirmed</h2>

                    <p>Hi <strong>" . htmlspecialchars($user_info['fullname']) . "</strong>,</p>

                    <p>Your bus ticket booking has been successfully confirmed.</p>

                    <div style='background:#f8f8f8;border:1px solid #eee;border-radius:8px;padding:20px;margin:25px 0;'>

                        <p><strong>Booking ID:</strong> #" . $booking_id . "</p>

                        <p><strong>Route:</strong> " . htmlspecialchars($route['depart_from']) . " → " . htmlspecialchars($route['arrive_to']) . "</p>

                        <p><strong>Seats:</strong> " . htmlspecialchars($seat_string) . "</p>

                        <p><strong>Boarding Gate:</strong> " . htmlspecialchars($gate_no) . "</p>

                        <p><strong>Total Paid:</strong> RM " . number_format($total_price, 2) . "</p>

                        <p><strong>Payment Method:</strong> " . htmlspecialchars($payment_method) . "</p>

                    </div>

                    <p style='font-size:13px;color:#666;'>
                        Please arrive at the terminal at least 30 minutes before departure.
                    </p>

                    <p style='font-size:13px;color:#666;'>
                        Thank you for choosing KKKL Express.
                    </p>

                </div>

            </div>
            ";

            $mail->send();

        } catch (Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | DO NOT STOP BOOKING IF EMAIL FAILS
            |--------------------------------------------------------------------------
            */
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE SUCCESS SESSION
        |--------------------------------------------------------------------------
        */

        $_SESSION['booking_final'] = [

            'booking_id' => $booking_id,

            'route' =>
                $route['depart_from'] .
                " → " .
                $route['arrive_to'],

            'seats' => $seat_string,

            'total' => $total_price
        ];
    }

    unset($_SESSION['temp_booking']);

    header("Location: booking_success.php");

    exit();

} else {

    /*
    |--------------------------------------------------------------------------
    | INVALID OTP / TAC
    |--------------------------------------------------------------------------
    */

    $_SESSION['trx_attempt']++;

    if ($_SESSION['trx_attempt'] >= 3) {

        unset($_SESSION['trx_attempt']);

        /*
        |--------------------------------------------------------------------------
        | RELEASE HELD SEATS AFTER FAILED PAYMENT
        |--------------------------------------------------------------------------
        */

        if (isset($_SESSION['temp_booking'])) {

            $ticket_id = $_SESSION['temp_booking']['ticket_id'];

            $seats = $_SESSION['temp_booking']['seat'];

            foreach ($seats as $seat) {

                $release = $conn->prepare("
                    DELETE FROM seat_holds
                    WHERE ticket_id = ?
                    AND seat_number = ?
                    AND user_id = ?
                ");

                $release->bind_param(
                    "isi",
                    $ticket_id,
                    $seat,
                    $user_id
                );

                $release->execute();
            }

            unset($_SESSION['temp_booking']);
        }

        echo "
        <script>
            alert('Payment verification failed. Your booking has been cancelled and seats have been released.');
            window.location.href='dashboard.php';
        </script>
        ";

        exit();
    }

    echo "
    <script>
        alert('Invalid TAC. Please try again.');
        window.location.href='secure_verify.php';
    </script>
    ";

    exit();
}
?>