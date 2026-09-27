<?php

require_once(__DIR__ . "/app.php");
require_once(__DIR__ . "/session.php");

/**
 * Generate a cryptographically secure hexadecimal token.
 *
 * @param int $bytes Number of random bytes before hex encoding.
 * @return string|false
 */
function infinitia_secure_random_hex($bytes)
{
    if(function_exists("random_bytes")){

        return bin2hex(random_bytes($bytes));

    }

    if(function_exists("openssl_random_pseudo_bytes")){

        $strong = false;
        $random = openssl_random_pseudo_bytes($bytes, $strong);

        if($random !== false && strlen($random) === $bytes){

            return bin2hex($random);

        }

        error_log("Remember token random generation failed: openssl returned invalid length.");

    }

    error_log("Remember token random generation failed: no compatible secure generator.");
    return false;
}

function infinitia_hash_equals($known, $user)
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

if(!defined("APP_ENV")){

    $app_host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "";

    if($app_host == "localhost"
        || strpos($app_host, "localhost:") === 0
        || $app_host == "127.0.0.1"
        || strpos($app_host, "127.0.0.1:") === 0){

        define("APP_ENV", "development");

    }else{

        define("APP_ENV", "production");

    }

}

if(!defined("APP_BASE_URL")){

    define("APP_BASE_URL", "");

}

function infinitia_is_development()
{
    return defined("APP_ENV") && APP_ENV == "development";
}

function infinitia_csrf_token($key)
{
    if(!isset($_SESSION[$key]) || $_SESSION[$key] == ""){

        $_SESSION[$key] = infinitia_secure_random_hex(32);

    }

    return $_SESSION[$key];
}

function infinitia_verify_csrf_token($key, $token)
{
    if(!isset($_SESSION[$key]) || $token == ""){

        return false;

    }

    return infinitia_hash_equals($_SESSION[$key], $token);
}

function infinitia_invalidate_csrf_token($key)
{
    if(isset($_SESSION[$key])){
        unset($_SESSION[$key]);
    }
}

function infinitia_rotate_csrf_token($key)
{
    infinitia_invalidate_csrf_token($key);
    return infinitia_csrf_token($key);
}

function infinitia_consume_csrf_token($key, $token)
{
    if(!infinitia_verify_csrf_token($key, $token)){
        return false;
    }

    infinitia_invalidate_csrf_token($key);
    return true;
}

function infinitia_delete_expired_password_reset_tokens($conn)
{
    $sql = "DELETE FROM password_reset_tokens WHERE expires_at < NOW() OR used = 1";
    $stmt = mysqli_prepare($conn, $sql);

    if($stmt){

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

    }else{

        error_log("Password reset cleanup prepare error: " . mysqli_error($conn));

    }
}

function infinitia_delete_user_password_reset_tokens($conn, $user_id)
{
    $sql = "DELETE FROM password_reset_tokens WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        error_log("Password reset delete user prepare error: " . mysqli_error($conn));
        return false;

    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $ok = mysqli_stmt_execute($stmt);

    if(!$ok){

        error_log("Password reset delete user execute error: " . mysqli_stmt_error($stmt));

    }

    mysqli_stmt_close($stmt);
    return $ok;
}

function infinitia_recent_password_reset_token_exists($conn, $user_id)
{
    $token_id = 0;
    $sql = "
    SELECT id
    FROM password_reset_tokens
    WHERE user_id = ?
    AND used = 0
    AND expires_at > NOW()
    AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        error_log("Password reset recent token prepare error: " . mysqli_error($conn));
        return false;

    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $token_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$token_id > 0;
}

