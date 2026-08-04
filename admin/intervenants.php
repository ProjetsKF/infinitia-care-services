<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1){

    header("Location: " . app_url("login"));
    exit();

}

function safe_text($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function display_value($value)
{
    if($value === NULL || $value === ""){

        return "Non renseigne";

    }

    return (string)$value;
}

function format_date_fr($value, $with_time)
{
    if($value === NULL || $value === ""){

        return "Non renseigne";

    }

    $timestamp = strtotime($value);

    if($timestamp === false){

        return "Non renseigne";

    }

    if($with_time){

        return date("d/m/Y H:i", $timestamp);

    }

    return date("d/m/Y", $timestamp);
}

function redirect_intervenants()
{
    header("Location: " . app_url("admin/intervenants"));
    exit();
}

function intervenants_pagination_url($page_number)
{
    $params = $_GET;
    $params["page"] = (int)$page_number;

    return app_url_with_query("admin/intervenants", $params);
}

function count_query($conn, $sql)
{
    $total = 0;
    $result = mysqli_query($conn, $sql);

    if($result){

        $row = mysqli_fetch_assoc($result);

        if($row && isset($row["total"])){

            $total = (int)$row["total"];

        }

        mysqli_free_result($result);

    }

    return $total;
}

function count_search_query($conn, $sql, $search_pattern)
{
    $total = 0;
    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        return $total;

    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $search_pattern,
        $search_pattern,
        $search_pattern,
        $search_pattern,
        $search_pattern
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$total;
}

function normalize_text($value)
{
    $value = strtolower((string)$value);

    $search = array(
        "é", "è", "ê", "ë", "É", "È", "Ê", "Ë",
        "à", "â", "ä", "À", "Â", "Ä",
        "î", "ï", "Î", "Ï",
        "ô", "ö", "Ô", "Ö",
        "ù", "û", "ü", "Ù", "Û", "Ü",
        "ç", "Ç"
    );

    $replace = array(
        "e", "e", "e", "e", "e", "e", "e", "e",
        "a", "a", "a", "a", "a", "a",
        "i", "i", "i", "i",
        "o", "o", "o", "o",
        "u", "u", "u", "u", "u", "u",
        "c", "c"
    );

    return str_replace($search, $replace, $value);
}

function profile_photo_path($profile_photo)
{
    if($profile_photo === NULL || $profile_photo === ""){

        return app_url("assets/images/default-user.png");

    }

    if(strpos($profile_photo, "uploads/") === 0){

        return app_url($profile_photo);

    }

    return app_url("uploads/profiles/" . $profile_photo);
}

function document_path($file_path)
{
    if($file_path === NULL || $file_path === ""){

        return "";

    }

    if(strpos($file_path, "uploads/") === 0){

        return app_url(ltrim($file_path, "/"));

    }

    if(strpos($file_path, "../") === 0){

        return app_url(ltrim(substr($file_path, 3), "/"));

    }

    return app_url(ltrim($file_path, "/"));
}

function availability_badge_class($status)
{
    $normalized = normalize_text($status);

    if($normalized == "disponible"){

        return "green";

    }

    if($normalized == "occupe"){

        return "orange";

    }

    return "red";
}

function verification_badge_class($status)
{
    $normalized = normalize_text($status);

    if($normalized == "verified" || $normalized == "verifie"){

        return "green";

    }

    if($normalized == "pending" || $normalized == "en_attente"){

        return "orange";

    }

    return "red";
}

function account_badge_class($status)
{
    if($status == "active"){

        return "green";

    }

    if($status == "inactive"){

        return "grey";

    }

    return "red";
}

function score_level($score)
{
    $score = (int)$score;

    if($score >= 85){

        return "Excellent";

    }

    if($score >= 70){

        return "Bon";

    }

    if($score >= 50){

        return "Moyen";

    }

    return "Faible";
}

function calculate_ai_score($candidate, &$reasons)
{
    $score = 0;
    $availability = isset($candidate["availability_status"]) ? $candidate["availability_status"] : "";
    $availability_normalized = normalize_text($availability);
    $active_skills = isset($candidate["active_skills"]) ? (int)$candidate["active_skills"] : 0;
    $experience = isset($candidate["experience_years"]) ? $candidate["experience_years"] : "";
    $average = isset($candidate["average_rating"]) ? $candidate["average_rating"] : NULL;
    $reviews = isset($candidate["review_total"]) ? (int)$candidate["review_total"] : 0;
    $verification = isset($candidate["verification_status"]) ? $candidate["verification_status"] : "";
    $verification_normalized = normalize_text($verification);

    if($availability_normalized == "disponible"){

        $score += 30;
        $reasons[] = "Disponibilite : disponible (+30)";

    }elseif($availability_normalized == "occupe"){

        $score += 15;
        $reasons[] = "Disponibilite : occupe (+15)";

    }else{

        $reasons[] = "Disponibilite : hors ligne ou non renseignee (+0)";

    }

    if($active_skills >= 3){

        $score += 30;
        $reasons[] = "Competences actives : " . $active_skills . " (+30)";

    }elseif($active_skills == 2){

        $score += 22;
        $reasons[] = "Competences actives : 2 (+22)";

    }elseif($active_skills == 1){

        $score += 15;
        $reasons[] = "Competences actives : 1 (+15)";

    }else{

        $reasons[] = "Competences actives : aucune (+0)";

    }

    if($experience === NULL || $experience === ""){

        $reasons[] = "Experience : non renseignee (+0)";

    }else{

        $years = (int)$experience;

        if($years >= 5){

            $score += 20;
            $reasons[] = "Experience : 5 ans ou plus (+20)";

        }elseif($years >= 3){

            $score += 15;
            $reasons[] = "Experience : 3 a 4 ans (+15)";

        }elseif($years >= 1){

            $score += 10;
            $reasons[] = "Experience : 1 a 2 ans (+10)";

        }else{

            $score += 5;
            $reasons[] = "Experience : moins d'un an (+5)";

        }

    }

    if($reviews <= 0){

        $score += 5;
        $reasons[] = "Evaluations : aucune, score neutre (+5)";

    }else{

        $average = (float)$average;

        if($average >= 4.5){

            $score += 10;
            $reasons[] = "Evaluations : moyenne >= 4.5 (+10)";

        }elseif($average >= 4){

            $score += 8;
            $reasons[] = "Evaluations : moyenne >= 4 (+8)";

        }elseif($average >= 3){

            $score += 5;
            $reasons[] = "Evaluations : moyenne >= 3 (+5)";

        }else{

            $score += 2;
            $reasons[] = "Evaluations : moyenne < 3 (+2)";

        }

    }

    if($verification_normalized == "verified" || $verification_normalized == "verifie"){

        $score += 10;
        $reasons[] = "Verification : profil verifie (+10)";

    }elseif($verification_normalized == "pending" || $verification_normalized == "en_attente"){

        $score += 4;
        $reasons[] = "Verification : en attente (+4)";

    }else{

        $reasons[] = "Verification : non verifie ou rejete (+0)";

    }

    if($score > 100){

        $score = 100;

    }

    return $score;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

    $candidate_id = isset($_POST["candidate_id"])
        ? (int)$_POST["candidate_id"]
        : 0;

    if($candidate_id <= 0){

        $_SESSION["error"] = "Intervenant invalide.";
        redirect_intervenants();

    }

    $user_id = 0;

    $sql = "
    SELECT user_id
    FROM candidates
    WHERE id = ?
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        die("Erreur SQL : " . mysqli_error($conn));

    }

    mysqli_stmt_bind_param($stmt, "i", $candidate_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if($user_id <= 0){

        $_SESSION["error"] = "Intervenant introuvable.";
        redirect_intervenants();

    }

    if($action == "update_candidate"){

        $phone = isset($_POST["phone"]) ? trim($_POST["phone"]) : "";
        $city = isset($_POST["city"]) ? trim($_POST["city"]) : "";
        $address = isset($_POST["address"]) ? trim($_POST["address"]) : "";
        $experience_years = isset($_POST["experience_years"]) ? trim($_POST["experience_years"]) : "";
        $bio = isset($_POST["bio"]) ? trim($_POST["bio"]) : "";
        $availability_status = isset($_POST["availability_status"]) ? trim($_POST["availability_status"]) : "";

        if($availability_status != "disponible" && $availability_status != "occupé" && $availability_status != "hors_ligne"){

            $availability_status = "hors_ligne";

        }

        if($experience_years === ""){

            $experience_value = NULL;

        }else{

            $experience_value = (int)$experience_years;

        }

        if($phone == ""){

            $phone = NULL;

        }

        if($city == ""){

            $city = NULL;

        }

        if($address == ""){

            $address = NULL;

        }

        if($bio == ""){

            $bio = NULL;

        }

        $sql = "
        UPDATE users
        SET phone = ?
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "si", $phone, $user_id);
        $user_updated = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $sql = "
        UPDATE candidates
        SET
            city = ?,
            address = ?,
            experience_years = ?,
            bio = ?,
            availability_status = ?
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssissi",
            $city,
            $address,
            $experience_value,
            $bio,
            $availability_status,
            $candidate_id
        );

        $candidate_updated = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if($user_updated && $candidate_updated){

            $_SESSION["success"] = "Intervenant modifie avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors de la modification de l'intervenant.";

        }

        redirect_intervenants();

    }

    if($action == "verify_candidate" || $action == "reject_candidate"){

        $verification_status = "verifie";

        if($action == "reject_candidate"){

            $verification_status = "rejete";

        }

        $sql = "
        UPDATE candidates
        SET verification_status = ?
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "si", $verification_status, $candidate_id);

        if(mysqli_stmt_execute($stmt)){

            if($action == "verify_candidate"){

                $_SESSION["success"] = "Profil intervenant verifie avec succes.";

            }else{

                $_SESSION["success"] = "Profil intervenant rejete avec succes.";

            }

        }else{

            $_SESSION["error"] = "Erreur lors de la mise a jour de la verification.";

        }

        mysqli_stmt_close($stmt);
        redirect_intervenants();

    }

    if($action == "toggle_account"){

        $current_status = isset($_POST["current_status"]) ? $_POST["current_status"] : "";
        $new_status = "active";

        if($current_status == "active"){

            $new_status = "inactive";

        }

        $sql = "
        UPDATE users
        SET status = ?
        WHERE id = ?
        AND role_id = 3
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);

        if(mysqli_stmt_execute($stmt)){

            $_SESSION["success"] = "Statut du compte mis a jour.";

        }else{

            $_SESSION["error"] = "Erreur lors de la mise a jour du compte.";

        }

        mysqli_stmt_close($stmt);
        redirect_intervenants();

    }

}

