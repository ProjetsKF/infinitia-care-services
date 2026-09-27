<?php

require_once(__DIR__ . "/auth.php");

function infinitia_google_config()
{
    return array(
        "client_id" => trim((string)app_config_value("google_client_id", "")),
        "client_secret" => trim((string)app_config_value("google_client_secret", "")),
        "redirect_uri" => trim((string)app_config_value("google_redirect_uri", "")),
        "authorization_endpoint" => "https://accounts.google.com/o/oauth2/v2/auth",
        "token_endpoint" => "https://oauth2.googleapis.com/token",
        "certificates_endpoint" => "https://www.googleapis.com/oauth2/v1/certs"
    );
}

function infinitia_google_config_error($config)
{
    if(!is_array($config)
        || $config["client_id"] === ""
        || $config["client_secret"] === ""
        || $config["redirect_uri"] === ""){
        return "La connexion Google n'est pas encore configuree.";
    }

    $parts = parse_url($config["redirect_uri"]);

    if(!is_array($parts) || !isset($parts["scheme"]) || !isset($parts["host"])){
        return "La configuration Google est invalide.";
    }

    $scheme = strtolower($parts["scheme"]);
    $host = strtolower($parts["host"]);
    $local_host = $host === "localhost" || $host === "127.0.0.1";

    if($scheme !== "https" && !($scheme === "http" && $local_host)){
        return "La Redirect URI Google doit utiliser HTTPS hors environnement local.";
    }

    if(!infinitia_is_development() && $scheme !== "https"){
        return "La connexion Google exige HTTPS en production.";
    }

    return "";
}

function infinitia_google_intent_role($intent)
{
    if($intent === "register_client"){
        return 2;
    }

    if($intent === "register_candidate"){
        return 3;
    }

    if($intent === "login"){
        return 0;
    }

    return -1;
}

function infinitia_google_return_path($intent)
{
    if($intent === "register_client"){
        return "inscription/client";
    }

    if($intent === "register_candidate"){
        return "inscription/intervenant";
    }

    return "login";
}

function infinitia_google_fail_and_redirect($intent, $message)
{
    $_SESSION["error"] = $message;
    header("Location: " . app_url(infinitia_google_return_path($intent)));
    exit();
}

function infinitia_google_base64url_encode($value)
{
    return rtrim(strtr(base64_encode($value), "+/", "-_"), "=");
}

function infinitia_google_base64url_decode($value)
{
    if(!is_string($value) || !preg_match('/^[A-Za-z0-9_-]+$/D', $value)){
        return false;
    }

    $padding = strlen($value) % 4;

    if($padding > 0){
        $value .= str_repeat("=", 4 - $padding);
    }

    return base64_decode(strtr($value, "-_", "+/"), true);
}

function infinitia_google_begin_transaction($intent)
{
    $expected_role = infinitia_google_intent_role($intent);

    if($expected_role < 0){
        return array("success" => false, "error" => "Parcours Google invalide.");
    }

    $config = infinitia_google_config();
    $config_error = infinitia_google_config_error($config);

    if($config_error !== ""){
        return array("success" => false, "error" => $config_error);
    }

    $state = infinitia_secure_random_hex(32);
    $nonce = infinitia_secure_random_hex(32);
    $code_verifier = infinitia_secure_random_hex(64);

    if($state === false || $nonce === false || $code_verifier === false){
        return array("success" => false, "error" => "Impossible de demarrer la connexion Google.");
    }

    $code_challenge = infinitia_google_base64url_encode(hash("sha256", $code_verifier, true));

    $_SESSION["google_oauth_transaction"] = array(
        "state" => $state,
        "nonce" => $nonce,
        "code_verifier" => $code_verifier,
        "intent" => $intent,
        "expected_role" => $expected_role,
        "created_at" => time()
    );

    $params = array(
        "client_id" => $config["client_id"],
        "redirect_uri" => $config["redirect_uri"],
        "response_type" => "code",
        "scope" => "openid email profile",
        "state" => $state,
        "nonce" => $nonce,
        "code_challenge" => $code_challenge,
        "code_challenge_method" => "S256",
        "prompt" => "select_account"
    );

    return array(
        "success" => true,
        "authorization_url" => $config["authorization_endpoint"] . "?" . http_build_query($params, "", "&")
    );
}