function infinitia_create_password_reset_token($conn, $user_id)
{
    if(infinitia_recent_password_reset_token_exists($conn, $user_id)){

        return "limited";

    }

    $selector = infinitia_secure_random_hex(16);
    $validator = infinitia_secure_random_hex(32);

    if($selector === false || $validator === false || strlen($selector) !== 32 || strlen($validator) !== 64){

        error_log("Password reset token generation failed.");
        return false;

    }

    $validator_hash = hash("sha256", $validator);

    if(strlen($validator_hash) !== 64){

        error_log("Password reset validator hash length invalid.");
        return false;

    }

    $expires = time() + (30 * 60);
    $expires_at = date("Y-m-d H:i:s", $expires);

    if(function_exists("mysqli_begin_transaction")){

        mysqli_begin_transaction($conn);

    }else{

        mysqli_autocommit($conn, false);

    }

    $sqlDelete = "DELETE FROM password_reset_tokens WHERE user_id = ?";
    $stmtDelete = mysqli_prepare($conn, $sqlDelete);

    if(!$stmtDelete){

        error_log("Password reset token delete prepare error: " . mysqli_error($conn));
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        return false;

    }

    mysqli_stmt_bind_param($stmtDelete, "i", $user_id);

    if(!mysqli_stmt_execute($stmtDelete)){

        error_log("Password reset token delete execute error: " . mysqli_stmt_error($stmtDelete));
        mysqli_stmt_close($stmtDelete);
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        return false;

    }

    mysqli_stmt_close($stmtDelete);

    $sql = "
    INSERT INTO password_reset_tokens(
        user_id,
        selector,
        validator_hash,
        expires_at,
        used
    )
    VALUES(?, ?, ?, ?, 0)
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        error_log("Password reset token insert prepare error: " . mysqli_error($conn));
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        return false;

    }

    mysqli_stmt_bind_param($stmt, "isss", $user_id, $selector, $validator_hash, $expires_at);
    $ok = mysqli_stmt_execute($stmt);

    if(!$ok){

        error_log("Password reset token insert execute error: " . mysqli_stmt_error($stmt));

    }

    mysqli_stmt_close($stmt);

    if(!$ok){

        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        return false;

    }

    mysqli_commit($conn);
    mysqli_autocommit($conn, true);

    return array(
        "selector" => $selector,
        "validator" => $validator,
        "expires_at" => $expires_at,
        "expires" => $expires
    );
}

function infinitia_build_reset_url($selector, $validator)
{
    $url = app_absolute_url("reinitialiser-mot-de-passe");

    if($url === ""){
        error_log("Password reset URL creation failed: application origin is not configured.");
        return "";
    }

    return $url . "?" . http_build_query(
        array(
            "selector" => $selector,
            "validator" => $validator
        )
    );
}

function infinitia_send_password_reset_email($email, $name, $reset_link)
{
    if(!file_exists(dirname(__DIR__) . "/vendor/phpmailer/phpmailer/src/PHPMailer.php")){

        error_log("Password reset email skipped: PHPMailer is not available.");
        return false;

    }

    require_once(dirname(__DIR__) . "/vendor/phpmailer/phpmailer/src/Exception.php");
    require_once(dirname(__DIR__) . "/vendor/phpmailer/phpmailer/src/PHPMailer.php");
    require_once(dirname(__DIR__) . "/vendor/phpmailer/phpmailer/src/SMTP.php");

    $mail_config_file = dirname(__DIR__) . "/config/mail.php";

    if(!file_exists($mail_config_file)){

        error_log("Password reset email skipped: mail config missing.");
        return false;

    }

    $config = require($mail_config_file);

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try{

        $mail->isSMTP();
        $mail->Host = $config["host"];
        $mail->SMTPAuth = true;
        $mail->Username = $config["username"];
        $mail->Password = $config["password"];
        $mail->SMTPSecure = $config["secure"];
        $mail->Port = (int)$config["port"];
        $mail->CharSet = "UTF-8";
        $mail->setFrom($config["from_email"], $config["from_name"]);
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = "Reinitialisation de votre mot de passe - INFINITIA Care Services";
        $safe_name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
        $safe_link = htmlspecialchars($reset_link, ENT_QUOTES, "UTF-8");

        $mail->Body = "
            <p>Bonjour " . $safe_name . ",</p>
            <p>Vous avez demande la reinitialisation de votre mot de passe INFINITIA Care Services.</p>
            <p><a href=\"" . $safe_link . "\" style=\"display:inline-block;padding:12px 18px;background:#081f78;color:#ffffff;text-decoration:none;border-radius:6px;\">Reinitialiser mon mot de passe</a></p>
            <p>Ce lien expire dans 30 minutes.</p>
            <p>Si vous n'etes pas a l'origine de cette demande, vous pouvez ignorer ce message.</p>
        ";

        $mail->AltBody = "Bonjour " . $name . ",\n\n"
            . "Vous avez demande la reinitialisation de votre mot de passe INFINITIA Care Services.\n"
            . "Lien: " . $reset_link . "\n\n"
            . "Ce lien expire dans 30 minutes.\n"
            . "Si vous n'etes pas a l'origine de cette demande, vous pouvez ignorer ce message.";

        return $mail->send();

    }catch(Exception $e){

        error_log("Password reset email send error: " . $mail->ErrorInfo);
        return false;

    }
}

