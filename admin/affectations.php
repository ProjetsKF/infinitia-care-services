<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1){

    header("Location: " . app_url("login"));
    exit();

}

$admin_id = (int)$_SESSION["user_id"];
$selected_request_id = 0;
$selected_request = false;
$requests = array();
$candidates = array();
$candidate_skills = array();
$recommendations = array();

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

function format_date_fr($value)
{
    if($value === NULL || $value === ""){

        return "Non renseigne";

    }

    $timestamp = strtotime($value);

    if($timestamp === false){

        return "Non renseigne";

    }

    return date("d/m/Y", $timestamp);
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

function normalize_text($value)
{
    $value = strtolower((string)$value);

    $search = array(
        "é", "è", "ê", "ë",
        "à", "â", "ä",
        "î", "ï",
        "ô", "ö",
        "ù", "û", "ü",
        "ç"
    );

    $replace = array(
        "e", "e", "e", "e",
        "a", "a", "a",
        "i", "i",
        "o", "o",
        "u", "u", "u",
        "c"
    );

    return str_replace($search, $replace, $value);
}

function status_label($value)
{
    if($value == "en_attente"){

        return "En attente";

    }

    if($value == "validee"){

        return "Validée";

    }

    if($value == "attribuee" || $value == "affectee"){

        return "Affectée";

    }

    if($value == "en_cours"){

        return "En cours";

    }

    if($value == "terminee"){

        return "Terminée";

    }

    return display_value($value);
}

function availability_score($status, &$reasons)
{
    $normalized = normalize_text($status);

    if($normalized == "disponible"){

        $reasons[] = "Disponibilite optimale (+30)";
        return 30;

    }

    if($normalized == "occupe" || $normalized == "occupé"){

        $reasons[] = "Intervenant occupe mais mobilisable (+15)";
        return 15;

    }

    $reasons[] = "Intervenant hors ligne ou disponibilite faible (+0)";
    return 0;
}

function skill_level_score($level)
{
    $normalized = normalize_text($level);

    if($normalized == "expert"){

        return 30;

    }

    if($normalized == "avance"){

        return 25;

    }

    if($normalized == "intermediaire"){

        return 15;

    }

    if($normalized == "debutant"){

        return 8;

    }

    return 0;
}

function experience_score($years, &$reasons)
{
    if($years === NULL || $years === ""){

        $reasons[] = "Experience non renseignee (+0)";
        return 0;

    }

    $years = (int)$years;

    if($years >= 5){

        $reasons[] = "Experience solide de 5 ans ou plus (+20)";
        return 20;

    }

    if($years >= 3){

        $reasons[] = "Experience confirmee de 3 a 4 ans (+15)";
        return 15;

    }

    if($years >= 1){

        $reasons[] = "Experience de 1 a 2 ans (+10)";
        return 10;

    }

    $reasons[] = "Experience inferieure a 1 an (+5)";
    return 5;
}

function review_score($average, $count, &$reasons)
{
    if((int)$count <= 0){

        $reasons[] = "Aucune evaluation, score neutre (+5)";
        return 5;

    }

    $average = (float)$average;

    if($average >= 4.5){

        $reasons[] = "Excellente moyenne client (+10)";
        return 10;

    }

    if($average >= 4){

        $reasons[] = "Tres bonne moyenne client (+8)";
        return 8;

    }

    if($average >= 3){

        $reasons[] = "Moyenne client correcte (+5)";
        return 5;

    }

    $reasons[] = "Moyenne client faible (+2)";
    return 2;
}

function verification_score($status, &$reasons)
{
    $normalized = normalize_text($status);

    if($normalized == "verified" || $normalized == "verifie" || $normalized == "verifie"){

        $reasons[] = "Profil verifie (+10)";
        return 10;

    }

    if($normalized == "pending" || $normalized == "en_attente"){

        $reasons[] = "Profil en attente de verification (+4)";
        return 4;

    }

    $reasons[] = "Profil non verifie (+0)";
    return 0;
}

function redirect_affectations($request_id)
{
    if($request_id > 0){

        header("Location: " . app_url_with_query("admin/affectations", array("request_id" => (int)$request_id)));
        exit();

    }

    header("Location: " . app_url("admin/affectations"));
    exit();
}

function rollback_affectation($conn, $message, $request_id)
{
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);
    $_SESSION["error"] = $message;
    redirect_affectations($request_id);
}

