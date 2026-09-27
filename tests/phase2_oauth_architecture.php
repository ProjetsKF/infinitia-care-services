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
$password = "OAuthArchitecture9A";
$emails = array(
    "local_client" => "codex-oauth-local-client-" . $suffix . "@example.test",
    "local_candidate" => "codex-oauth-local-candidate-" . $suffix . "@example.test",
    "social" => "codex-oauth-social-" . $suffix . "@example.test",
    "other" => "codex-oauth-other-" . $suffix . "@example.test"
);
$cookies = array();
$results = array();
$baseline_passwords = array();

function phase2_result(&$results, $name, $condition, $details)
{
    $results[] = array("name" => $name, "ok" => $condition ? true : false);
    echo ($condition ? "PASS" : "FAIL") . " - " . $name . " - " . $details . PHP_EOL;
}

function phase2_http_call($base_url, $path, $cookie_file, $post_data)
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
    $result = array(
        "body" => $body === false ? "" : $body,
        "url" => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL),
        "status" => (int)curl_getinfo($curl, CURLINFO_HTTP_CODE),
        "error" => curl_error($curl)
    );
    curl_close($curl);
    return $result;
}

function phase2_csrf_from_html($html)
{
    if(preg_match_all('/name=["\']csrf_token["\'][^>]*value=["\']([^"\']+)["\']/i', $html, $matches)
        && isset($matches[1])
        && count($matches[1]) > 0){
        return html_entity_decode($matches[1][count($matches[1]) - 1], ENT_QUOTES, "UTF-8");
    }

    return "";
}

function phase2_cookie(&$cookies)
{
    $file = tempnam(sys_get_temp_dir(), "infinitia_oauth_");
    $cookies[] = $file;
    return $file;
}

function phase2_login($base_url, $email, $password, &$cookies)
{
    $cookie = phase2_cookie($cookies);
    $page = phase2_http_call($base_url, "/login", $cookie, NULL);
    $token = phase2_csrf_from_html($page["body"]);

    return phase2_http_call($base_url, "/login", $cookie, array(
        "csrf_token" => $token,
        "email" => $email,
        "password" => $password
    ));
}

function phase2_insert_user($conn, $role_id, $email, $password)
{
    $status = "active";
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users(role_id, first_name, last_name, email, phone, password, status) VALUES(?, 'OAuth', 'Test', ?, NULL, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "isss", $role_id, $email, $password, $status);

    if(!mysqli_stmt_execute($stmt)){
        mysqli_stmt_close($stmt);
        return 0;
    }

    $user_id = (int)mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $user_id;
}

function phase2_count($conn, $sql, $value)
{
    $count = 0;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return (int)$count;
}

