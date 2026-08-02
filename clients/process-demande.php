<?php

session_start();

require_once(dirname(__FILE__) . "/../config/database.php");

function redirect_demande()
{
    header("Location: " . app_url("client/demandes"));
    exit();
}

function csrf_hash_equals($known, $user)
{
    if(function_exists("hash_equals")){
        return hash_equals($known, $user);
    }

    if(strlen($known) != strlen($user)){
        return false;
    }

    $result = 0;
    $i = 0;

    for($i = 0; $i < strlen($known); $i++){
        $result |= ord($known[$i]) ^ ord($user[$i]);
    }

    return $result === 0;
}

function request_safe($value)
{
    if($value === NULL || $value === ""){
        return "Non renseigne";
    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function urgency_label($value)
{
    if($value == "low"){
        return "Faible";
    }

    if($value == "medium"){
        return "Moyenne";
    }

    if($value == "high"){
        return "Elevee";
    }

    return $value;
}

function status_label($value)
{
    if($value == "en_attente"){
        return "En attente";
    }

    return str_replace("_", " ", $value);
}

function format_request_date($value)
{
    $timestamp = strtotime($value);

    if($timestamp === false){
        return $value;
    }

    return date("d/m/Y", $timestamp);
}

function build_mailer()
{
    $base_path = dirname(__FILE__) . "/..";
    $required_files = array(
        $base_path . "/vendor/phpmailer/phpmailer/src/Exception.php",
        $base_path . "/vendor/phpmailer/phpmailer/src/PHPMailer.php",
        $base_path . "/vendor/phpmailer/phpmailer/src/SMTP.php"
    );
    $i = 0;

    for($i = 0; $i < count($required_files); $i++){
        if(!is_file($required_files[$i])){
            throw new Exception("Dépendance e-mail introuvable.");
        }

        require_once($required_files[$i]);
    }

    if(!class_exists("PHPMailer\\PHPMailer\\PHPMailer")){
        throw new Exception("Classe PHPMailer introuvable.");
    }

    $config_file = $base_path . "/config/mail.php";

    if(!is_file($config_file)){
        throw new Exception("Configuration e-mail introuvable.");
    }

    $config = require($config_file);
    $required_keys = array(
        "host",
        "username",
        "password",
        "secure",
        "port",
        "from_email",
        "from_name",
        "to_email",
        "to_name"
    );

    if(!is_array($config)){
        throw new Exception("Configuration e-mail invalide.");
    }

    for($i = 0; $i < count($required_keys); $i++){
        $key = $required_keys[$i];

        if(!isset($config[$key]) || trim((string)$config[$key]) == ""){
            throw new Exception("Configuration e-mail incomplète : " . $key . ".");
        }
    }

    if(!in_array($config["secure"], array("ssl", "tls"), true)
        || ($config["secure"] == "ssl" && (int)$config["port"] != 465)
        || ($config["secure"] == "tls" && (int)$config["port"] != 587)
        || !filter_var($config["from_email"], FILTER_VALIDATE_EMAIL)
        || !filter_var($config["to_email"], FILTER_VALIDATE_EMAIL)){
        throw new Exception("Paramètres e-mail invalides.");
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $config["host"];
    $mail->SMTPAuth = true;
    $mail->Username = $config["username"];
    $mail->Password = $config["password"];
    $mail->SMTPSecure = $config["secure"];
    $mail->Port = (int)$config["port"];
    $mail->Timeout = 5;
    $mail->getSMTPInstance()->Timelimit = 5;
    $mail->SMTPKeepAlive = true;
    $mail->CharSet = "UTF-8";
    $mail->setFrom($config["from_email"], $config["from_name"]);

    return array($mail, $config);
}

function send_request_notifications($request)
{
    $admin_sent = false;
    $client_sent = false;
    $mail = NULL;
    $request_id = (int)$request["request_id"];
    $reference = "DEM-" . str_pad($request_id, 5, "0", STR_PAD_LEFT);

    try{
        $mail_data = build_mailer();
        $mail = $mail_data[0];
        $config = $mail_data[1];

        $admin_email = isset($config["to_email"]) ? $config["to_email"] : $config["from_email"];
        $admin_name = isset($config["to_name"]) ? $config["to_name"] : "Administrateur INFINITIA";

        $mail->addAddress($admin_email, $admin_name);
        $mail->isHTML(true);
        $mail->Subject = "Nouvelle demande de service #" . str_pad($request_id, 5, "0", STR_PAD_LEFT) . " - INFINITIA Care Services";

        $mail->Body = "
            <div style=\"font-family:Arial,sans-serif;color:#263238;line-height:1.5;\">
                <div style=\"background:#081f78;color:#ffffff;padding:18px;border-radius:8px 8px 0 0;\">
                    <h2 style=\"margin:0;\">INFINITIA Care Services</h2>
                    <p style=\"margin:6px 0 0;\">Nouvelle demande de service</p>
                </div>
                <div style=\"border:1px solid #eeeeee;border-top:0;padding:18px;border-radius:0 0 8px 8px;\">
                    <h3 style=\"color:#081f78;margin-top:0;\">" . request_safe($reference) . "</h3>
                    <p><strong>Date de soumission :</strong> " . request_safe(date("d/m/Y H:i")) . "</p>
                    <h4>Client</h4>
                    <p><strong>Nom :</strong> " . request_safe($request["client_name"]) . "<br>
                    <strong>E-mail :</strong> " . request_safe($request["client_email"]) . "<br>
                    <strong>Telephone :</strong> " . request_safe($request["client_phone"]) . "</p>
                    <h4>Details du service</h4>
                    <p><strong>Categorie :</strong> " . request_safe($request["category_name"]) . "<br>
                    <strong>Titre :</strong> " . request_safe($request["title"]) . "<br>
                    <strong>Description :</strong><br>" . nl2br(request_safe($request["description"])) . "</p>
                    <h4>Planification</h4>
                    <p><strong>Lieu :</strong> " . request_safe($request["location"]) . "<br>
                    <strong>Date prevue :</strong> " . request_safe(format_request_date($request["service_date"])) . "<br>
                    <strong>Duree :</strong> " . (int)$request["duration"] . " heure(s)</p>
                    <h4>Budget et urgence</h4>
                    <p><strong>Budget :</strong> " . request_safe(number_format((float)$request["budget"], 2, ",", " ")) . " USD<br>
                    <strong>Urgence :</strong> " . request_safe(urgency_label($request["urgency_level"])) . "<br>
                    <strong>Statut :</strong> " . request_safe(status_label($request["status"])) . "</p>
                    <p style=\"color:#757575;font-size:12px;\">Notification automatique envoyee par INFINITIA Care Services.</p>
                </div>
            </div>";

        $mail->AltBody = "Nouvelle demande de service\n"
            . "Reference : " . $reference . "\n"
            . "Client : " . $request["client_name"] . "\n"
            . "E-mail : " . $request["client_email"] . "\n"
            . "Telephone : " . $request["client_phone"] . "\n"
            . "Categorie : " . $request["category_name"] . "\n"
            . "Titre : " . $request["title"] . "\n"
            . "Description : " . $request["description"] . "\n"
            . "Lieu : " . $request["location"] . "\n"
            . "Date prevue : " . format_request_date($request["service_date"]) . "\n"
            . "Duree : " . (int)$request["duration"] . " heure(s)\n"
            . "Budget : " . number_format((float)$request["budget"], 2, ",", " ") . " USD\n"
            . "Urgence : " . urgency_label($request["urgency_level"]) . "\n"
            . "Statut : " . status_label($request["status"]);

        $admin_sent = $mail->send();

        if(filter_var($request["client_email"], FILTER_VALIDATE_EMAIL)){
            $mail->clearAddresses();
            $mail->clearAttachments();
            $mail->addAddress($request["client_email"], $request["client_name"]);
            $mail->Subject = "Confirmation de votre demande #" . str_pad($request_id, 5, "0", STR_PAD_LEFT) . " - INFINITIA Care Services";
            $mail->Body = "
                <div style=\"font-family:Arial,sans-serif;color:#263238;line-height:1.5;\">
                    <div style=\"background:#081f78;color:#ffffff;padding:18px;border-radius:8px 8px 0 0;\">
                        <h2 style=\"margin:0;\">INFINITIA Care Services</h2>
                        <p style=\"margin:6px 0 0;\">Confirmation de votre demande</p>
                    </div>
                    <div style=\"border:1px solid #eeeeee;border-top:0;padding:18px;border-radius:0 0 8px 8px;\">
                        <p>Bonjour " . request_safe($request["client_name"]) . ",</p>
                        <p>Votre demande a bien ete enregistree. Notre equipe prendra contact avec vous.</p>
                        <p><strong>Reference :</strong> " . request_safe($reference) . "<br>
                        <strong>Categorie :</strong> " . request_safe($request["category_name"]) . "<br>
                        <strong>Titre :</strong> " . request_safe($request["title"]) . "<br>
                        <strong>Date prevue :</strong> " . request_safe(format_request_date($request["service_date"])) . "<br>
                        <strong>Lieu :</strong> " . request_safe($request["location"]) . "<br>
                        <strong>Budget :</strong> " . request_safe(number_format((float)$request["budget"], 2, ",", " ")) . " USD<br>
                        <strong>Urgence :</strong> " . request_safe(urgency_label($request["urgency_level"])) . "</p>
                    </div>
                </div>";
            $mail->AltBody = "Votre demande a bien ete enregistree.\n"
                . "Reference : " . $reference . "\n"
                . "Categorie : " . $request["category_name"] . "\n"
                . "Titre : " . $request["title"] . "\n"
                . "Date prevue : " . format_request_date($request["service_date"]) . "\n"
                . "Lieu : " . $request["location"] . "\n"
                . "Budget : " . number_format((float)$request["budget"], 2, ",", " ") . " USD\n"
                . "Urgence : " . urgency_label($request["urgency_level"]);

            $client_sent = $mail->send();
        }

    }catch(Exception $e){
        error_log(
            "Erreur notification demande #" . $request_id
            . " : " . $e->getMessage()
            . " [" . $e->getFile() . ":" . $e->getLine() . "]"
        );
    }

    if($mail instanceof PHPMailer\PHPMailer\PHPMailer){
        $mail->smtpClose();
    }

    return $admin_sent && ($client_sent || !filter_var($request["client_email"], FILTER_VALIDATE_EMAIL));
}

if($_SERVER["REQUEST_METHOD"] != "POST"){
    redirect_demande();
}

if(!isset($_SESSION["user_id"])){
    $_SESSION["error"] = "Veuillez vous connecter.";
    header("Location: " . app_url("login"));
    exit();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!isset($_SESSION["service_request_csrf"]) || !csrf_hash_equals($_SESSION["service_request_csrf"], $csrf_token)){
    $_SESSION["error"] = "La session du formulaire a expire. Veuillez reessayer.";
    redirect_demande();
}

unset($_SESSION["service_request_csrf"]);

$user_id = (int)$_SESSION["user_id"];
$client_id = 0;
$client_first_name = "";
$client_last_name = "";
$client_email = "";
$client_phone = "";

$sql = "
SELECT
    c.id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone
FROM clients c
INNER JOIN users u
ON u.id = c.user_id
WHERE c.user_id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){
    error_log("Demande client lookup prepare error: " . mysqli_error($conn));
    $_SESSION["error"] = "Une erreur est survenue. Veuillez reessayer.";
    redirect_demande();
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $client_id, $client_first_name, $client_last_name, $client_email, $client_phone);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if((int)$client_id <= 0){
    $_SESSION["error"] = "Profil client introuvable.";
    redirect_demande();
}

$category_id = isset($_POST["category_id"]) ? (int)$_POST["category_id"] : 0;
$title = isset($_POST["title"]) ? trim($_POST["title"]) : "";
$description = isset($_POST["description"]) ? trim($_POST["description"]) : "";
$location = isset($_POST["location"]) ? trim($_POST["location"]) : "";
$service_date = isset($_POST["service_date"]) ? trim($_POST["service_date"]) : "";
$duration = isset($_POST["duration"]) ? (int)$_POST["duration"] : 0;
$budget_raw = isset($_POST["budget"]) ? trim($_POST["budget"]) : "";
$budget = is_numeric($budget_raw) ? (float)$budget_raw : -1;
$urgency_level = isset($_POST["urgency_level"]) ? trim($_POST["urgency_level"]) : "";
$status = "en_attente";

$date_parts = explode("-", $service_date);
$date_valid = count($date_parts) == 3
    && checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0]);