function infinitia_google_consume_transaction($state)
{
    $result = array("valid" => false, "error" => "La demande Google est invalide ou a expire.");

    if(!isset($_SESSION["google_oauth_transaction"])
        || !is_array($_SESSION["google_oauth_transaction"])){
        return $result;
    }

    $transaction = $_SESSION["google_oauth_transaction"];

    if(!isset($transaction["state"])
        || !is_string($state)
        || $state === ""
        || !infinitia_hash_equals($transaction["state"], $state)){
        return $result;
    }

    unset($_SESSION["google_oauth_transaction"]);

    if(!isset($transaction["created_at"])
        || (int)$transaction["created_at"] < time() - 600
        || (int)$transaction["created_at"] > time() + 60){
        return $result;
    }

    if(!isset($transaction["intent"])
        || infinitia_google_intent_role($transaction["intent"]) < 0
        || !isset($transaction["nonce"])
        || !isset($transaction["code_verifier"])){
        return $result;
    }

    $transaction["valid"] = true;
    $transaction["error"] = "";
    return $transaction;
}

function infinitia_google_http_request($url, $post_fields)
{
    $url_parts = parse_url($url);

    if(!is_array($url_parts)
        || !isset($url_parts["scheme"])
        || strtolower($url_parts["scheme"]) !== "https"){
        return array("success" => false, "status" => 0, "body" => "");
    }

    if(function_exists("curl_init")){
        $curl = curl_init($url);

        if($curl === false){
            return array("success" => false, "status" => 0, "body" => "");
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept: application/json"));
        curl_setopt($curl, CURLOPT_USERAGENT, "INFINITIA-Care-Services-OIDC/1.0");

        if(is_array($post_fields)){
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_fields, "", "&"));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "Accept: application/json",
                "Content-Type: application/x-www-form-urlencoded"
            ));
        }

        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if($body === false || $curl_error !== "" || $status < 200 || $status >= 300){
            return array("success" => false, "status" => $status, "body" => "");
        }

        return array("success" => true, "status" => $status, "body" => $body);
    }

    if(!extension_loaded("openssl")
        || !function_exists("stream_context_create")
        || !function_exists("file_get_contents")){
        error_log("Google HTTPS request unavailable: neither cURL nor an OpenSSL stream is available.");
        return array("success" => false, "status" => 0, "body" => "");
    }

    $headers = array(
        "Accept: application/json",
        "User-Agent: INFINITIA-Care-Services-OIDC/1.0",
        "Connection: close"
    );
    $http_options = array(
        "method" => "GET",
        "header" => implode("\r\n", $headers),
        "timeout" => 15,
        "ignore_errors" => true,
        "follow_location" => 0,
        "max_redirects" => 0,
        "protocol_version" => 1.1
    );

    if(is_array($post_fields)){
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        $http_options["method"] = "POST";
        $http_options["header"] = implode("\r\n", $headers);
        $http_options["content"] = http_build_query($post_fields, "", "&");
    }

    $ssl_options = array(
        "verify_peer" => true,
        "verify_peer_name" => true,
        "allow_self_signed" => false,
        "SNI_enabled" => true
    );
    $ca_file = trim((string)ini_get("openssl.cafile"));

    if($ca_file === "" || !is_file($ca_file)){
        $ca_file = trim((string)ini_get("curl.cainfo"));
    }

    if(($ca_file === "" || !is_file($ca_file))
        && function_exists("php_ini_loaded_file")
        && function_exists("parse_ini_file")){
        $loaded_ini_file = php_ini_loaded_file();
        $loaded_ini_values = is_string($loaded_ini_file) && is_file($loaded_ini_file)
            ? @parse_ini_file($loaded_ini_file)
            : false;

        if(is_array($loaded_ini_values)
            && isset($loaded_ini_values["curl.cainfo"])
            && is_string($loaded_ini_values["curl.cainfo"])){
            $ca_file = trim($loaded_ini_values["curl.cainfo"]);
        }

        unset($loaded_ini_values);
    }

    if($ca_file !== "" && is_file($ca_file)){
        $ssl_options["cafile"] = $ca_file;
    }else{
        $certificate_locations = function_exists("openssl_get_cert_locations")
            ? openssl_get_cert_locations()
            : array();
        $default_ca_file = isset($certificate_locations["default_cert_file"])
            ? $certificate_locations["default_cert_file"]
            : "";
        $default_ca_path = isset($certificate_locations["default_cert_dir"])
            ? $certificate_locations["default_cert_dir"]
            : "";

        if(is_string($default_ca_file) && is_file($default_ca_file)){
            $ssl_options["cafile"] = $default_ca_file;
        }elseif(is_string($default_ca_path) && is_dir($default_ca_path)){
            $ssl_options["capath"] = $default_ca_path;
        }else{
            error_log("Google HTTPS request unavailable: no trusted CA store is configured.");
            return array("success" => false, "status" => 0, "body" => "");
        }
    }

    $context = stream_context_create(array(
        "http" => $http_options,
        "ssl" => $ssl_options
    ));
    $body = @file_get_contents($url, false, $context);
    $status = 0;
    $response_headers = isset($http_response_header) && is_array($http_response_header)
        ? $http_response_header
        : array();

    foreach($response_headers as $response_header){
        if(preg_match('/^HTTP\/\S+\s+([0-9]{3})\b/i', $response_header, $matches)){
            $status = (int)$matches[1];
        }
    }

    if($body === false || $status < 200 || $status >= 300){
        return array("success" => false, "status" => $status, "body" => "");
    }

    return array("success" => true, "status" => $status, "body" => $body);
}

