<?php

if(PHP_SAPI !== "cli"){
    exit("CLI only.\n");
}

putenv("INFINITIA_GOOGLE_CLIENT_ID=codex-google-client.example");
putenv("INFINITIA_GOOGLE_CLIENT_SECRET=codex-google-secret-placeholder");
putenv("INFINITIA_GOOGLE_REDIRECT_URI=http://127.0.0.1:8080/infinitia-group-sarlu/auth/google/callback.php");

$_SERVER["HTTP_HOST"] = "127.0.0.1:8080";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";

require_once(dirname(__DIR__) . "/config/auth.php");
require_once(dirname(__DIR__) . "/config/database.php");
require_once(dirname(__DIR__) . "/config/google-oauth.php");

$results = array();
$suffix = date("YmdHis") . mt_rand(1000, 9999);
$emails = array(
    "client" => "codex-google-client-" . $suffix . "@example.test",
    "candidate" => "codex-google-candidate-" . $suffix . "@example.test",
    "admin" => "codex-google-admin-" . $suffix . "@example.test",
    "local" => "codex-google-local-" . $suffix . "@example.test",
    "rollback" => "codex-google-rollback-" . $suffix . "@example.test"
);

function google_test_result(&$results, $name, $ok)
{
    $results[] = array("name" => $name, "ok" => $ok ? true : false);
    echo ($ok ? "PASS" : "FAIL") . " - " . $name . PHP_EOL;
}

function google_test_insert_user($conn, $role_id, $email, $status)
{
    $first_name = "Google";
    $last_name = "Test";
    $password = NULL;
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users(role_id, first_name, last_name, email, password, status) VALUES(?, ?, ?, ?, ?, ?)"
    );

    if(!$stmt){
        return 0;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssss",
        $role_id,
        $first_name,
        $last_name,
        $email,
        $password,
        $status
    );
    $ok = mysqli_stmt_execute($stmt);
    $user_id = $ok ? (int)mysqli_insert_id($conn) : 0;
    mysqli_stmt_close($stmt);
    return $user_id;
}

function google_test_identity($suffix, $email)
{
    return array(
        "sub" => "google-sub-" . $suffix,
        "email" => $email,
        "email_verified" => true,
        "first_name" => "Google",
        "last_name" => "Test",
        "name" => "Google Test",
        "picture" => "https://example.test/avatar.png"
    );
}

function google_test_delete_users($conn, $emails)
{
    foreach($emails as $email){
        $user_id = 0;
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");

        if($stmt){
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $user_id);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }

        if((int)$user_id <= 0){
            continue;
        }

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
}

$_SESSION = array();

$started = infinitia_google_begin_transaction("login");
$transaction = isset($_SESSION["google_oauth_transaction"])
    ? $_SESSION["google_oauth_transaction"]
    : false;
$query = array();

if(isset($started["success"]) && $started["success"]){
    $parts = parse_url($started["authorization_url"]);
    parse_str(isset($parts["query"]) ? $parts["query"] : "", $query);
}

google_test_result(
    $results,
    "Authorization Code, scopes minimaux et PKCE S256",
    isset($started["success"]) && $started["success"]
        && $transaction !== false
        && isset($query["response_type"]) && $query["response_type"] === "code"
        && isset($query["scope"]) && $query["scope"] === "openid email profile"
        && isset($query["code_challenge_method"]) && $query["code_challenge_method"] === "S256"
        && isset($query["nonce"]) && $query["nonce"] === $transaction["nonce"]
);

$state = $transaction === false ? "" : $transaction["state"];
$consumed = infinitia_google_consume_transaction($state);
google_test_result($results, "State lie a la session et accepte une fois", $consumed["valid"]);
$replayed = infinitia_google_consume_transaction($state);
google_test_result($results, "Rejeu du state refuse", !$replayed["valid"]);

$started = infinitia_google_begin_transaction("register_client");
$transaction = $_SESSION["google_oauth_transaction"];
$invalid_state = infinitia_google_consume_transaction("invalid-state");
$valid_after_invalid = infinitia_google_consume_transaction($transaction["state"]);
google_test_result(
    $results,
    "State incorrect refuse sans detruire la transaction legitime",
    !$invalid_state["valid"] && $valid_after_invalid["valid"]
);

