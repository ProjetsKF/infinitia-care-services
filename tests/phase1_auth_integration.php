<?php

if(PHP_SAPI !== "cli"){
    exit("CLI only.\n");
}

$_SERVER["HTTP_HOST"] = "127.0.0.1:8080";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";

require_once(dirname(__DIR__) . "/config/auth.php");
require_once(dirname(__DIR__) . "/config/database.php");

$base_url = "http://127.0.0.1:8080/infinitia-group-sarlu";
$suffix = date("YmdHis") . mt_rand(1000, 9999);
$password = "PhaseTest9A";
$new_password = "PhaseReset8B";
$test_emails = array(
    "client" => "codex-phase1-client-" . $suffix . "@example.test",
    "candidate" => "codex-phase1-candidate-" . $suffix . "@example.test",
    "admin" => "codex-phase1-admin-" . $suffix . "@example.test",
    "rollback" => "codex-phase1-rollback-" . $suffix . "@example.test",
    "csrf_client" => "codex-phase1-csrf-client-" . $suffix . "@example.test",
    "csrf_candidate" => "codex-phase1-csrf-candidate-" . $suffix . "@example.test",
    "limited" => "codex-phase1-limited-" . $suffix . "@example.test",
    "unknown" => "unknown-" . $suffix . "@example.test"
);
$test_phones = array(
    "client" => "099" . substr($suffix, -7),
    "candidate" => "098" . substr($suffix, -7),
    "rollback" => "097" . substr($suffix, -7),
    "csrf_client" => "096" . substr($suffix, -7),
    "csrf_candidate" => "095" . substr($suffix, -7)
);
$tests = array();
$cookie_files = array();

function test_result(&$tests, $name, $condition, $details)
{
    $tests[] = array("name" => $name, "ok" => $condition ? true : false, "details" => $details);
    echo ($condition ? "PASS" : "FAIL") . " - " . $name . " - " . $details . PHP_EOL;
    flush();
}

function new_cookie_file(&$cookie_files)
{
    $file = tempnam(sys_get_temp_dir(), "infinitia_auth_");
    $cookie_files[] = $file;
    return $file;
}

function http_call($base_url, $path, $cookie_file, $post_data)
{
    $curl = curl_init($base_url . $path);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 8);
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);

    if(is_array($post_data)){
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
    }

    $body = curl_exec($curl);
    $error = curl_error($curl);
    $result = array(
        "body" => $body === false ? "" : $body,
        "url" => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL),
        "status" => (int)curl_getinfo($curl, CURLINFO_HTTP_CODE),
        "error" => $error
    );
    curl_close($curl);
    return $result;
}

function csrf_from_html($html)
{
    if(preg_match_all('/name=["\']csrf_token["\'][^>]*value=["\']([^"\']+)["\']/i', $html, $matches)
        && isset($matches[1])
        && count($matches[1]) > 0){
        return html_entity_decode($matches[1][count($matches[1]) - 1], ENT_QUOTES, "UTF-8");
    }

    return "";
}

function logout_csrf_from_html($html)
{
    if(preg_match('/<form[^>]*action=["\'][^"\']*deconnexion[^"\']*["\'][^>]*>(.*?)<\/form>/is', $html, $form)){
        return csrf_from_html($form[1]);
    }

    return "";
}

function scalar_query($conn, $sql, $value)
{
    $result = 0;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $result);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return (int)$result;
}

function user_id_by_email($conn, $email)
{
    return scalar_query($conn, "SELECT id FROM users WHERE email = ? LIMIT 1", $email);
}