function infinitia_google_exchange_code($code, $code_verifier, $config)
{
    if(!is_string($code) || $code === "" || strlen($code) > 2048){
        return array("success" => false, "error" => "Code Google manquant ou invalide.");
    }

    $response = infinitia_google_http_request($config["token_endpoint"], array(
        "code" => $code,
        "client_id" => $config["client_id"],
        "client_secret" => $config["client_secret"],
        "redirect_uri" => $config["redirect_uri"],
        "grant_type" => "authorization_code",
        "code_verifier" => $code_verifier
    ));

    if(!$response["success"]){
        error_log("Google token exchange failed with HTTP status " . (int)$response["status"] . ".");
        return array("success" => false, "error" => "La connexion Google n'a pas pu etre finalisee.");
    }

    $payload = json_decode($response["body"], true);

    if(!is_array($payload) || !isset($payload["id_token"]) || !is_string($payload["id_token"])){
        error_log("Google token exchange returned no usable ID token.");
        return array("success" => false, "error" => "La reponse Google est invalide.");
    }

    $id_token = $payload["id_token"];
    unset($payload, $response);
    return array("success" => true, "id_token" => $id_token);
}

function infinitia_google_audience_valid($audience, $client_id)
{
    if(is_string($audience)){
        return infinitia_hash_equals($client_id, $audience);
    }

    if(is_array($audience)){
        foreach($audience as $value){
            if(is_string($value) && infinitia_hash_equals($client_id, $value)){
                return true;
            }
        }
    }

    return false;
}

function infinitia_google_limit_text($value, $limit)
{
    if(!is_string($value)){
        return "";
    }

    $value = trim($value);

    if(function_exists("mb_substr")){
        return mb_substr($value, 0, $limit, "UTF-8");
    }

    return substr($value, 0, $limit);
}