$without_active_mission_sql = "
AND NOT EXISTS (
    SELECT 1
    FROM missions active_mission
    WHERE active_mission.candidate_id = c.id
    AND active_mission.mission_status IN ('attribuee', 'en_cours')
)";

$stats = array(
    "total" => count_query($conn, "SELECT COUNT(*) AS total FROM candidates c INNER JOIN users u ON u.id = c.user_id WHERE u.role_id = 3 " . $without_active_mission_sql),
    "available" => count_query($conn, "SELECT COUNT(*) AS total FROM candidates c INNER JOIN users u ON u.id = c.user_id WHERE u.role_id = 3 AND c.availability_status = 'disponible' " . $without_active_mission_sql),
    "busy" => count_query($conn, "SELECT COUNT(*) AS total FROM candidates c INNER JOIN users u ON u.id = c.user_id WHERE u.role_id = 3 AND c.availability_status = 'occupé' " . $without_active_mission_sql),
    "offline" => count_query($conn, "SELECT COUNT(*) AS total FROM candidates c INNER JOIN users u ON u.id = c.user_id WHERE u.role_id = 3 AND c.availability_status = 'hors_ligne' " . $without_active_mission_sql),
    "verified" => count_query($conn, "SELECT COUNT(*) AS total FROM candidates c INNER JOIN users u ON u.id = c.user_id WHERE u.role_id = 3 AND c.verification_status = 'verifie' " . $without_active_mission_sql),
    "pending" => count_query($conn, "SELECT COUNT(*) AS total FROM candidates c INNER JOIN users u ON u.id = c.user_id WHERE u.role_id = 3 AND c.verification_status = 'en_attente' " . $without_active_mission_sql),
    "active_accounts" => count_query($conn, "SELECT COUNT(*) AS total FROM candidates c INNER JOIN users u ON u.id = c.user_id WHERE u.role_id = 3 AND u.status = 'active' " . $without_active_mission_sql)
);