function phase2_cleanup($conn, $emails, $cookies)
{
    foreach($emails as $email){
        $user_id = 0;
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $user_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if((int)$user_id > 0){
            foreach(array("clients", "candidates") as $table){
                $stmt = mysqli_prepare($conn, "DELETE FROM " . $table . " WHERE user_id = ?");
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

    foreach($cookies as $file){
        if(is_file($file)){
            unlink($file);
        }
    }
}

$snapshot = mysqli_query($conn, "SELECT id, password FROM users ORDER BY id");
while($row = mysqli_fetch_assoc($snapshot)){
    $baseline_passwords[(int)$row["id"]] = $row["password"];
}
mysqli_free_result($snapshot);

try{
    $local_hash = password_hash($password, PASSWORD_DEFAULT);
    $client_id = phase2_insert_user($conn, 2, $emails["local_client"], $local_hash);
    $candidate_id = phase2_insert_user($conn, 3, $emails["local_candidate"], $local_hash);
    $social_id = phase2_insert_user($conn, 2, $emails["social"], NULL);
    $other_id = phase2_insert_user($conn, 2, $emails["other"], NULL);

    $stmt = mysqli_prepare($conn, "INSERT INTO clients(user_id, client_type) VALUES(?, 'individual')");
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "INSERT INTO candidates(user_id, verification_status) VALUES(?, 'verifie')");
    mysqli_stmt_bind_param($stmt, "i", $candidate_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $client_login = phase2_login($base_url, $emails["local_client"], $password, $cookies);
    phase2_result(
        $results,
        "TEST 2 - Connexion locale avec hash existant",
        strpos($client_login["url"], "/client/tableau-de-bord") !== false,
        "redirection Client conservee"
    );

    $stored_password = "not-null";
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $social_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $stored_password);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    phase2_result($results, "TEST 3 - Stockage password NULL", $stored_password === NULL, "NULL lu sans conversion");

    $social_login = phase2_login($base_url, $emails["social"], $password, $cookies);
    $generic_login = strpos($social_login["body"], "Adresse email ou mot de passe incorrect.") !== false;
    $no_php_error = stripos($social_login["body"], "warning") === false && stripos($social_login["body"], "fatal error") === false;

    $forgot_cookie = phase2_cookie($cookies);
    $forgot_page = phase2_http_call($base_url, "/mot-de-passe-oublie", $forgot_cookie, NULL);
    $forgot_result = phase2_http_call($base_url, "/mot-de-passe-oublie", $forgot_cookie, array(
        "csrf_token" => phase2_csrf_from_html($forgot_page["body"]),
        "email" => $emails["social"]
    ));
    $reset_count = phase2_count($conn, "SELECT COUNT(*) FROM password_reset_tokens WHERE user_id = ?", $social_id);
    $generic_forgot = strpos($forgot_result["body"], "Si cette adresse e-mail correspond a un compte actif") !== false;

    phase2_result(
        $results,
        "TEST 4 - Refus local et oubli generique pour password NULL",
        $generic_login && $no_php_error && $generic_forgot && $reset_count === 0,
        "aucun warning, aucune enumeration, aucun jeton"
    );

    $provider = "google";
    $provider_id = "google-sub-" . $suffix;
    $provider_email = $emails["social"];
    $verified = 1;
    $stmt = mysqli_prepare($conn, "INSERT INTO user_auth_providers(user_id, provider, provider_user_id, provider_email, provider_email_verified) VALUES(?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isssi", $social_id, $provider, $provider_id, $provider_email, $verified);
    $google_ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    phase2_result($results, "TEST 5 - Identite Google fictive", $google_ok, "ligne fournisseur creee");

    $stmt = mysqli_prepare($conn, "INSERT INTO user_auth_providers(user_id, provider, provider_user_id) VALUES(?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $other_id, $provider, $provider_id);
    $duplicate_identity_rejected = !mysqli_stmt_execute($stmt) && mysqli_stmt_errno($stmt) === 1062;
    mysqli_stmt_close($stmt);
    phase2_result($results, "TEST 6 - Identite fournisseur unique", $duplicate_identity_rejected, "doublon provider/provider_user_id refuse");

    $second_google_id = "google-second-" . $suffix;
    $stmt = mysqli_prepare($conn, "INSERT INTO user_auth_providers(user_id, provider, provider_user_id) VALUES(?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $social_id, $provider, $second_google_id);
    $duplicate_provider_rejected = !mysqli_stmt_execute($stmt) && mysqli_stmt_errno($stmt) === 1062;
    mysqli_stmt_close($stmt);
    phase2_result($results, "TEST 7 - Un fournisseur par utilisateur", $duplicate_provider_rejected, "deuxieme identite Google refusee");

    $facebook = "facebook";
    $facebook_id = "facebook-id-" . $suffix;
    $stmt = mysqli_prepare($conn, "INSERT INTO user_auth_providers(user_id, provider, provider_user_id, provider_email) VALUES(?, ?, ?, NULL)");
    mysqli_stmt_bind_param($stmt, "iss", $social_id, $facebook, $facebook_id);
    $facebook_ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $provider_count = phase2_count($conn, "SELECT COUNT(*) FROM user_auth_providers WHERE user_id = ?", $social_id);
    phase2_result($results, "TEST 8 - Google et Facebook simultanes", $facebook_ok && $provider_count === 2, "provider_email Facebook accepte NULL");

    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $social_id);
    $deleted = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $provider_count_after_delete = phase2_count($conn, "SELECT COUNT(*) FROM user_auth_providers WHERE user_id = ?", $social_id);
    phase2_result($results, "TEST 9 - Cascade fournisseur", $deleted && $provider_count_after_delete === 0, "identites supprimees avec users");

    $candidate_login = phase2_login($base_url, $emails["local_candidate"], $password, $cookies);
    phase2_result(
        $results,
        "TEST 10 - Parcours Client et Intervenante",
        strpos($client_login["url"], "/client/tableau-de-bord") !== false
            && strpos($candidate_login["url"], "/intervenant/tableau-de-bord") !== false,
        "deux redirections metier conservees"
    );

}catch(Exception $exception){
    phase2_result($results, "Execution de la suite", false, $exception->getMessage());
}

phase2_cleanup($conn, $emails, $cookies);

$passwords_unchanged = true;
foreach($baseline_passwords as $user_id => $password_hash){
    $current_password = NULL;
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $current_password);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if(!$found || $current_password !== $password_hash){
        $passwords_unchanged = false;
        break;
    }
}
phase2_result($results, "TEST 1 - Mots de passe existants preserves", $passwords_unchanged, "comparaison exacte avant/apres");

$remaining = 0;
foreach($emails as $email){
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    $remaining += (int)$count;
}

$failed = 0;
foreach($results as $result){
    if(!$result["ok"]){
        $failed++;
    }
}

echo "RESULT=" . (count($results) - $failed) . "/" . count($results) . PHP_EOL;
echo "TEST_DATA_REMAINING=" . $remaining . PHP_EOL;
$conn->close();
exit($failed === 0 && $remaining === 0 ? 0 : 1);

?>