function infinitia_validate_password_strength($password)
{
    if(strlen($password) < 8){

        return "Le mot de passe doit contenir au moins 8 caracteres.";

    }

    if(strlen($password) > 128){

        return "Le mot de passe ne doit pas depasser 128 caracteres.";

    }

    if(!preg_match("/[A-Z]/", $password)){

        return "Le mot de passe doit contenir au moins une lettre majuscule.";

    }

    if(!preg_match("/[a-z]/", $password)){

        return "Le mot de passe doit contenir au moins une lettre minuscule.";

    }

    if(!preg_match("/[0-9]/", $password)){

        return "Le mot de passe doit contenir au moins un chiffre.";

    }

    return "";
}

function infinitia_cookie_secure()
{
    return infinitia_is_https_request();
}

function infinitia_set_remember_cookie($selector, $validator, $expires)
{
    if(PHP_VERSION_ID >= 70300){
        return setcookie(
            "infinitia_remember",
            $selector . ":" . $validator,
            array(
                "expires" => $expires,
                "path" => infinitia_cookie_path(),
                "domain" => "",
                "secure" => infinitia_cookie_secure(),
                "httponly" => true,
                "samesite" => "Lax"
            )
        );
    }

    return setcookie(
        "infinitia_remember",
        $selector . ":" . $validator,
        $expires,
        infinitia_legacy_samesite_cookie_path(),
        "",
        infinitia_cookie_secure(),
        true
    );
}

function infinitia_delete_remember_cookie()
{
    if(PHP_VERSION_ID >= 70300){
        return setcookie(
            "infinitia_remember",
            "",
            array(
                "expires" => time() - 3600,
                "path" => infinitia_cookie_path(),
                "domain" => "",
                "secure" => infinitia_cookie_secure(),
                "httponly" => true,
                "samesite" => "Lax"
            )
        );
    }

    return setcookie(
        "infinitia_remember",
        "",
        time() - 3600,
        infinitia_legacy_samesite_cookie_path(),
        "",
        infinitia_cookie_secure(),
        true
    );
}

function infinitia_login_identifier_hash($value)
{
    return hash("sha256", strtolower(trim((string)$value)));
}

function infinitia_login_ip_hash()
{
    $ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "unknown";
    return hash("sha256", $ip);
}