function infinitia_google_identity_from_claims($claims, $expected_nonce, $client_id)
{
    if(!is_array($claims)){
        return array("valid" => false, "error" => "Jeton Google invalide.");
    }

    $now = time();
    $issuer_valid = isset($claims["iss"])
        && ($claims["iss"] === "https://accounts.google.com" || $claims["iss"] === "accounts.google.com");

    if(!$issuer_valid
        || !isset($claims["aud"])
        || !infinitia_google_audience_valid($claims["aud"], $client_id)
        || !isset($claims["exp"])
        || !is_numeric($claims["exp"])
        || (int)$claims["exp"] < $now - 60
        || !isset($claims["iat"])
        || !is_numeric($claims["iat"])
        || (int)$claims["iat"] > $now + 60
        || (int)$claims["iat"] < $now - 7200){
        return array("valid" => false, "error" => "Jeton Google expire ou invalide.");
    }

    if(isset($claims["azp"])
        && (!is_string($claims["azp"]) || !infinitia_hash_equals($client_id, $claims["azp"]))){
        return array("valid" => false, "error" => "Audience Google invalide.");
    }

    if(!isset($claims["nonce"])
        || !is_string($claims["nonce"])
        || !infinitia_hash_equals($expected_nonce, $claims["nonce"])){
        return array("valid" => false, "error" => "Nonce Google invalide.");
    }

    $sub = isset($claims["sub"]) ? (string)$claims["sub"] : "";
    $email = isset($claims["email"]) ? trim((string)$claims["email"]) : "";
    $verified = isset($claims["email_verified"])
        && ($claims["email_verified"] === true || $claims["email_verified"] === 1 || $claims["email_verified"] === "true");

    if(!preg_match('/^[\x21-\x7E]{1,255}$/D', $sub)){
        return array("valid" => false, "error" => "Identifiant Google invalide.");
    }

    if(!$verified || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)){
        return array("valid" => false, "error" => "L'adresse e-mail Google doit etre verifiee.");
    }

    $picture = isset($claims["picture"]) ? trim((string)$claims["picture"]) : "";

    if($picture !== ""){
        $picture_parts = parse_url($picture);

        if(strlen($picture) > 2048
            || !is_array($picture_parts)
            || !isset($picture_parts["scheme"])
            || strtolower($picture_parts["scheme"]) !== "https"){
            $picture = "";
        }
    }

    return array(
        "valid" => true,
        "sub" => $sub,
        "email" => $email,
        "email_verified" => 1,
        "first_name" => infinitia_google_limit_text(isset($claims["given_name"]) ? $claims["given_name"] : "", 100),
        "last_name" => infinitia_google_limit_text(isset($claims["family_name"]) ? $claims["family_name"] : "", 100),
        "name" => infinitia_google_limit_text(isset($claims["name"]) ? $claims["name"] : "", 200),
        "picture" => $picture
    );
}