function affectations_pagination_url($page_number)
{
    $params = $_GET;
    $params["page"] = (int)$page_number;

    return app_url_with_query("admin/affectations", $params);
}

if(isset($_GET["request_id"])){

    $selected_request_id = (int)$_GET["request_id"];

}

$limit = 50;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if($page < 1){

    $page = 1;

}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

    if($action == "assign_candidate"){

        $request_id = isset($_POST["request_id"])
            ? (int)$_POST["request_id"]
            : 0;

        $candidate_id = isset($_POST["candidate_id"])
            ? (int)$_POST["candidate_id"]
            : 0;

        if($request_id <= 0 || $candidate_id <= 0){

            $_SESSION["error"] = "Demande ou intervenant invalide.";
            redirect_affectations($request_id);

        }

        if(!mysqli_autocommit($conn, false)){

            $_SESSION["error"] = "Impossible de démarrer l'affectation.";
            redirect_affectations($request_id);

        }

        $existing_request_id = 0;

        $sql = "
        SELECT id
        FROM service_requests
        WHERE id = ?
        AND status = 'validee'
        LIMIT 1
        FOR UPDATE
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            rollback_affectation($conn, "Une erreur est survenue pendant la vérification de la demande.", $request_id);

        }

        mysqli_stmt_bind_param($stmt, "i", $request_id);
        if(!mysqli_stmt_execute($stmt)){

            mysqli_stmt_close($stmt);
            rollback_affectation($conn, "Une erreur est survenue pendant la vérification de la demande.", $request_id);

        }

        mysqli_stmt_bind_result($stmt, $existing_request_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if($existing_request_id <= 0){

            rollback_affectation($conn, "Cette demande n'est pas disponible pour l'affectation.", 0);

        }

        $existing_candidate_id = 0;

        $sql = "
        SELECT c.id
        FROM candidates c
        INNER JOIN users u
        ON u.id = c.user_id
        WHERE c.id = ?
        AND u.role_id = 3
        AND u.status = 'active'
        AND c.verification_status = 'verifie'
        LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            rollback_affectation($conn, "Une erreur est survenue pendant la vérification de l'intervenant.", $request_id);

        }

        mysqli_stmt_bind_param($stmt, "i", $candidate_id);
        if(!mysqli_stmt_execute($stmt)){

            mysqli_stmt_close($stmt);
            rollback_affectation($conn, "Une erreur est survenue pendant la vérification de l'intervenant.", $request_id);

        }

        mysqli_stmt_bind_result($stmt, $existing_candidate_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if($existing_candidate_id <= 0){

            rollback_affectation($conn, "Cet intervenant n'est pas actif ou son profil n'est pas vérifié.", $request_id);

        }

        $mission_id = 0;

        $sql = "
        SELECT id
        FROM missions
        WHERE service_request_id = ?
        LIMIT 1
        FOR UPDATE
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            rollback_affectation($conn, "Une erreur est survenue pendant la vérification des missions existantes.", $request_id);

        }

        mysqli_stmt_bind_param($stmt, "i", $request_id);
        if(!mysqli_stmt_execute($stmt)){

            mysqli_stmt_close($stmt);
            rollback_affectation($conn, "Une erreur est survenue pendant la vérification des missions existantes.", $request_id);

        }

        mysqli_stmt_bind_result($stmt, $mission_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if($mission_id > 0){

            rollback_affectation($conn, "Cette demande est déjà affectée.", $request_id);

        }

        $sql = "
        INSERT INTO missions(
            service_request_id,
            candidate_id,
            assigned_by,
            mission_status,
            created_at,
            updated_at
        )
        VALUES(?, ?, ?, 'affectee', NOW(), NOW())
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            rollback_affectation($conn, "Une erreur est survenue pendant la création de la mission.", $request_id);

        }

        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $request_id,
            $candidate_id,
            $admin_id
        );

        if(!mysqli_stmt_execute($stmt)){

            mysqli_stmt_close($stmt);
            rollback_affectation($conn, "Erreur lors de la création de la mission.", $request_id);

        }

        mysqli_stmt_close($stmt);

        $sql = "
        UPDATE service_requests
        SET status = 'attribuee'
        WHERE id = ?
        AND status = 'validee'
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            rollback_affectation($conn, "Une erreur est survenue pendant la mise à jour de la demande.", $request_id);

        }

        mysqli_stmt_bind_param($stmt, "i", $request_id);

        if(!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) != 1){

            mysqli_stmt_close($stmt);
            rollback_affectation($conn, "La demande n'a pas pu être marquée comme affectée.", $request_id);

        }

        mysqli_stmt_close($stmt);

        if(!mysqli_commit($conn)){

            rollback_affectation($conn, "L'affectation n'a pas pu être enregistrée.", $request_id);

        }

        mysqli_autocommit($conn, true);
        $_SESSION["success"] = "Intervenant affecté avec succès.";
        redirect_affectations(0);

    }

}