if($category_id <= 0
    || $title == ""
    || $description == ""
    || $location == ""
    || !$date_valid
    || $duration <= 0
    || $budget < 0
    || ($urgency_level != "low" && $urgency_level != "medium" && $urgency_level != "high")){

    $_SESSION["error"] = "Veuillez verifier les informations de votre demande.";
    redirect_demande();
}

$category_name = "";
$category_found_id = 0;

$sql = "
SELECT id, name
FROM service_categories
WHERE id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){
    error_log("Demande category lookup prepare error: " . mysqli_error($conn));
    $_SESSION["error"] = "Une erreur est survenue. Veuillez reessayer.";
    redirect_demande();
}

mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $category_found_id, $category_name);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if((int)$category_found_id <= 0){
    $_SESSION["error"] = "La categorie selectionnee est invalide.";
    redirect_demande();
}

if(function_exists("mysqli_begin_transaction")){
    mysqli_begin_transaction($conn);
}else{
    mysqli_autocommit($conn, false);
}

$sql = "
INSERT INTO service_requests(
    client_id,
    category_id,
    title,
    description,
    location,
    service_date,
    duration,
    budget,
    urgency_level,
    status
)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){
    error_log("Demande insert prepare error: " . mysqli_error($conn));
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);
    $_SESSION["error"] = "Erreur lors de l'enregistrement de votre demande.";
    redirect_demande();
}