function cleanup_test_data($conn, $test_emails, $cookie_files)
{
    foreach($test_emails as $email){
        $user_id = user_id_by_email($conn, $email);

        if($user_id > 0){
            foreach(array("clients", "candidates", "remember_tokens", "password_reset_tokens") as $table){
                $sql = "DELETE FROM " . $table . " WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $email_hash = infinitia_login_identifier_hash($email);
        $stmt = mysqli_prepare($conn, "DELETE FROM login_attempts WHERE email_hash = ?");
        mysqli_stmt_bind_param($stmt, "s", $email_hash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    foreach($cookie_files as $file){
        if(is_file($file)){
            unlink($file);
        }
    }
}

function client_registration_data($email, $phone, $password)
{
    return array(
        "first_name" => "Test",
        "last_name" => "Client",
        "email" => $email,
        "phone" => $phone,
        "password" => $password,
        "confirm_password" => $password,
        "client_type" => "individual",
        "company_name" => "",
        "address" => "Adresse test",
        "city" => "Lubumbashi",
        "gps_location" => ""
    );
}

function candidate_registration_data($email, $phone, $password)
{
    return array(
        "first_name" => "Test",
        "last_name" => "Candidate",
        "email" => $email,
        "phone" => $phone,
        "password" => $password,
        "confirm_password" => $password,
        "birth_date" => "1990-01-01",
        "gender" => "Femme",
        "address" => "Adresse test",
        "city" => "Lubumbashi",
        "nationality" => "Congolaise",
        "marital_status" => "Celibataire",
        "education_level" => "Secondaire",
        "experience_years" => "2",
        "bio" => "Profil de test automatise",
        "emergency_contact" => "0990000000",
        "photo_consent" => "0",
        "terms_accepted" => "1"
    );
}

function login_request($base_url, $email, $password, $remember, &$cookie_files)
{
    $cookie = new_cookie_file($cookie_files);
    $page = http_call($base_url, "/login", $cookie, NULL);
    $token = csrf_from_html($page["body"]);
    $result = http_call($base_url, "/login", $cookie, array(
        "csrf_token" => $token,
        "email" => $email,
        "password" => $password,
        "remember_me" => $remember ? "1" : ""
    ));
    $result["cookie"] = $cookie;
    return $result;
}

cleanup_test_data($conn, $test_emails, $cookie_files);

try{
    $cookie = new_cookie_file($cookie_files);
    $page = http_call($base_url, "/inscription/client", $cookie, NULL);
    $token = csrf_from_html($page["body"]);
    $data = client_registration_data($test_emails["client"], $test_phones["client"], $password);
    $data["csrf_token"] = $token;
    http_call($base_url, "/inscription/client/traiter", $cookie, $data);
    $client_user_id = user_id_by_email($conn, $test_emails["client"]);
    $client_profile_count = scalar_query($conn, "SELECT COUNT(*) FROM clients c INNER JOIN users u ON u.id = c.user_id WHERE u.email = ?", $test_emails["client"]);
    test_result($tests, "Inscription Client normale", $client_user_id > 0 && $client_profile_count === 1, "users et clients crees");

    $cookie = new_cookie_file($cookie_files);
    $page = http_call($base_url, "/inscription/client", $cookie, NULL);
    $data = client_registration_data($test_emails["client"], "094" . substr($suffix, -7), $password);
    $data["csrf_token"] = csrf_from_html($page["body"]);
    $response = http_call($base_url, "/inscription/client/traiter", $cookie, $data);
    $duplicate_count = scalar_query($conn, "SELECT COUNT(*) FROM users WHERE email = ?", $test_emails["client"]);
    test_result($tests, "Inscription Client email existant", $duplicate_count === 1 && strpos($response["body"], "existe deja") !== false, "aucun doublon cree");

    $cookie = new_cookie_file($cookie_files);
    $data = client_registration_data($test_emails["csrf_client"], $test_phones["csrf_client"], $password);
    $data["csrf_token"] = "invalid";
    $response = http_call($base_url, "/inscription/client/traiter", $cookie, $data);
    test_result($tests, "Inscription Client CSRF invalide", user_id_by_email($conn, $test_emails["csrf_client"]) === 0 && strpos($response["body"], "expire") !== false, "requete rejetee avant insertion");

    $cookie = new_cookie_file($cookie_files);
    $page = http_call($base_url, "/inscription/client", $cookie, NULL);
    $data = client_registration_data($test_emails["rollback"], $test_phones["rollback"], $password);
    $data["client_type"] = "company";
    $data["company_name"] = str_repeat("X", 300);
    $data["csrf_token"] = csrf_from_html($page["body"]);
    http_call($base_url, "/inscription/client/traiter", $cookie, $data);
    test_result($tests, "Rollback Client sur echec du profil", user_id_by_email($conn, $test_emails["rollback"]) === 0, "aucun users orphelin");

    $cookie = new_cookie_file($cookie_files);
    $page = http_call($base_url, "/inscription/intervenant", $cookie, NULL);
    $token = csrf_from_html($page["body"]);
    $data = candidate_registration_data($test_emails["candidate"], $test_phones["candidate"], $password);
    $data["csrf_token"] = $token;
    http_call($base_url, "/inscription/intervenant/traiter", $cookie, $data);
    $candidate_user_id = user_id_by_email($conn, $test_emails["candidate"]);
    $candidate_state = "";
    $stmt = mysqli_prepare($conn, "SELECT CONCAT(u.status, ':', c.verification_status) FROM users u INNER JOIN candidates c ON c.user_id = u.id WHERE u.email = ?");
    mysqli_stmt_bind_param($stmt, "s", $test_emails["candidate"]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $candidate_state);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    test_result($tests, "Inscription Intervenante transaction et statuts", $candidate_user_id > 0 && $candidate_state === "inactive:en_attente", $candidate_state);

    $cookie = new_cookie_file($cookie_files);
    $page = http_call($base_url, "/inscription/intervenant", $cookie, NULL);
    $data = candidate_registration_data($test_emails["candidate"], "093" . substr($suffix, -7), $password);
    $data["csrf_token"] = csrf_from_html($page["body"]);
    $response = http_call($base_url, "/inscription/intervenant/traiter", $cookie, $data);
    test_result($tests, "Inscription Intervenante email existant", scalar_query($conn, "SELECT COUNT(*) FROM users WHERE email = ?", $test_emails["candidate"]) === 1 && strpos($response["body"], "existe deja") !== false, "aucun doublon cree");

    $cookie = new_cookie_file($cookie_files);
    $data = candidate_registration_data($test_emails["csrf_candidate"], $test_phones["csrf_candidate"], $password);
    $data["csrf_token"] = "invalid";
    $response = http_call($base_url, "/inscription/intervenant/traiter", $cookie, $data);
    test_result($tests, "Inscription Intervenante CSRF invalide", user_id_by_email($conn, $test_emails["csrf_candidate"]) === 0 && strpos($response["body"], "expire") !== false, "requete rejetee avant insertion");

    $login = login_request($base_url, $test_emails["client"], $password, false, $cookie_files);
    test_result($tests, "Connexion Client et redirection", strpos($login["url"], "/client/tableau-de-bord") !== false, $login["url"]);

    $login = login_request($base_url, $test_emails["candidate"], $password, false, $cookie_files);
    test_result($tests, "Connexion Intervenante inactive", strpos($login["body"], "attente d&#039;activation") !== false || strpos($login["body"], "attente d'activation") !== false, "dashboard refuse");

    $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'active' WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $test_emails["candidate"]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $login = login_request($base_url, $test_emails["candidate"], $password, false, $cookie_files);
    test_result($tests, "Connexion Intervenante active et redirection", strpos($login["url"], "/intervenant/tableau-de-bord") !== false, $login["url"]);

    $bad_email_login = login_request($base_url, $test_emails["unknown"], "WrongPassword9", false, $cookie_files);
    $bad_password_login = login_request($base_url, $test_emails["client"], "WrongPassword9", false, $cookie_files);
    $generic_message = "Adresse email ou mot de passe incorrect.";
    test_result($tests, "Connexion sans enumeration", strpos($bad_email_login["body"], $generic_message) !== false && strpos($bad_password_login["body"], $generic_message) !== false, "meme message pour email et mot de passe");

    $limited_body = "";
    for($attempt = 1; $attempt <= 6; $attempt++){
        $rate_login = login_request($base_url, $test_emails["limited"], "WrongPassword9", false, $cookie_files);
        $limited_body = $rate_login["body"];
    }
    test_result($tests, "Limitation des tentatives", strpos($limited_body, "Trop de tentatives") !== false, "5 echecs autorises puis blocage temporaire");

    $admin_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users(role_id, first_name, last_name, email, phone, password, status) VALUES(1, 'Test', 'Admin', ?, NULL, ?, 'active')");
    mysqli_stmt_bind_param($stmt, "ss", $test_emails["admin"], $admin_hash);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    foreach(array(
        array("Client", $test_emails["client"], "/client/tableau-de-bord", true),
        array("Intervenante", $test_emails["candidate"], "/intervenant/tableau-de-bord", false),
        array("Admin", $test_emails["admin"], "/admin/tableau-de-bord", false)
    ) as $logout_case){
        $login = login_request($base_url, $logout_case[1], $password, $logout_case[3], $cookie_files);
        $token = logout_csrf_from_html($login["body"]);
        $invalid_logout = http_call($base_url, "/deconnexion", $login["cookie"], array("csrf_token" => "invalid"));
        $still_connected = http_call($base_url, $logout_case[2], $login["cookie"], NULL);
        $token = logout_csrf_from_html($invalid_logout["body"]);
        $logout = http_call($base_url, "/deconnexion", $login["cookie"], array("csrf_token" => $token));
        $protected = http_call($base_url, $logout_case[2], $login["cookie"], NULL);
        $remember_count = scalar_query($conn, "SELECT COUNT(*) FROM remember_tokens rt INNER JOIN users u ON u.id = rt.user_id WHERE u.email = ?", $logout_case[1]);
        test_result($tests, "Deconnexion " . $logout_case[0] . " POST/CSRF", $token != "" && strpos($still_connected["url"], $logout_case[2]) !== false && strpos($logout["url"], "/login") !== false && strpos($protected["url"], "/login") !== false && $remember_count === 0, "CSRF invalide refuse, session et remember supprimes avec token valide");
    }

    infinitia_delete_user_password_reset_tokens($conn, $client_user_id);
    $expired_token = infinitia_create_password_reset_token($conn, $client_user_id);
    $stmt = mysqli_prepare($conn, "UPDATE password_reset_tokens SET expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE selector = ?");
    mysqli_stmt_bind_param($stmt, "s", $expired_token["selector"]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $cookie = new_cookie_file($cookie_files);
    $expired_page = http_call($base_url, "/reinitialiser-mot-de-passe?selector=" . $expired_token["selector"] . "&validator=" . $expired_token["validator"], $cookie, NULL);
    test_result($tests, "Expiration du lien de reinitialisation", strpos($expired_page["body"], "invalide ou a expire") !== false, "jeton expire refuse");

    infinitia_delete_user_password_reset_tokens($conn, $client_user_id);
    $reset_token = infinitia_create_password_reset_token($conn, $client_user_id);
    $reset_url = infinitia_build_reset_url($reset_token["selector"], $reset_token["validator"]);
    test_result($tests, "Construction sure du lien de reinitialisation", strpos($reset_url, $base_url . "/reinitialiser-mot-de-passe") === 0, $reset_url);

    $remember_selector = infinitia_secure_random_hex(16);
    $remember_validator = infinitia_secure_random_hex(32);
    $remember_hash = hash("sha256", $remember_validator);
    $remember_expiry = date("Y-m-d H:i:s", time() + 3600);
    $stmt = mysqli_prepare($conn, "INSERT INTO remember_tokens(user_id, selector, validator_hash, expires_at) VALUES(?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $client_user_id, $remember_selector, $remember_hash, $remember_expiry);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $cookie = new_cookie_file($cookie_files);
    $reset_path = "/reinitialiser-mot-de-passe?selector=" . $reset_token["selector"] . "&validator=" . $reset_token["validator"];
    $reset_page = http_call($base_url, $reset_path, $cookie, NULL);
    $reset_csrf = csrf_from_html($reset_page["body"]);
    $reset_result = http_call($base_url, $reset_path, $cookie, array(
        "csrf_token" => $reset_csrf,
        "password" => $new_password,
        "password_confirmation" => $new_password
    ));
    $stored_password = "";
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $client_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $stored_password);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    $remember_count = scalar_query($conn, "SELECT COUNT(*) FROM remember_tokens rt INNER JOIN users u ON u.id = rt.user_id WHERE u.email = ?", $test_emails["client"]);
    test_result($tests, "Reinitialisation et invalidation des sessions persistantes", strpos($reset_result["url"], "/login") !== false && password_verify($new_password, $stored_password) && $remember_count === 0, "mot de passe remplace et remember supprime");

}catch(Exception $exception){
    test_result($tests, "Execution de la suite", false, $exception->getMessage());
}

$failed = 0;

foreach($tests as $test){
    if(!$test["ok"]){
        $failed++;
    }
}

cleanup_test_data($conn, $test_emails, $cookie_files);
echo "RESULT: " . (count($tests) - $failed) . "/" . count($tests) . " tests passed" . PHP_EOL;
exit($failed === 0 ? 0 : 1);

?>