$total_requests = 0;

$sql = "
SELECT COUNT(*) AS total
FROM service_requests sr
WHERE sr.status = 'validee'
AND NOT EXISTS(
    SELECT 1
    FROM missions m
    WHERE m.service_request_id = sr.id
)
";

$result = mysqli_query($conn, $sql);

if($result){

    $row = mysqli_fetch_assoc($result);

    if($row && isset($row["total"])){

        $total_requests = (int)$row["total"];

    }

    mysqli_free_result($result);

}

$total_pages = (int)ceil($total_requests / $limit);

if($total_pages > 0 && $page > $total_pages){

    $page = $total_pages;

}

if($total_pages < 1){

    $page = 1;

}

$offset = ($page - 1) * $limit;

$sql = "
SELECT
    sr.id,
    sr.title,
    sr.description,
    sr.location,
    sr.service_date,
    sr.budget,
    sr.urgency_level,
    sr.status,
    sr.category_id,
    u.first_name,
    u.last_name,
    sc.name AS category_name
FROM service_requests sr
INNER JOIN clients c
ON c.id = sr.client_id
INNER JOIN users u
ON u.id = c.user_id
LEFT JOIN service_categories sc
ON sc.id = sr.category_id
WHERE sr.status = 'validee'
AND NOT EXISTS(
    SELECT 1
    FROM missions m
    WHERE m.service_request_id = sr.id
)
ORDER BY sr.created_at DESC
LIMIT ?
OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt){

    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $requests[] = $row;

            if($selected_request_id > 0 && (int)$row["id"] == $selected_request_id){

                $selected_request = $row;

            }

        }

        mysqli_free_result($result);

    }

    mysqli_stmt_close($stmt);

}