mysqli_stmt_bind_param(
    $stmt,
    "iissssidss",
    $client_id,
    $category_id,
    $title,
    $description,
    $location,
    $service_date,
    $duration,
    $budget,
    $urgency_level,
    $status
);

$insert_ok = mysqli_stmt_execute($stmt);

if(!$insert_ok){
    error_log("Demande insert execute error: " . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);

if(!$insert_ok){
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);
    $_SESSION["error"] = "Erreur lors de l'enregistrement de votre demande.";
    redirect_demande();
}

$request_id = mysqli_insert_id($conn);
mysqli_commit($conn);
mysqli_autocommit($conn, true);

$request_data = array(
    "request_id" => $request_id,
    "client_name" => trim($client_first_name . " " . $client_last_name),
    "client_email" => $client_email,
    "client_phone" => $client_phone,
    "category_name" => $category_name,
    "title" => $title,
    "description" => $description,
    "location" => $location,
    "service_date" => $service_date,
    "duration" => $duration,
    "budget" => $budget,
    "urgency_level" => $urgency_level,
    "status" => $status
);

$email_envoye = send_request_notifications($request_data);

if($email_envoye){
    $_SESSION["success"] = "Votre demande a ete enregistree avec succes. Une confirmation vous a ete envoyee par e-mail.";
}else{
    $_SESSION["success"] = "Votre demande a ete enregistree avec succes. La confirmation par e-mail n'a toutefois pas pu etre envoyee.";
}

header("Location: " . app_url("client/demandes"));
exit();

?>