function infinitia_google_verify_id_token($id_token, $expected_nonce, $client_id)
{
    if(!is_string($id_token) || $id_token === "" || strlen($id_token) > 12000){
        return array("valid" => false, "error" => "Jeton Google invalide.");
    }

    $parts = explode(".", $id_token);

    if(count($parts) !== 3){
        return array("valid" => false, "error" => "Jeton Google invalide.");
    }

    $header_json = infinitia_google_base64url_decode($parts[0]);
    $claims_json = infinitia_google_base64url_decode($parts[1]);
    $signature = infinitia_google_base64url_decode($parts[2]);
    $header = $header_json === false ? NULL : json_decode($header_json, true);
    $claims = $claims_json === false ? NULL : json_decode($claims_json, true);

    if(!is_array($header)
        || !is_array($claims)
        || $signature === false
        || !isset($header["alg"])
        || $header["alg"] !== "RS256"
        || !isset($header["kid"])
        || !is_string($header["kid"])){
        return array("valid" => false, "error" => "Jeton Google invalide.");
    }

    $config = infinitia_google_config();
    $cert_response = infinitia_google_http_request($config["certificates_endpoint"], NULL);

    if(!$cert_response["success"]){
        error_log("Google certificates retrieval failed with HTTP status " . (int)$cert_response["status"] . ".");
        return array("valid" => false, "error" => "Impossible de verifier l'identite Google.");
    }

    $certificates = json_decode($cert_response["body"], true);
    $kid = $header["kid"];

    if(!is_array($certificates)
        || !isset($certificates[$kid])
        || !is_string($certificates[$kid])){
        return array("valid" => false, "error" => "Cle de signature Google inconnue.");
    }

    $public_key = openssl_pkey_get_public($certificates[$kid]);

    if($public_key === false){
        return array("valid" => false, "error" => "Certificat Google invalide.");
    }

    $verified = openssl_verify($parts[0] . "." . $parts[1], $signature, $public_key, OPENSSL_ALGO_SHA256);
    openssl_free_key($public_key);
    unset($id_token, $signature, $certificates, $cert_response);

    if($verified !== 1){
        return array("valid" => false, "error" => "Signature Google invalide.");
    }

    return infinitia_google_identity_from_claims($claims, $expected_nonce, $client_id);
}

function infinitia_google_find_provider_user($conn, $provider_sub)
{
    $user = false;
    $provider = "google";
    $sql = "
    SELECT
        u.id,
        u.role_id,
        u.first_name,
        u.last_name,
        u.email,
        u.status,
        c.verification_status
    FROM user_auth_providers uap
    INNER JOIN users u ON u.id = uap.user_id
    LEFT JOIN candidates c ON c.user_id = u.id
    WHERE uap.provider = ? AND uap.provider_user_id = ?
    LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){
        error_log("Google provider lookup prepare failed.");
        return $user;
    }

    mysqli_stmt_bind_param($stmt, "ss", $provider, $provider_sub);

    if(!mysqli_stmt_execute($stmt)){
        error_log("Google provider lookup execute failed.");
        mysqli_stmt_close($stmt);
        return false;
    }

    mysqli_stmt_bind_result(
        $stmt,
        $user_id,
        $role_id,
        $first_name,
        $last_name,
        $email,
        $status,
        $verification_status
    );

    if(mysqli_stmt_fetch($stmt)){
        $user = array(
            "id" => (int)$user_id,
            "role_id" => (int)$role_id,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "email" => $email,
            "status" => $status,
            "verification_status" => $verification_status
        );
    }

    mysqli_stmt_close($stmt);
    return $user;
}

function infinitia_google_find_email_user($conn, $email)
{
    $user = false;
    $stmt = mysqli_prepare($conn, "SELECT id, role_id, status FROM users WHERE email = ? LIMIT 1");

    if(!$stmt){
        error_log("Google email lookup prepare failed.");
        return $user;
    }

    mysqli_stmt_bind_param($stmt, "s", $email);

    if(!mysqli_stmt_execute($stmt)){
        error_log("Google email lookup execute failed.");
        mysqli_stmt_close($stmt);
        return false;
    }

    mysqli_stmt_bind_result($stmt, $user_id, $role_id, $status);

    if(mysqli_stmt_fetch($stmt)){
        $user = array("id" => (int)$user_id, "role_id" => (int)$role_id, "status" => $status);
    }

    mysqli_stmt_close($stmt);
    return $user;
}

function infinitia_google_existing_user_decision($user, $intent)
{
    if(!is_array($user) || !isset($user["id"])){
        return array("allowed" => false, "message" => "Compte INFINITIA introuvable.");
    }

    $role_id = (int)$user["role_id"];
    $expected_role = infinitia_google_intent_role($intent);

    if($role_id === 1){
        return array("allowed" => false, "message" => "La connexion Google n'est pas autorisee pour les administrateurs.");
    }

    if(!in_array($role_id, array(2, 3), true)){
        return array("allowed" => false, "message" => "Role INFINITIA incompatible avec Google.");
    }

    if($expected_role > 0 && $role_id !== $expected_role){
        return array("allowed" => false, "message" => "Ce compte Google est deja associe a un autre type de compte INFINITIA.");
    }

    if($user["status"] === "suspended"){
        return array("allowed" => false, "message" => "Ce compte INFINITIA est suspendu.");
    }

    if($user["status"] !== "active"){
        if($role_id === 3){
            return array("allowed" => false, "message" => "Votre profil Intervenante est en attente d'activation par l'administrateur.");
        }

        return array("allowed" => false, "message" => "Ce compte INFINITIA n'est pas actif.");
    }

    return array("allowed" => true, "message" => "");
}