$started = infinitia_google_begin_transaction("register_candidate");
$transaction = $_SESSION["google_oauth_transaction"];
$_SESSION["google_oauth_transaction"]["created_at"] = time() - 601;
$expired = infinitia_google_consume_transaction($transaction["state"]);
google_test_result(
    $results,
    "State expire refuse",
    !$expired["valid"]
);

$nonce = "simulated-nonce-" . $suffix;
$claims = array(
    "iss" => "https://accounts.google.com",
    "aud" => "codex-google-client.example",
    "exp" => time() + 300,
    "iat" => time() - 10,
    "nonce" => $nonce,
    "sub" => "simulated-sub-" . $suffix,
    "email" => "simulated-" . $suffix . "@example.test",
    "email_verified" => true,
    "given_name" => "Simule",
    "family_name" => "Google"
);
$valid_identity = infinitia_google_identity_from_claims(
    $claims,
    $nonce,
    "codex-google-client.example"
);
google_test_result(
    $results,
    "Identite simulee resolue par sub",
    isset($valid_identity["valid"]) && $valid_identity["valid"]
        && $valid_identity["sub"] === $claims["sub"]
);

$wrong_nonce = infinitia_google_identity_from_claims(
    $claims,
    "wrong-nonce",
    "codex-google-client.example"
);
google_test_result($results, "Nonce incorrect refuse", !$wrong_nonce["valid"]);

$wrong_audience_claims = $claims;
$wrong_audience_claims["aud"] = "another-client.example";
$wrong_audience = infinitia_google_identity_from_claims(
    $wrong_audience_claims,
    $nonce,
    "codex-google-client.example"
);
google_test_result($results, "Audience OIDC incorrecte refusee", !$wrong_audience["valid"]);

$wrong_issuer_claims = $claims;
$wrong_issuer_claims["iss"] = "https://example.test";
$wrong_issuer = infinitia_google_identity_from_claims(
    $wrong_issuer_claims,
    $nonce,
    "codex-google-client.example"
);
google_test_result($results, "Issuer OIDC incorrect refuse", !$wrong_issuer["valid"]);

$expired_claims = $claims;
$expired_claims["exp"] = time() - 120;
$expired_identity = infinitia_google_identity_from_claims(
    $expired_claims,
    $nonce,
    "codex-google-client.example"
);
google_test_result($results, "ID Token expire refuse", !$expired_identity["valid"]);

$unverified_claims = $claims;
$unverified_claims["email_verified"] = false;
$unverified = infinitia_google_identity_from_claims(
    $unverified_claims,
    $nonce,
    "codex-google-client.example"
);
google_test_result($results, "Email Google non verifie refuse", !$unverified["valid"]);