if($selected_request_id > 0 && !$selected_request){

    $sql = "
    SELECT
        sr.id,
        sr.title,
        sr.description,
        sr.location,
        sr.service_date,
        sr.budget,
        sr.urgency_level,
        sr.status,
        sr.category_id,
        u.first_name,
        u.last_name,
        sc.name AS category_name
    FROM service_requests sr
    INNER JOIN clients c
    ON c.id = sr.client_id
    INNER JOIN users u
    ON u.id = c.user_id
    LEFT JOIN service_categories sc
    ON sc.id = sr.category_id
    WHERE sr.id = ?
    AND sr.status = 'validee'
    AND NOT EXISTS(
        SELECT 1
        FROM missions m
        WHERE m.service_request_id = sr.id
    )
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if($stmt){

        mysqli_stmt_bind_param($stmt, "i", $selected_request_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if($result){

            $selected_request = mysqli_fetch_assoc($result);
            mysqli_free_result($result);

        }

        mysqli_stmt_close($stmt);

    }

}

if(!$selected_request && $selected_request_id > 0){

    $unavailable_status = "";
    $sql = "SELECT status FROM service_requests WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);

    if($stmt){

        mysqli_stmt_bind_param($stmt, "i", $selected_request_id);

        if(mysqli_stmt_execute($stmt)){

            mysqli_stmt_bind_result($stmt, $unavailable_status);
            mysqli_stmt_fetch($stmt);

        }

        mysqli_stmt_close($stmt);

    }

    if($unavailable_status == "en_attente"){

        $_SESSION["error"] = "Cette demande doit d'abord être validée avant son affectation.";

    }else{

        $_SESSION["error"] = "Cette demande n'est pas disponible pour l'affectation.";

    }

    redirect_affectations(0);

}

if($selected_request){

    $sql = "
    SELECT
        c.id AS candidate_id,
        c.city,
        c.experience_years,
        c.availability_status,
        c.verification_status,
        u.first_name,
        u.last_name,
        u.profile_photo,
        AVG(sr.note_generale) AS average_rating,
        COUNT(sr.id) AS review_count
    FROM candidates c
    INNER JOIN users u
    ON u.id = c.user_id
    LEFT JOIN service_reviews sr
    ON sr.candidate_id = c.id
    WHERE u.role_id = 3
    AND u.status = 'active'
    GROUP BY
        c.id,
        c.city,
        c.experience_years,
        c.availability_status,
        c.verification_status,
        u.first_name,
        u.last_name,
        u.profile_photo
    ";

    $result = mysqli_query($conn, $sql);

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $candidates[] = $row;
            $candidate_skills[(int)$row["candidate_id"]] = array();

        }

        mysqli_free_result($result);

    }

    $skill_category_column_exists = false;
    $sql = "SHOW COLUMNS FROM candidate_skills LIKE 'service_category_id'";
    $result = mysqli_query($conn, $sql);

    if($result){

        $skill_category_column_exists = mysqli_num_rows($result) > 0;
        mysqli_free_result($result);

    }

    $skill_category_select = $skill_category_column_exists
        ? "service_category_id"
        : "NULL AS service_category_id";

    $sql = "
    SELECT
        candidate_id,
        skill_name,
        level,
        " . $skill_category_select . "
    FROM candidate_skills
    WHERE is_active = 1
    ";

    $result = mysqli_query($conn, $sql);

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $candidate_id = isset($row["candidate_id"]) ? (int)$row["candidate_id"] : 0;

            if($candidate_id > 0 && isset($candidate_skills[$candidate_id])){

                $candidate_skills[$candidate_id][] = $row;

            }

        }

        mysqli_free_result($result);

    }

    $selected_category_id = isset($selected_request["category_id"])
        ? (int)$selected_request["category_id"]
        : 0;

    foreach($candidates as $candidate){

        $candidate_id = (int)$candidate["candidate_id"];
        $reasons = array();
        $score = 0;
        $matched_skill = "Aucune compétence correspondante";
        $skill_score = 0;
        $has_matching_skill = false;
        $skills = isset($candidate_skills[$candidate_id]) ? $candidate_skills[$candidate_id] : array();

        $score += availability_score($candidate["availability_status"], $reasons);

        foreach($skills as $skill){

            $skill_name = isset($skill["skill_name"]) ? $skill["skill_name"] : "";
            $skill_level = isset($skill["level"]) ? $skill["level"] : "";
            $skill_category_id = isset($skill["service_category_id"])
                ? (int)$skill["service_category_id"]
                : 0;

            if(
                $selected_category_id > 0
                && $skill_category_id > 0
                && $skill_category_id === $selected_category_id
            ){

                $candidate_skill_score = skill_level_score($skill_level);

                if(!$has_matching_skill || $candidate_skill_score > $skill_score){

                    $skill_score = $candidate_skill_score;
                    $matched_skill = $skill_name . " (" . display_value($skill_level) . ")";

                }

                $has_matching_skill = true;

            }

        }

        if($has_matching_skill){

            $reasons[] = "Competence correspondante : " . $matched_skill . " (+" . $skill_score . ")";

        }else{

            $reasons[] = "Aucune compétence active ne correspond à la catégorie du service (+0)";

        }

        $score += $skill_score;
        $score += experience_score($candidate["experience_years"], $reasons);
        $score += review_score($candidate["average_rating"], $candidate["review_count"], $reasons);
        $score += verification_score($candidate["verification_status"], $reasons);

        if($score > 100){

            $score = 100;

        }

        $candidate["recommendation_score"] = $score;
        $candidate["matched_skill"] = $matched_skill;
        $candidate["recommendation_reasons"] = $reasons;

        $recommendations[] = $candidate;

    }

    usort($recommendations, "sort_recommendations");

}