$candidates = array();
$skills_by_candidate = array();
$documents_by_candidate = array();
$trainings_by_candidate = array();
$missions_by_candidate = array();
$reviews_by_candidate = array();
$limit = 50;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$search_pattern = "%" . $search . "%";
$search_sql = "";

if($page < 1){

    $page = 1;

}

$count_sql = "
SELECT COUNT(*) AS total
FROM candidates c
INNER JOIN users u
ON u.id = c.user_id
WHERE u.role_id = 3
" . $without_active_mission_sql;

if($search != ""){

    $search_sql = "
    AND (
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR u.phone LIKE ?
        OR u.email LIKE ?
        OR c.city LIKE ?
    )";
    $count_sql .= $search_sql;
    $total_candidates = count_search_query($conn, $count_sql, $search_pattern);

}else{

    $total_candidates = count_query($conn, $count_sql);

}

$total_pages = (int)ceil($total_candidates / $limit);

if($total_pages > 0 && $page > $total_pages){

    $page = $total_pages;

}

if($total_pages < 1){

    $page = 1;

}

$offset = ($page - 1) * $limit;

$sql = "
SELECT
    c.id AS candidate_id,
    c.user_id,
    c.birth_date,
    c.gender,
    c.address,
    c.city,
    c.nationality,
    c.marital_status,
    c.education_level,
    c.experience_years,
    c.bio,
    c.availability_status,
    c.verification_status,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.profile_photo,
    u.status AS account_status,
    (SELECT COUNT(*) FROM candidate_skills cs WHERE cs.candidate_id = c.id AND cs.is_active = 1) AS active_skills,
    (SELECT COUNT(*) FROM missions m WHERE m.candidate_id = c.id) AS mission_total,
    (SELECT AVG(sr.note_generale) FROM service_reviews sr WHERE sr.candidate_id = c.id) AS average_rating,
    (SELECT COUNT(*) FROM service_reviews sr WHERE sr.candidate_id = c.id) AS review_total
FROM candidates c
INNER JOIN users u
ON u.id = c.user_id
WHERE u.role_id = 3
" . $without_active_mission_sql . $search_sql . "
ORDER BY
    c.created_at DESC,
    c.id DESC
LIMIT ?
OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt){

    if($search != ""){

        mysqli_stmt_bind_param(
            $stmt,
            "sssssii",
            $search_pattern,
            $search_pattern,
            $search_pattern,
            $search_pattern,
            $search_pattern,
            $limit,
            $offset
        );

    }else{

        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);

    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result(
        $stmt,
        $candidate_id,
        $user_id,
        $birth_date,
        $gender,
        $address,
        $city,
        $nationality,
        $marital_status,
        $education_level,
        $experience_years,
        $bio,
        $availability_status,
        $verification_status,
        $first_name,
        $last_name,
        $email,
        $phone,
        $profile_photo,
        $account_status,
        $active_skills,
        $mission_total,
        $average_rating,
        $review_total
    );

    while(mysqli_stmt_fetch($stmt)){

        $row = array(
            "candidate_id" => $candidate_id,
            "user_id" => $user_id,
            "birth_date" => $birth_date,
            "gender" => $gender,
            "address" => $address,
            "city" => $city,
            "nationality" => $nationality,
            "marital_status" => $marital_status,
            "education_level" => $education_level,
            "experience_years" => $experience_years,
            "bio" => $bio,
            "availability_status" => $availability_status,
            "verification_status" => $verification_status,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "email" => $email,
            "phone" => $phone,
            "profile_photo" => $profile_photo,
            "account_status" => $account_status,
            "active_skills" => $active_skills,
            "mission_total" => $mission_total,
            "average_rating" => $average_rating,
            "review_total" => $review_total
        );
        $reasons = array();
        $score = calculate_ai_score($row, $reasons);
        $row["ai_score"] = $score;
        $row["score_level"] = score_level($score);
        $row["score_reasons"] = $reasons;
        $candidates[] = $row;
        $candidate_id = (int)$row["candidate_id"];
        $skills_by_candidate[$candidate_id] = array();
        $documents_by_candidate[$candidate_id] = array();
        $trainings_by_candidate[$candidate_id] = array();
        $missions_by_candidate[$candidate_id] = array();
        $reviews_by_candidate[$candidate_id] = array();

    }

    mysqli_stmt_close($stmt);

}