try{
    $unknown_provider = infinitia_google_find_provider_user(
        $conn,
        "google-unknown-" . $suffix
    );
    google_test_result(
        $results,
        "Identite Google inconnue renvoie false",
        $unknown_provider === false
    );

    $client_identity = google_test_identity("client-" . $suffix, $emails["client"]);
    $client_id = google_test_insert_user($conn, 2, $emails["client"], "active");
    $client_type = "individual";
    $stmt = mysqli_prepare($conn, "INSERT INTO clients(user_id, client_type) VALUES(?, ?)");
    mysqli_stmt_bind_param($stmt, "is", $client_id, $client_type);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $client_provider_ok = infinitia_google_insert_provider($conn, $client_id, $client_identity);
    $client_user = infinitia_google_find_provider_user($conn, $client_identity["sub"]);
    $client_decision = infinitia_google_existing_user_decision($client_user, "login");
    google_test_result(
        $results,
        "Client actif lie autorise",
        $client_provider_ok && $client_decision["allowed"]
    );

    $candidate_identity = google_test_identity("candidate-" . $suffix, $emails["candidate"]);
    $candidate_id = google_test_insert_user($conn, 3, $emails["candidate"], "inactive");
    $verification_status = "en_attente";
    $availability_status = "hors_ligne";
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO candidates(user_id, verification_status, availability_status) VALUES(?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "iss", $candidate_id, $verification_status, $availability_status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $candidate_provider_ok = infinitia_google_insert_provider($conn, $candidate_id, $candidate_identity);
    $candidate_user = infinitia_google_find_provider_user($conn, $candidate_identity["sub"]);
    $candidate_decision = infinitia_google_existing_user_decision($candidate_user, "login");
    google_test_result(
        $results,
        "Candidate inactive en attente refusee",
        $candidate_provider_ok && !$candidate_decision["allowed"]
    );

    $active_status = "active";
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $active_status, $candidate_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $candidate_user = infinitia_google_find_provider_user($conn, $candidate_identity["sub"]);
    $candidate_decision = infinitia_google_existing_user_decision($candidate_user, "login");
    google_test_result(
        $results,
        "Candidate active en attente autorisee",
        $candidate_decision["allowed"]
    );

    $verified_status = "verifie";
    $stmt = mysqli_prepare($conn, "UPDATE candidates SET verification_status = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $verified_status, $candidate_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $candidate_user = infinitia_google_find_provider_user($conn, $candidate_identity["sub"]);
    $candidate_decision = infinitia_google_existing_user_decision($candidate_user, "login");
    google_test_result(
        $results,
        "Candidate active verifiee autorisee",
        $candidate_decision["allowed"]
    );

    $suspended_status = "suspended";
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $suspended_status, $candidate_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $candidate_user = infinitia_google_find_provider_user($conn, $candidate_identity["sub"]);
    $candidate_decision = infinitia_google_existing_user_decision($candidate_user, "login");
    google_test_result(
        $results,
        "Candidate suspendue refusee",
        !$candidate_decision["allowed"]
    );

    $admin_identity = google_test_identity("admin-" . $suffix, $emails["admin"]);
    $admin_id = google_test_insert_user($conn, 1, $emails["admin"], "active");
    $admin_provider_ok = infinitia_google_insert_provider($conn, $admin_id, $admin_identity);
    $admin_user = infinitia_google_find_provider_user($conn, $admin_identity["sub"]);
    $admin_decision = infinitia_google_existing_user_decision($admin_user, "login");
    google_test_result(
        $results,
        "Admin refuse sans exception",
        $admin_provider_ok && !$admin_decision["allowed"]
    );

    $role_mismatch = infinitia_google_existing_user_decision($client_user, "register_candidate");
    google_test_result(
        $results,
        "Changement de role refuse",
        !$role_mismatch["allowed"]
    );

    $local_id = google_test_insert_user($conn, 2, $emails["local"], "active");
    $local_found = infinitia_google_find_email_user($conn, $emails["local"]);
    google_test_result(
        $results,
        "Email local existant detecte sans association Google",
        $local_id > 0 && $local_found !== false && (int)$local_found["id"] === $local_id
    );

    mysqli_autocommit($conn, false);
    $rollback_id = google_test_insert_user($conn, 2, $emails["rollback"], "active");
    $stmt = mysqli_prepare($conn, "INSERT INTO clients(user_id, client_type) VALUES(?, ?)");
    mysqli_stmt_bind_param($stmt, "is", $rollback_id, $client_type);
    $profile_ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $duplicate_provider_ok = infinitia_google_insert_provider($conn, $rollback_id, $client_identity);

    if($rollback_id > 0 && $profile_ok && !$duplicate_provider_ok){
        mysqli_rollback($conn);
    }else{
        mysqli_commit($conn);
    }

    mysqli_autocommit($conn, true);
    $rollback_found = infinitia_google_find_email_user($conn, $emails["rollback"]);
    $provider_count = 0;
    $provider = "google";
    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) FROM user_auth_providers WHERE provider = ? AND provider_user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ss", $provider, $client_identity["sub"]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $provider_count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    google_test_result(
        $results,
        "Rollback complet si liaison fournisseur echoue",
        !$duplicate_provider_ok && $rollback_found === false
    );
    google_test_result(
        $results,
        "Aucun doublon d'identite fournisseur",
        (int)$provider_count === 1
    );
}catch(Exception $exception){
    google_test_result($results, "Execution des tests base", false);
}

google_test_delete_users($conn, $emails);

$remaining = 0;
foreach($emails as $email){
    if(infinitia_google_find_email_user($conn, $email) !== false){
        $remaining++;
    }
}

google_test_result($results, "Nettoyage des donnees temporaires", $remaining === 0);

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