function sort_recommendations($a, $b)
{
    $score_a = isset($a["recommendation_score"]) ? (int)$a["recommendation_score"] : 0;
    $score_b = isset($b["recommendation_score"]) ? (int)$b["recommendation_score"] : 0;

    if($score_a == $score_b){

        return 0;

    }

    return ($score_a > $score_b) ? -1 : 1;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(dirname(__DIR__) . "/includes/pwa-head.php"); ?>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Affectations | INFINITIA</title>

    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

    <style>
        .recommendation-card{
            border-radius:14px;
        }

        .candidate-photo{
            width:72px;
            height:72px;
            border-radius:50%;
            object-fit:cover;
        }

        .score-bar{
            background:#eeeeee;
            height:10px;
            border-radius:20px;
            overflow:hidden;
            margin-top:8px;
        }

        .score-fill{
            background:linear-gradient(45deg,#081f78,#e83e8c);
            height:10px;
        }

        .reason-list{
            margin-left:18px;
        }

        .table-pagination{
            margin-top:25px;
            margin-bottom:20px;
            text-align:center;
        }
    </style>

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "affectations";

    include("menuadmin.php");

    ?>

    <div class="main-content">

        <div class="topbar">
            <div>
                <div class="page-title">Affectations</div>
                <div class="welcome-text">
                    Moteur interne de recommandation des intervenants.
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

        <div class="table-card">
            <div class="table-title">Demandes clients non affectees</div>

            <table class="highlight responsive-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Service demande</th>
                        <th>Description</th>
                        <th>Lieu</th>
                        <th>Date prevue</th>
                        <th>Budget</th>
                        <th>Urgence</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($requests) > 0){ ?>
                        <?php foreach($requests as $request){ ?>
                            <?php
                            $request_id = (int)$request["id"];
                            $client_name = trim($request["first_name"] . " " . $request["last_name"]);
                            ?>
                            <tr>
                                <td>#<?php echo str_pad($request_id, 3, "0", STR_PAD_LEFT); ?></td>
                                <td><?php echo safe_text(display_value($client_name)); ?></td>
                                <td><?php echo safe_text(display_value($request["title"])); ?></td>
                                <td><?php echo safe_text(display_value($request["description"])); ?></td>
                                <td><?php echo safe_text(display_value($request["location"])); ?></td>
                                <td><?php echo safe_text(format_date_fr($request["service_date"])); ?></td>
                                <td>
                                    <?php echo safe_text(number_format((float)$request["budget"], 2)); ?>
                                </td>
                                <td><?php echo safe_text(display_value($request["urgency_level"])); ?></td>
                                <td>
                                    <span class="status pending">
                                        <?php echo safe_text(status_label($request["status"])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo app_url_with_query_html("admin/affectations", array("request_id" => $request_id)); ?>"
                                       class="btn-small waves-effect waves-light">
                                        Recommander / Affecter
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php }else{ ?>
                        <tr>
                            <td colspan="10" class="center-align">
                                Aucune demande en attente d'affectation.
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
                                <a href="<?php echo safe_text(affectations_pagination_url($page - 1)); ?>">Precedent</a>
                            </li>
                        <?php }else{ ?>
                            <li class="disabled"><a href="#!">Precedent</a></li>
                        <?php } ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if($start_page > 1){
                        ?>
                            <li class="waves-effect"><a href="<?php echo safe_text(affectations_pagination_url(1)); ?>">1</a></li>
                            <?php if($start_page > 2){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                        <?php } ?>

                        <?php for($page_number = $start_page; $page_number <= $end_page; $page_number++){ ?>
                            <?php if($page_number == $page){ ?>
                                <li class="active"><a href="#!"><?php echo (int)$page_number; ?></a></li>
                            <?php }else{ ?>
                                <li class="waves-effect">
                                    <a href="<?php echo safe_text(affectations_pagination_url($page_number)); ?>"><?php echo (int)$page_number; ?></a>
                                </li>
                            <?php } ?>
                        <?php } ?>

                        <?php if($end_page < $total_pages){ ?>
                            <?php if($end_page < $total_pages - 1){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                            <li class="waves-effect"><a href="<?php echo safe_text(affectations_pagination_url($total_pages)); ?>"><?php echo (int)$total_pages; ?></a></li>
                        <?php } ?>

                        <?php if($page < $total_pages){ ?>
                            <li class="waves-effect">
                                <a href="<?php echo safe_text(affectations_pagination_url($page + 1)); ?>">Suivant</a>
                            </li>
                        <?php }else{ ?>
                            <li class="disabled"><a href="#!">Suivant</a></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>
        </div>

        <br>
        <?php if($selected_request){ ?>

            <div class="table-card">
                <div class="table-title">
                    Recommandations pour la demande #<?php echo str_pad((int)$selected_request["id"], 3, "0", STR_PAD_LEFT); ?>
                </div>

                <p>
                    <strong>Service :</strong>
                    <?php echo safe_text(display_value($selected_request["title"])); ?>
                    |
                    <strong>Categorie :</strong>
                    <?php echo safe_text(display_value($selected_request["category_name"])); ?>
                </p>

                <?php if(count($recommendations) > 0){ ?>
                    <div class="row">
                        <?php foreach($recommendations as $index => $candidate){ ?>
                            <?php
                            $candidate_id = (int)$candidate["candidate_id"];
                            $full_name = trim($candidate["first_name"] . " " . $candidate["last_name"]);
                            $score = isset($candidate["recommendation_score"]) ? (int)$candidate["recommendation_score"] : 0;
                            $average_rating = isset($candidate["average_rating"]) && $candidate["average_rating"] !== NULL
                                ? number_format((float)$candidate["average_rating"], 1)
                                : "Aucune";
                            ?>
                            <div class="col s12 l6">
                                <div class="card recommendation-card">
                                    <div class="card-content">
                                        <div style="display:flex; gap:16px; align-items:center;">
                                            <img class="candidate-photo"
                                                 src="<?php echo safe_text(profile_photo_path($candidate["profile_photo"])); ?>"
                                                 alt="Photo intervenant">
                                            <div>
                                                <span class="card-title" style="font-weight:700;">
                                                    <?php echo safe_text(display_value($full_name)); ?>
                                                </span>
                                                <?php if($index < 5){ ?>
                                                    <span class="new badge green" data-badge-caption="">Top <?php echo (int)($index + 1); ?></span>
                                                <?php } ?>
                                            </div>
                                        </div>

                                        <div style="margin-top:18px;">
                                            <p><strong>Ville :</strong> <?php echo safe_text(display_value($candidate["city"])); ?></p>
                                            <p><strong>Disponibilite :</strong> <?php echo safe_text(display_value($candidate["availability_status"])); ?></p>
                                            <p><strong>Verification :</strong> <?php echo safe_text(display_value($candidate["verification_status"])); ?></p>
                                            <p><strong>Experience :</strong> <?php echo safe_text(display_value($candidate["experience_years"])); ?> an(s)</p>
                                            <p><strong>Competence :</strong> <?php echo safe_text(display_value($candidate["matched_skill"])); ?></p>
                                            <p><strong>Moyenne evaluations :</strong> <?php echo safe_text($average_rating); ?></p>
                                            <p><strong>Score :</strong> <?php echo (int)$score; ?>%</p>
                                            <div class="score-bar">
                                                <div class="score-fill" style="width:<?php echo (int)$score; ?>%;"></div>
                                            </div>

                                            <p style="margin-top:16px;"><strong>Raisons :</strong></p>
                                            <ul class="reason-list">
                                                <?php foreach($candidate["recommendation_reasons"] as $reason){ ?>
                                                    <li><?php echo safe_text($reason); ?></li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="card-action">
                                        <form action="<?php echo app_url_html("admin/affectations"); ?>" method="POST">
                                            <input type="hidden" name="action" value="assign_candidate">
                                            <input type="hidden" name="request_id" value="<?php echo (int)$selected_request["id"]; ?>">
                                            <input type="hidden" name="candidate_id" value="<?php echo $candidate_id; ?>">
                                            <button type="submit"
                                                    class="btn waves-effect waves-light"
                                                    onclick="return confirm('Affecter cet intervenant a la demande ?');">
                                                Affecter cet intervenant
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php }else{ ?>
                    <div class="card-panel orange lighten-5">
                        Aucun intervenant n'est disponible pour cette demande.
                    </div>
                <?php } ?>
            </div>

        <?php } ?>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

</body>
</html>
