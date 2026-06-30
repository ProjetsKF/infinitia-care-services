<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail_config = require 'config/mail.php';

if($_SERVER["REQUEST_METHOD"] != "POST")
{
    header("Location: contact.php");
    exit();
}

$name = isset($_POST["name"]) ? trim($_POST["name"]) : "";
$phone = isset($_POST["phone"]) ? trim($_POST["phone"]) : "";
$email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
$subject = isset($_POST["subject"]) ? trim($_POST["subject"]) : "";
$message = isset($_POST["message"]) ? trim($_POST["message"]) : "";

if(empty($name) || empty($phone) || empty($email) || empty($subject) || empty($message))
{
    $_SESSION["contact_error"] = "Veuillez remplir tous les champs.";
    header("Location: contact.php");
    exit();
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL))
{
    $_SESSION["contact_error"] = "Adresse email invalide.";
    header("Location: contact.php");
    exit();
}

$safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safe_phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$safe_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safe_subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$safe_message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

$email_body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;">

    <div style="max-width:700px;margin:30px auto;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 35px rgba(0,0,0,0.12);">

        <div style="background:linear-gradient(135deg,#081f78,#e83e8c,#f9a825);padding:35px;text-align:center;color:#ffffff;">
            <h1 style="margin:0;font-size:28px;">INFINITIA GROUP SARLU</h1>
            <p style="margin:8px 0 0;font-size:15px;">
                Nouveau message reçu depuis le site web
            </p>
        </div>

        <div style="padding:35px;">

            <h2 style="color:#081f78;margin-top:0;">Détails du message</h2>

            <table width="100%" cellpadding="12" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td style="font-weight:bold;color:#081f78;width:180px;border-bottom:1px solid #e5e7eb;">Nom complet</td>
                    <td style="border-bottom:1px solid #e5e7eb;">'.$safe_name.'</td>
                </tr>
                <tr>
                    <td style="font-weight:bold;color:#081f78;border-bottom:1px solid #e5e7eb;">Téléphone</td>
                    <td style="border-bottom:1px solid #e5e7eb;">'.$safe_phone.'</td>
                </tr>
                <tr>
                    <td style="font-weight:bold;color:#081f78;border-bottom:1px solid #e5e7eb;">Email</td>
                    <td style="border-bottom:1px solid #e5e7eb;">'.$safe_email.'</td>
                </tr>
                <tr>
                    <td style="font-weight:bold;color:#081f78;border-bottom:1px solid #e5e7eb;">Sujet</td>
                    <td style="border-bottom:1px solid #e5e7eb;">'.$safe_subject.'</td>
                </tr>
            </table>

            <div style="margin-top:30px;">
                <h3 style="color:#081f78;">Message</h3>
                <div style="background:#f4f6fb;border-left:5px solid #e83e8c;padding:20px;border-radius:12px;color:#333;line-height:1.7;">
                    '.$safe_message.'
                </div>
            </div>

        </div>

        <div style="background:#081f78;color:#ffffff;text-align:center;padding:18px;font-size:13px;">
            Cet e-mail a été envoyé automatiquement depuis le site web INFINITIA GROUP SARLU.
        </div>

    </div>

</body>
</html>
';

try
{
    $mail = new PHPMailer(true);

    /*
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';
    */

    $mail->isSMTP();
    $mail->Host = $mail_config["host"];
    $mail->SMTPAuth = true;
    $mail->Username = $mail_config["username"];
    $mail->Password = $mail_config["password"];
    $mail->SMTPSecure = "ssl";
    $mail->Port = 465;

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->CharSet = "UTF-8";

    $mail->setFrom(
        $mail_config["from_email"],
        $mail_config["from_name"]
    );

    $mail->addAddress(
        $mail_config["to_email"],
        $mail_config["to_name"]
    );

    $mail->addReplyTo(
        $email,
        $name
    );

    $mail->isHTML(true);

    $mail->Subject = "Nouveau message : " . $subject;

    $mail->Body = $email_body;

    $mail->AltBody =
        "Nouveau message depuis le site web\n\n" .
        "Nom : " . $name . "\n" .
        "Téléphone : " . $phone . "\n" .
        "Email : " . $email . "\n" .
        "Sujet : " . $subject . "\n\n" .
        "Message :\n" . $message;

    $mail->send();

    $_SESSION["success"] = "Votre message a été envoyé avec succès.";

    header("Location: contact.php");
    exit();
}
catch(Exception $e)
{
     $_SESSION["contact_error"] =
        "Erreur lors de l'envoi du message : " .
        $e->getMessage();

    header("Location: contact.php");
    exit();
}

?>