function infinitia_google_update_last_login($conn, $user_id)
{
    $stmt = mysqli_prepare($conn, "UPDATE users SET last_login = NOW() WHERE id = ?");

    if($stmt){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function infinitia_google_set_pending_identity($identity, $intent)
{
    $expected_role = infinitia_google_intent_role($intent);
    $token = infinitia_secure_random_hex(32);

    if($expected_role < 0 || $token === false || !is_array($identity) || empty($identity["sub"])){
        return false;
    }

    $_SESSION["google_oauth_pending_identity"] = array(
        "token" => $token,
        "intent" => $intent,
        "expected_role" => $expected_role,
        "identity" => $identity,
        "created_at" => time()
    );

    return true;
}

function infinitia_google_pending_identity()
{
    if(!isset($_SESSION["google_oauth_pending_identity"])
        || !is_array($_SESSION["google_oauth_pending_identity"])){
        return false;
    }

    $pending = $_SESSION["google_oauth_pending_identity"];

    if(!isset($pending["created_at"]) || (int)$pending["created_at"] < time() - 900){
        unset($_SESSION["google_oauth_pending_identity"]);
        return false;
    }

    return $pending;
}

function infinitia_google_pending_registration($intent, $token)
{
    $pending = infinitia_google_pending_identity();

    if($pending === false
        || !isset($pending["intent"])
        || $pending["intent"] !== $intent
        || !isset($pending["token"])
        || !is_string($token)
        || $token === ""
        || !infinitia_hash_equals($pending["token"], $token)){
        return false;
    }

    return $pending;
}

function infinitia_google_update_pending_intent($intent)
{
    $pending = infinitia_google_pending_identity();
    $expected_role = infinitia_google_intent_role($intent);
    $token = infinitia_secure_random_hex(32);

    if($pending === false || $pending["intent"] !== "login" || $expected_role < 2 || $token === false){
        return false;
    }

    $pending["intent"] = $intent;
    $pending["expected_role"] = $expected_role;
    $pending["token"] = $token;
    $pending["created_at"] = time();
    $_SESSION["google_oauth_pending_identity"] = $pending;
    return true;
}

function infinitia_google_clear_pending_identity()
{
    if(isset($_SESSION["google_oauth_pending_identity"])){
        unset($_SESSION["google_oauth_pending_identity"]);
    }
}

function infinitia_google_insert_provider($conn, $user_id, $identity)
{
    $provider = "google";
    $provider_sub = $identity["sub"];
    $provider_email = $identity["email"];
    $verified = 1;
    $avatar_url = $identity["picture"] === "" ? NULL : $identity["picture"];
    $sql = "
    INSERT INTO user_auth_providers(
        user_id,
        provider,
        provider_user_id,
        provider_email,
        provider_email_verified,
        avatar_url
    ) VALUES(?, ?, ?, ?, ?, ?)
    ";
    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){
        error_log("Google provider insert prepare failed.");
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssis",
        $user_id,
        $provider,
        $provider_sub,
        $provider_email,
        $verified,
        $avatar_url
    );
    $ok = mysqli_stmt_execute($stmt);

    if(!$ok){
        error_log("Google provider insert failed with database error code " . (int)mysqli_stmt_errno($stmt) . ".");
    }

    mysqli_stmt_close($stmt);
    return $ok;
}

?>