function infinitia_cleanup_login_attempts($conn)
{
    $sql = "DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){
        error_log("Login attempt cleanup prepare error: " . mysqli_error($conn));
        return false;
    }

    $ok = mysqli_stmt_execute($stmt);

    if(!$ok){
        error_log("Login attempt cleanup execute error: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    return $ok;
}

function infinitia_login_rate_limit_status($conn, $email)
{
    infinitia_cleanup_login_attempts($conn);

    $email_hash = infinitia_login_identifier_hash($email);
    $ip_hash = infinitia_login_ip_hash();
    $pair_count = 0;
    $ip_count = 0;
    $sql = "
    SELECT
        SUM(CASE WHEN email_hash = ? AND ip_hash = ? THEN 1 ELSE 0 END),
        SUM(CASE WHEN ip_hash = ? THEN 1 ELSE 0 END)
    FROM login_attempts
    WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    AND ip_hash = ?
    ";
    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){
        error_log("Login rate limit prepare error: " . mysqli_error($conn));
        return array("limited" => false, "pair_count" => 0, "ip_count" => 0);
    }

    mysqli_stmt_bind_param($stmt, "ssss", $email_hash, $ip_hash, $ip_hash, $ip_hash);

    if(!mysqli_stmt_execute($stmt)){
        error_log("Login rate limit execute error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return array("limited" => false, "pair_count" => 0, "ip_count" => 0);
    }

    mysqli_stmt_bind_result($stmt, $pair_count, $ip_count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $pair_count = (int)$pair_count;
    $ip_count = (int)$ip_count;

    return array(
        "limited" => $pair_count >= 5 || $ip_count >= 30,
        "pair_count" => $pair_count,
        "ip_count" => $ip_count
    );
}

function infinitia_record_failed_login($conn, $email)
{
    $email_hash = infinitia_login_identifier_hash($email);
    $ip_hash = infinitia_login_ip_hash();
    $sql = "INSERT INTO login_attempts(email_hash, ip_hash, attempted_at) VALUES(?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){
        error_log("Login attempt insert prepare error: " . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ss", $email_hash, $ip_hash);
    $ok = mysqli_stmt_execute($stmt);

    if(!$ok){
        error_log("Login attempt insert execute error: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    return $ok;
}

function infinitia_clear_successful_login_attempts($conn, $email)
{
    $email_hash = infinitia_login_identifier_hash($email);
    $ip_hash = infinitia_login_ip_hash();
    $sql = "DELETE FROM login_attempts WHERE email_hash = ? AND ip_hash = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){
        error_log("Login attempt delete prepare error: " . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ss", $email_hash, $ip_hash);
    $ok = mysqli_stmt_execute($stmt);

    if(!$ok){
        error_log("Login attempt delete execute error: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    return $ok;
}

function infinitia_validate_profile_image_upload($file, $allowed_mime_types, $max_size)
{
    if(!is_array($file) || !isset($file["error"]) || (int)$file["error"] !== UPLOAD_ERR_OK){
        return array("valid" => false, "error" => "Le televersement de la photo a echoue.");
    }

    $temporary_path = isset($file["tmp_name"]) ? $file["tmp_name"] : "";
    $file_size = isset($file["size"]) ? (int)$file["size"] : 0;

    if($temporary_path === "" || !is_file($temporary_path)){
        return array("valid" => false, "error" => "Le fichier photo est invalide.");
    }

    if($file_size <= 0 || $file_size > (int)$max_size){
        return array("valid" => false, "error" => "La photo ne doit pas depasser 5 Mo.");
    }

    $image_info = @getimagesize($temporary_path);

    if($image_info === false || !isset($image_info[0]) || !isset($image_info[1]) ||
       (int)$image_info[0] <= 0 || (int)$image_info[1] <= 0 ||
       ((int)$image_info[0] * (int)$image_info[1]) > 25000000){
        return array("valid" => false, "error" => "Le contenu de la photo est invalide.");
    }

    $image_mime = isset($image_info["mime"]) ? strtolower($image_info["mime"]) : "";
    $detected_mime = $image_mime;

    if(function_exists("finfo_open")){
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if($finfo){
            $finfo_mime = strtolower((string)finfo_file($finfo, $temporary_path));
            finfo_close($finfo);

            if($finfo_mime !== ""){
                if($image_mime !== "" && $finfo_mime !== $image_mime){
                    return array("valid" => false, "error" => "Le contenu de la photo est invalide.");
                }

                $detected_mime = $finfo_mime;
            }
        }
    }

    $mime_extensions = array(
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/gif" => "gif"
    );

    if(!in_array($detected_mime, $allowed_mime_types, true) || !isset($mime_extensions[$detected_mime])){
        return array("valid" => false, "error" => "Le format de la photo n'est pas autorise.");
    }

    return array(
        "valid" => true,
        "mime" => $detected_mime,
        "extension" => $mime_extensions[$detected_mime]
    );
}

function infinitia_delete_expired_tokens($conn)
{
    $sql = "DELETE FROM remember_tokens WHERE expires_at < NOW()";
    $stmt = mysqli_prepare($conn, $sql);

    if($stmt){

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

    }else{

        error_log("Remember token expired cleanup prepare error: " . mysqli_error($conn));

    }
}

function infinitia_delete_user_tokens($conn, $user_id)
{
    $sql = "DELETE FROM remember_tokens WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if($stmt){

        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if(!mysqli_stmt_execute($stmt)){

            error_log("Remember token delete user execute error: " . mysqli_stmt_error($stmt));

        }
        mysqli_stmt_close($stmt);

    }else{

        error_log("Remember token delete user prepare error: " . mysqli_error($conn));

    }
}

function infinitia_delete_token_by_selector($conn, $selector)
{
    $sql = "DELETE FROM remember_tokens WHERE selector = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if($stmt){

        mysqli_stmt_bind_param($stmt, "s", $selector);
        if(!mysqli_stmt_execute($stmt)){

            error_log("Remember token delete selector execute error: " . mysqli_stmt_error($stmt));

        }
        mysqli_stmt_close($stmt);

    }else{

        error_log("Remember token delete selector prepare error: " . mysqli_error($conn));

    }
}

function infinitia_parse_remember_cookie()
{
    if(!isset($_COOKIE["infinitia_remember"])){

        return false;

    }

    $parts = explode(":", $_COOKIE["infinitia_remember"]);

    if(count($parts) != 2 || trim($parts[0]) == "" || trim($parts[1]) == ""){

        return false;

    }

    return array(
        "selector" => trim($parts[0]),
        "validator" => trim($parts[1])
    );
}

function infinitia_create_remember_token($conn, $user_id)
{
    $selector = infinitia_secure_random_hex(16);
    $validator = infinitia_secure_random_hex(32);

    if($selector === false || $validator === false){

        error_log("Remember token create failed: selector or validator generation returned false.");
        return false;

    }

    $validator_hash = hash("sha256", $validator);
    $expires = time() + (30 * 24 * 60 * 60);
    $expires_at = date("Y-m-d H:i:s", $expires);

    mysqli_autocommit($conn, false);

    $sqlDelete = "DELETE FROM remember_tokens WHERE user_id = ?";
    $stmtDelete = mysqli_prepare($conn, $sqlDelete);

    if(!$stmtDelete){

        error_log("Remember token create delete prepare error: " . mysqli_error($conn));
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        return false;

    }

    mysqli_stmt_bind_param($stmtDelete, "i", $user_id);

    if(!mysqli_stmt_execute($stmtDelete)){

        error_log("Remember token create delete execute error: " . mysqli_stmt_error($stmtDelete));
        mysqli_stmt_close($stmtDelete);
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        return false;

    }

    mysqli_stmt_close($stmtDelete);

    $sql = "
    INSERT INTO remember_tokens(
        user_id,
        selector,
        validator_hash,
        expires_at,
        created_at
    )
    VALUES(?, ?, ?, ?, NOW())
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        error_log("Remember token insert prepare error: " . mysqli_error($conn));
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        return false;

    }

    mysqli_stmt_bind_param(
        $stmt,
        "isss",
        $user_id,
        $selector,
        $validator_hash,
        $expires_at
    );

    $ok = mysqli_stmt_execute($stmt);

    if(!$ok){

        error_log("Remember token insert execute error: " . mysqli_stmt_error($stmt));

    }

    mysqli_stmt_close($stmt);

    if(!$ok){

        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        return false;

    }

    mysqli_commit($conn);
    mysqli_autocommit($conn, true);

    if(!infinitia_set_remember_cookie($selector, $validator, $expires)){

        error_log("Remember token cookie creation returned false.");

    }

    return true;
}

function infinitia_apply_user_session($user)
{
    session_regenerate_id(true);

    $_SESSION["user_id"] = (int)$user["id"];
    $_SESSION["role_id"] = (int)$user["role_id"];
    $_SESSION["first_name"] = $user["first_name"];
    $_SESSION["last_name"] = $user["last_name"];
    $_SESSION["email"] = $user["email"];
}

function infinitia_redirect_by_role($role_id)
{
    if($role_id == 1){

        header("Location: " . app_url("admin/tableau-de-bord"));
        exit();

    }

    if($role_id == 2){

        header("Location: " . app_url("client/tableau-de-bord"));
        exit();

    }

    if($role_id == 3){

        header("Location: " . app_url("intervenant/tableau-de-bord"));
        exit();

    }
}

function infinitia_rotate_remember_token($conn, $token_id)
{
    $selector = infinitia_secure_random_hex(16);
    $validator = infinitia_secure_random_hex(32);

    if($selector === false || $validator === false){

        return false;

    }

    $validator_hash = hash("sha256", $validator);
    $expires = time() + (30 * 24 * 60 * 60);
    $expires_at = date("Y-m-d H:i:s", $expires);

    $sql = "
    UPDATE remember_tokens
    SET
        selector = ?,
        validator_hash = ?,
        expires_at = ?,
        created_at = NOW()
    WHERE id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        return false;

    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssi",
        $selector,
        $validator_hash,
        $expires_at,
        $token_id
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if(!$ok){

        return false;

    }

    if(!infinitia_set_remember_cookie($selector, $validator, $expires)){

        error_log("Remember token rotation cookie creation returned false.");

    }

    return true;
}

function infinitia_auto_login($conn)
{
    if(isset($_SESSION["user_id"])){

        return true;

    }

    $cookie = infinitia_parse_remember_cookie();

    if($cookie === false){

        infinitia_delete_remember_cookie();
        return false;

    }

    $selector = $cookie["selector"];
    $validator = $cookie["validator"];

    $sql = "
    SELECT
        rt.id,
        rt.validator_hash,
        rt.expires_at,
        u.id AS user_id,
        u.role_id,
        u.first_name,
        u.last_name,
        u.email,
        u.status
    FROM remember_tokens rt
    INNER JOIN users u
    ON u.id = rt.user_id
    WHERE rt.selector = ?
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        infinitia_delete_remember_cookie();
        return false;

    }

    mysqli_stmt_bind_param($stmt, "s", $selector);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result(
        $stmt,
        $token_id,
        $validator_hash,
        $expires_at,
        $user_id,
        $role_id,
        $first_name,
        $last_name,
        $email,
        $status
    );

    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if(!$found){

        infinitia_delete_remember_cookie();
        return false;

    }

    if(strtotime($expires_at) < time()){

        infinitia_delete_token_by_selector($conn, $selector);
        infinitia_delete_remember_cookie();
        return false;

    }

    if(!infinitia_hash_equals($validator_hash, hash("sha256", $validator))){

        infinitia_delete_token_by_selector($conn, $selector);
        infinitia_delete_remember_cookie();
        return false;

    }

    if($status != "active"){

        infinitia_delete_token_by_selector($conn, $selector);
        infinitia_delete_remember_cookie();
        return false;

    }

    $user = array(
        "id" => $user_id,
        "role_id" => $role_id,
        "first_name" => $first_name,
        "last_name" => $last_name,
        "email" => $email
    );

    infinitia_apply_user_session($user);

    if(!infinitia_rotate_remember_token($conn, $token_id)){

        infinitia_delete_token_by_selector($conn, $selector);
        infinitia_delete_remember_cookie();

    }

    return true;
}

?>