if(count($candidates) > 0){

    $result = mysqli_query($conn, "SELECT * FROM candidate_skills ORDER BY created_at DESC");

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $candidate_id = isset($row["candidate_id"]) ? (int)$row["candidate_id"] : 0;

            if(isset($skills_by_candidate[$candidate_id])){

                $skills_by_candidate[$candidate_id][] = $row;

            }

        }

        mysqli_free_result($result);

    }

    $result = mysqli_query($conn, "SELECT * FROM candidate_documents ORDER BY uploaded_at DESC");

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $candidate_id = isset($row["candidate_id"]) ? (int)$row["candidate_id"] : 0;

            if(isset($documents_by_candidate[$candidate_id])){

                $documents_by_candidate[$candidate_id][] = $row;

            }

        }

        mysqli_free_result($result);

    }

    $sql = "
    SELECT
        ct.candidate_id,
        ct.status,
        t.title,
        t.duration,
        t.youtube_url
    FROM candidate_trainings ct
    INNER JOIN trainings t
    ON t.id = ct.training_id
    ORDER BY t.title ASC
    ";

    $result = mysqli_query($conn, $sql);

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $candidate_id = isset($row["candidate_id"]) ? (int)$row["candidate_id"] : 0;

            if(isset($trainings_by_candidate[$candidate_id])){

                $trainings_by_candidate[$candidate_id][] = $row;

            }

        }

        mysqli_free_result($result);

    }

    $sql = "
    SELECT
        m.id,
        m.candidate_id,
        m.service_request_id,
        m.start_time,
        m.end_time,
        m.mission_status,
        sr.title
    FROM missions m
    INNER JOIN service_requests sr
    ON sr.id = m.service_request_id
    ORDER BY m.created_at DESC
    ";

    $result = mysqli_query($conn, $sql);

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $candidate_id = isset($row["candidate_id"]) ? (int)$row["candidate_id"] : 0;

            if(isset($missions_by_candidate[$candidate_id])){

                $missions_by_candidate[$candidate_id][] = $row;

            }

        }

        mysqli_free_result($result);

    }

    $sql = "
    SELECT
        id,
        candidate_id,
        note_generale,
        note_ponctualite,
        note_professionnalisme,
        note_qualite_service,
        commentaire,
        created_at
    FROM service_reviews
    ORDER BY created_at DESC
    ";

    $result = mysqli_query($conn, $sql);

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $candidate_id = isset($row["candidate_id"]) ? (int)$row["candidate_id"] : 0;

            if(isset($reviews_by_candidate[$candidate_id])){

                $reviews_by_candidate[$candidate_id][] = $row;

            }

        }

        mysqli_free_result($result);

    }

}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(dirname(__DIR__) . "/includes/pwa-head.php"); ?>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Intervenants | INFINITIA</title>

    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

    <style>
        .admin-summary-card{
            background:#ffffff;
            border-radius:14px;
            padding:16px;
            box-shadow:0 8px 22px rgba(0,0,0,.08);
            min-height:112px;
        }

        .admin-summary-card h5{
            color:#2f3b55;
            font-size:14px;
            font-weight:600;
            margin:10px 0 5px;
        }

        .admin-summary-card h3{
            color:#081f78;
            font-size:28px;
            font-weight:800;
            margin:0;
        }

        .candidate-avatar{
            width:48px;
            height:48px;
            border-radius:50%;
            object-fit:cover;
        }

        .candidate-profile-photo{
            width:92px;
            height:92px;
            border-radius:50%;
            object-fit:cover;
            box-shadow:0 8px 20px rgba(0,0,0,.15);
        }

        .score-bar{
            background:#eeeeee;
            border-radius:20px;
            height:9px;
            margin-top:6px;
            overflow:hidden;
            width:110px;
        }

        .score-fill{
            background:linear-gradient(45deg,#081f78,#e83e8c);
            height:9px;
        }

        .modal-wide{
            width:88%;
            max-height:88%;
        }

        .profile-section{
            background:#ffffff;
            border:1px solid #eeeeee;
            border-radius:14px;
            box-shadow:0 6px 18px rgba(0,0,0,.05);
            margin-bottom:18px;
            padding:18px;
        }

        .profile-section-title{
            align-items:center;
            color:#081f78;
            display:flex;
            font-size:18px;
            font-weight:700;
            gap:8px;
            margin:0 0 14px;
        }

        .detail-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
            gap:12px;
        }

        .detail-item{
            background:#fafafa;
            border-radius:10px;
            padding:12px;
        }

        .detail-label{
            color:#757575;
            display:block;
            font-size:12px;
            font-weight:600;
            text-transform:uppercase;
        }

        .actions-wrap{
            display:flex;
            flex-wrap:wrap;
            gap:7px;
        }

        .actions-wrap form{
            margin:0;
        }

        .table-pagination{
            margin-top:25px;
            margin-bottom:20px;
            text-align:center;
        }

        .intervenants-search{
            align-items:center;
            display:flex;
            flex-wrap:wrap;
            gap:12px;
            margin-bottom:20px;
        }

        .intervenants-search .input-field{
            flex:1 1 320px;
            margin:0;
        }

        .intervenants-search .btn,
        .intervenants-search .btn-flat{
            margin:0;
        }
    </style>

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "intervenants";

    include("menuadmin.php");

    ?>

    <div class="main-content">

        <div class="topbar">
            <div>
                <div class="page-title">Intervenants</div>
                <div class="welcome-text">
                    Gestion des profils, disponibilites, formations, missions et evaluations.
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION["success"])){ ?>
            <div class="card-panel green white-text">
                <?php echo safe_text($_SESSION["success"]); ?>
            </div>
            <?php unset($_SESSION["success"]); ?>
        <?php } ?>

        <?php if(isset($_SESSION["error"])){ ?>
            <div class="card-panel red white-text">
                <?php echo safe_text($_SESSION["error"]); ?>
            </div>
            <?php unset($_SESSION["error"]); ?>
        <?php } ?>

        <div class="row">
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">groups</i></div>
                    <h5>Total intervenants</h5>
                    <h3><?php echo (int)$stats["total"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">check_circle</i></div>
                    <h5>Disponibles</h5>
                    <h3><?php echo (int)$stats["available"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">pending</i></div>
                    <h5>Occupes</h5>
                    <h3><?php echo (int)$stats["busy"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon pink-gradient"><i class="material-icons">power_settings_new</i></div>
                    <h5>Hors ligne</h5>
                    <h3><?php echo (int)$stats["offline"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">verified</i></div>
                    <h5>Verifies</h5>
                    <h3><?php echo (int)$stats["verified"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">schedule</i></div>
                    <h5>En attente</h5>
                    <h3><?php echo (int)$stats["pending"]; ?></h3>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">person</i></div>
                    <h5>Comptes actifs</h5>
                    <h3><?php echo (int)$stats["active_accounts"]; ?></h3>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-content">
                <form action="<?php echo app_url_html("admin/intervenants"); ?>" method="GET" class="intervenants-search">
                    <div class="input-field">
                        <i class="material-icons prefix">search</i>
                        <input type="text"
                               name="search"
                               id="intervenantsSearch"
                               value="<?php echo safe_text($search); ?>">
                        <label for="intervenantsSearch" class="<?php if($search != ""){ echo "active"; } ?>">
                            Rechercher par prenom, nom, telephone, email ou ville
                        </label>
                    </div>
                    <button type="submit" class="btn blue darken-4">
                        Rechercher
                    </button>
                    <a href="<?php echo app_url_html("admin/intervenants"); ?>" class="btn-flat">
                        Reinitialiser
                    </a>
                </form>
            </div>
        </div>

        <?php if(count($candidates) > 0){ ?>

            <div class="table-card">
                <div class="table-title">Liste des intervenants</div>

                <table class="highlight responsive-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Nom complet</th>
                            <th>Ville</th>
                            <th>Disponibilite</th>
                            <th>Verification</th>
                            <th>Experience</th>
                            <th>Score IA</th>
                            <th>Note moyenne</th>
                            <th>Missions</th>
                            <th>Compte</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($candidates as $candidate){ ?>
                            <?php
                            $candidate_id = (int)$candidate["candidate_id"];
                            $full_name = trim($candidate["first_name"] . " " . $candidate["last_name"]);
                            $availability = isset($candidate["availability_status"]) ? $candidate["availability_status"] : "";
                            $verification = isset($candidate["verification_status"]) ? $candidate["verification_status"] : "";
                            $account_status = isset($candidate["account_status"]) ? $candidate["account_status"] : "";
                            $score = isset($candidate["ai_score"]) ? (int)$candidate["ai_score"] : 0;
                            $average_rating = "Aucune";

                            if(isset($candidate["average_rating"]) && $candidate["average_rating"] !== NULL){

                                $average_rating = number_format((float)$candidate["average_rating"], 1);

                            }
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo safe_text(profile_photo_path($candidate["profile_photo"])); ?>"
                                         class="candidate-avatar"
                                         alt="Photo">
                                </td>
                                <td><?php echo safe_text(display_value($full_name)); ?></td>
                                <td><?php echo safe_text(display_value($candidate["city"])); ?></td>
                                <td>
                                    <span class="new badge <?php echo safe_text(availability_badge_class($availability)); ?>" data-badge-caption="">
                                        <?php echo safe_text(display_value($availability)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="new badge <?php echo safe_text(verification_badge_class($verification)); ?>" data-badge-caption="">
                                        <?php echo safe_text(display_value($verification)); ?>
                                    </span>
                                </td>
                                <td><?php echo safe_text(display_value($candidate["experience_years"])); ?> an(s)</td>
                                <td>
                                    <strong><?php echo (int)$score; ?>%</strong>
                                    <div class="score-bar">
                                        <div class="score-fill" style="width:<?php echo (int)$score; ?>%;"></div>
                                    </div>
                                    <small><?php echo safe_text($candidate["score_level"]); ?></small>
                                </td>
                                <td><?php echo safe_text($average_rating); ?></td>
                                <td><?php echo (int)$candidate["mission_total"]; ?></td>
                                <td>
                                    <span class="new badge <?php echo safe_text(account_badge_class($account_status)); ?>" data-badge-caption="">
                                        <?php echo safe_text(display_value($account_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-wrap">
                                        <a href="#viewCandidate<?php echo $candidate_id; ?>"
                                           class="btn-small green modal-trigger">
                                            Voir
                                        </a>
                                        <a href="#editCandidate<?php echo $candidate_id; ?>"
                                           class="btn-small blue modal-trigger">
                                            Modifier
                                        </a>

                                        <?php if(normalize_text($verification) != "verifie"){ ?>
                                            <form action="<?php echo app_url_html("admin/intervenants"); ?>" method="POST">
                                                <input type="hidden" name="action" value="verify_candidate">
                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate_id; ?>">
                                                <button type="submit" class="btn-small orange">
                                                    Verifier
                                                </button>
                                            </form>
                                        <?php } ?>

                                        <?php if(normalize_text($verification) != "rejete"){ ?>
                                            <form action="<?php echo app_url_html("admin/intervenants"); ?>" method="POST">
                                                <input type="hidden" name="action" value="reject_candidate">
                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate_id; ?>">
                                                <button type="submit" class="btn-small red">
                                                    Rejeter
                                                </button>
                                            </form>
                                        <?php } ?>

                                        <form action="<?php echo app_url_html("admin/intervenants"); ?>" method="POST">
                                            <input type="hidden" name="action" value="toggle_account">
                                            <input type="hidden" name="candidate_id" value="<?php echo $candidate_id; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo safe_text($account_status); ?>">
                                            <button type="submit" class="btn-small grey darken-1">
                                                <?php if($account_status == "active"){ ?>
                                                    Desactiver
                                                <?php }else{ ?>
                                                    Reactiver
                                                <?php } ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <?php if($total_pages > 1){ ?>
                    <div class="table-pagination">
                        <ul class="pagination center-align">
                            <?php if($page > 1){ ?>
                                <li class="waves-effect">
                                    <a href="<?php echo safe_text(intervenants_pagination_url($page - 1)); ?>">Precedent</a>
                                </li>
                            <?php }else{ ?>
                                <li class="disabled"><a href="#!">Precedent</a></li>
                            <?php } ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if($start_page > 1){
                            ?>
                                <li class="waves-effect"><a href="<?php echo safe_text(intervenants_pagination_url(1)); ?>">1</a></li>
                                <?php if($start_page > 2){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                            <?php } ?>

                            <?php for($page_number = $start_page; $page_number <= $end_page; $page_number++){ ?>
                                <?php if($page_number == $page){ ?>
                                    <li class="active"><a href="#!"><?php echo (int)$page_number; ?></a></li>
                                <?php }else{ ?>
                                    <li class="waves-effect">
                                        <a href="<?php echo safe_text(intervenants_pagination_url($page_number)); ?>"><?php echo (int)$page_number; ?></a>
                                    </li>
                                <?php } ?>
                            <?php } ?>

                            <?php if($end_page < $total_pages){ ?>
                                <?php if($end_page < $total_pages - 1){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                                <li class="waves-effect"><a href="<?php echo safe_text(intervenants_pagination_url($total_pages)); ?>"><?php echo (int)$total_pages; ?></a></li>
                            <?php } ?>

                            <?php if($page < $total_pages){ ?>
                                <li class="waves-effect">
                                    <a href="<?php echo safe_text(intervenants_pagination_url($page + 1)); ?>">Suivant</a>
                                </li>
                            <?php }else{ ?>
                                <li class="disabled"><a href="#!">Suivant</a></li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>
            </div>

        <?php }else{ ?>

            <div class="card">
                <div class="card-content center">
                    <i class="material-icons large blue-text text-darken-4">groups</i>
                    <h5>Aucun intervenant n'est encore enregistre.</h5>
                </div>
            </div>

        <?php } ?>

    </div>
</div>

<?php foreach($candidates as $candidate){ ?>
    <?php
    $candidate_id = (int)$candidate["candidate_id"];
    $full_name = trim($candidate["first_name"] . " " . $candidate["last_name"]);
    $availability = isset($candidate["availability_status"]) ? $candidate["availability_status"] : "";
    $verification = isset($candidate["verification_status"]) ? $candidate["verification_status"] : "";
    $account_status = isset($candidate["account_status"]) ? $candidate["account_status"] : "";
    $score = isset($candidate["ai_score"]) ? (int)$candidate["ai_score"] : 0;
    $candidate_skills = isset($skills_by_candidate[$candidate_id]) ? $skills_by_candidate[$candidate_id] : array();
    $candidate_documents = isset($documents_by_candidate[$candidate_id]) ? $documents_by_candidate[$candidate_id] : array();
    $candidate_trainings = isset($trainings_by_candidate[$candidate_id]) ? $trainings_by_candidate[$candidate_id] : array();
    $candidate_missions = isset($missions_by_candidate[$candidate_id]) ? $missions_by_candidate[$candidate_id] : array();
    $candidate_reviews = isset($reviews_by_candidate[$candidate_id]) ? $reviews_by_candidate[$candidate_id] : array();
    ?>

    <div id="viewCandidate<?php echo $candidate_id; ?>" class="modal modal-fixed-footer modal-wide">
        <div class="modal-content">
            <div class="profile-section">
                <div style="display:flex; gap:18px; align-items:center; flex-wrap:wrap;">
                    <img src="<?php echo safe_text(profile_photo_path($candidate["profile_photo"])); ?>"
                         class="candidate-profile-photo"
                         alt="Photo intervenant">
                    <div>
                        <h4 style="margin:0;"><?php echo safe_text(display_value($full_name)); ?></h4>
                        <p class="grey-text" style="margin:6px 0 0;">
                            <?php echo safe_text(display_value($candidate["email"])); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="profile-section">
                <h5 class="profile-section-title">
                    <i class="material-icons">person</i>
                    Informations personnelles
                </h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Telephone</span><?php echo safe_text(display_value($candidate["phone"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Sexe</span><?php echo safe_text(display_value($candidate["gender"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Date de naissance</span><?php echo safe_text(format_date_fr($candidate["birth_date"], false)); ?></div>
                    <div class="detail-item"><span class="detail-label">Nationalite</span><?php echo safe_text(display_value($candidate["nationality"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Etat civil</span><?php echo safe_text(display_value($candidate["marital_status"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Niveau d'etude</span><?php echo safe_text(display_value($candidate["education_level"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Ville</span><?php echo safe_text(display_value($candidate["city"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Adresse</span><?php echo safe_text(display_value($candidate["address"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Experience</span><?php echo safe_text(display_value($candidate["experience_years"])); ?> an(s)</div>
                    <div class="detail-item"><span class="detail-label">Disponibilite</span><?php echo safe_text(display_value($availability)); ?></div>
                    <div class="detail-item"><span class="detail-label">Verification</span><?php echo safe_text(display_value($verification)); ?></div>
                    <div class="detail-item"><span class="detail-label">Compte</span><?php echo safe_text(display_value($account_status)); ?></div>
                </div>
                <p style="margin-top:16px;">
                    <strong>Biographie :</strong><br>
                    <?php echo nl2br(safe_text(display_value($candidate["bio"]))); ?>
                </p>
            </div>

            <div class="profile-section">
                <h5 class="profile-section-title">
                    <i class="material-icons">auto_awesome</i>
                    Score IA interne
                </h5>
                <p>
                    <strong><?php echo (int)$score; ?>%</strong>
                    -
                    <?php echo safe_text($candidate["score_level"]); ?>
                </p>
                <div class="score-bar" style="width:100%; max-width:420px;">
                    <div class="score-fill" style="width:<?php echo (int)$score; ?>%;"></div>
                </div>
                <ul>
                    <?php foreach($candidate["score_reasons"] as $reason){ ?>
                        <li><?php echo safe_text($reason); ?></li>
                    <?php } ?>
                </ul>
                <p class="grey-text">
                    Les documents sont affiches pour consultation manuelle et ne sont pas utilises dans ce score.
                </p>
            </div>

            <div class="profile-section">
                <h5 class="profile-section-title"><i class="material-icons">psychology</i>Competences</h5>
                <?php if(count($candidate_skills) > 0){ ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Competence</th>
                                <th>Niveau</th>
                                <th>Description</th>
                                <th>Annees</th>
                                <th>Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($candidate_skills as $skill){ ?>
                                <tr>
                                    <td><?php echo safe_text(display_value($skill["skill_name"])); ?></td>
                                    <td><?php echo safe_text(display_value($skill["level"])); ?></td>
                                    <td><?php echo safe_text(display_value($skill["description"])); ?></td>
                                    <td><?php echo safe_text(display_value($skill["years_experience"])); ?></td>
                                    <td><?php echo ((int)$skill["is_active"] == 1) ? "Oui" : "Non"; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php }else{ ?>
                    <p class="grey-text">Aucune competence renseignee.</p>
                <?php } ?>
            </div>

            <div class="profile-section">
                <h5 class="profile-section-title"><i class="material-icons">folder</i>Documents</h5>
                <?php if(count($candidate_documents) > 0){ ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Fichier</th>
                                <th>Verifie</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($candidate_documents as $document){ ?>
                                <?php $file_path = isset($document["file_path"]) ? $document["file_path"] : ""; ?>
                                <tr>
                                    <td><?php echo safe_text(display_value($document["document_type"])); ?></td>
                                    <td><?php echo safe_text(display_value($file_path)); ?></td>
                                    <td><?php echo ((int)$document["verified"] == 1) ? "Oui" : "Non"; ?></td>
                                    <td><?php echo safe_text(format_date_fr($document["uploaded_at"], true)); ?></td>
                                    <td>
                                        <?php if($file_path != ""){ ?>
                                            <a href="<?php echo safe_text(document_path($file_path)); ?>"
                                               target="_blank"
                                               class="btn-small">
                                                Voir document
                                            </a>
                                        <?php }else{ ?>
                                            -
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php }else{ ?>
                    <p class="grey-text">Aucun document transmis.</p>
                <?php } ?>
            </div>

            <div class="profile-section">
                <h5 class="profile-section-title"><i class="material-icons">school</i>Formations</h5>
                <?php if(count($candidate_trainings) > 0){ ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Duree</th>
                                <th>Lien</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($candidate_trainings as $training){ ?>
                                <tr>
                                    <td><?php echo safe_text(display_value($training["title"])); ?></td>
                                    <td><?php echo safe_text(display_value($training["duration"])); ?></td>
                                    <td>
                                        <?php if(isset($training["youtube_url"]) && $training["youtube_url"] != ""){ ?>
                                            <a href="<?php echo safe_text($training["youtube_url"]); ?>" target="_blank">Ouvrir</a>
                                        <?php }else{ ?>
                                            -
                                        <?php } ?>
                                    </td>
                                    <td><?php echo safe_text(display_value($training["status"])); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php }else{ ?>
                    <p class="grey-text">Aucune formation attribuee.</p>
                <?php } ?>
            </div>

            <div class="profile-section">
                <h5 class="profile-section-title"><i class="material-icons">assignment</i>Missions</h5>
                <?php if(count($candidate_missions) > 0){ ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Service demande</th>
                                <th>Statut</th>
                                <th>Date debut</th>
                                <th>Date fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($candidate_missions as $mission){ ?>
                                <tr>
                                    <td>MISS-<?php echo str_pad((int)$mission["id"], 5, "0", STR_PAD_LEFT); ?></td>
                                    <td><?php echo safe_text(display_value($mission["title"])); ?></td>
                                    <td><?php echo safe_text(display_value($mission["mission_status"])); ?></td>
                                    <td><?php echo safe_text(format_date_fr($mission["start_time"], true)); ?></td>
                                    <td><?php echo safe_text(format_date_fr($mission["end_time"], true)); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php }else{ ?>
                    <p class="grey-text">Aucune mission affectee.</p>
                <?php } ?>
            </div>

            <div class="profile-section">
                <h5 class="profile-section-title"><i class="material-icons">star</i>Evaluations</h5>
                <?php if(count($candidate_reviews) > 0){ ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Generale</th>
                                <th>Ponctualite</th>
                                <th>Professionnalisme</th>
                                <th>Qualite</th>
                                <th>Commentaire</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($candidate_reviews as $review){ ?>
                                <tr>
                                    <td><?php echo (int)$review["note_generale"]; ?>/5</td>
                                    <td><?php echo (int)$review["note_ponctualite"]; ?>/5</td>
                                    <td><?php echo (int)$review["note_professionnalisme"]; ?>/5</td>
                                    <td><?php echo (int)$review["note_qualite_service"]; ?>/5</td>
                                    <td><?php echo safe_text(display_value($review["commentaire"])); ?></td>
                                    <td><?php echo safe_text(format_date_fr($review["created_at"], true)); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php }else{ ?>
                    <p class="grey-text">Aucune evaluation recue.</p>
                <?php } ?>
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Fermer</a>
        </div>
    </div>

    <div id="editCandidate<?php echo $candidate_id; ?>" class="modal modal-fixed-footer">
        <form action="<?php echo app_url_html("admin/intervenants"); ?>" method="POST">
            <input type="hidden" name="action" value="update_candidate">
            <input type="hidden" name="candidate_id" value="<?php echo $candidate_id; ?>">

            <div style="
                                background:linear-gradient(90deg,#1b2d8f,#e63b88);
                                padding:28px 40px;
                                border-radius:18px 18px 0 0;">

                                <h4 style="
                                    margin:0;
                                    color:#fff;
                                    font-size:38px;
                                    font-weight:700;
                                ">
                               Modifier l'intervenant
                                </h4>

                            </div>
            <div class="modal-content" style="font-size:17px;line-height:1.9;color:#555;text-align:justify;">
        

                <div class="input-field">
                    <input type="text"
                           name="phone"
                           id="phone<?php echo $candidate_id; ?>"
                           value="<?php echo safe_text($candidate["phone"]); ?>">
                    <label class="active" for="phone<?php echo $candidate_id; ?>">Telephone</label>
                </div>

                <div class="input-field">
                    <input type="text"
                           name="city"
                           id="city<?php echo $candidate_id; ?>"
                           value="<?php echo safe_text($candidate["city"]); ?>">
                    <label class="active" for="city<?php echo $candidate_id; ?>">Ville</label>
                </div>

                <div class="input-field">
                    <textarea name="address"
                              id="address<?php echo $candidate_id; ?>"
                              class="materialize-textarea"><?php echo safe_text($candidate["address"]); ?></textarea>
                    <label class="active" for="address<?php echo $candidate_id; ?>">Adresse</label>
                </div>

                <div class="input-field">
                    <input type="number"
                           min="0"
                           name="experience_years"
                           id="experience<?php echo $candidate_id; ?>"
                           value="<?php echo safe_text($candidate["experience_years"]); ?>">
                    <label class="active" for="experience<?php echo $candidate_id; ?>">Experience</label>
                </div>

                <div class="input-field">
                    <textarea name="bio"
                              id="bio<?php echo $candidate_id; ?>"
                              class="materialize-textarea"><?php echo safe_text($candidate["bio"]); ?></textarea>
                    <label class="active" for="bio<?php echo $candidate_id; ?>">Biographie</label>
                </div>

                <div class="input-field">
                    <select name="availability_status" required>
                        <option value="disponible" <?php if($availability == "disponible"){ echo "selected"; } ?>>Disponible</option>
                        <option value="occupé" <?php if($availability == "occupé"){ echo "selected"; } ?>>Occupe</option>
                        <option value="hors_ligne" <?php if($availability == "hors_ligne"){ echo "selected"; } ?>>Hors ligne</option>
                    </select>
                    <label>Disponibilite</label>
                </div>
            </div>

            <div class="modal-footer">
                <a href="#!" class="modal-close btn-flat">Annuler</a>
                <button type="submit" class="btn waves-effect waves-light">Enregistrer</button>
            </div>
        </form>
    </div>
<?php } ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    M.Modal.init(document.querySelectorAll('.modal'));
    M.FormSelect.init(document.querySelectorAll('select'));
    M.updateTextFields();
});
</script>

</body>
</html>
