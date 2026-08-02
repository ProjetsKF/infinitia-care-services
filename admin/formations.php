<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1){

    header("Location: " . app_url("login"));
    exit();

}

$admin_id = (int)$_SESSION["user_id"];

if(!isset($_SESSION["csrf_token"]) || $_SESSION["csrf_token"] == ""){

    $csrf_bytes = openssl_random_pseudo_bytes(32);

    if($csrf_bytes === false){

        $csrf_bytes = uniqid((string)mt_rand(), true);

    }

    $_SESSION["csrf_token"] = bin2hex($csrf_bytes);
}

$csrf_token = $_SESSION["csrf_token"];

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

    return date("d/m/Y H:i", $timestamp);
}

function extract_youtube_video_id($youtube_url)
{
    $youtube_url = trim((string)$youtube_url);

    if($youtube_url == ""){

        return "";

    }

    $parts = parse_url($youtube_url);

    if($parts === false || !isset($parts["host"])){

        return "";

    }

    $scheme = isset($parts["scheme"]) ? strtolower($parts["scheme"]) : "";
    $host = strtolower($parts["host"]);
    $path = isset($parts["path"]) ? trim($parts["path"], "/") : "";
    $video_id = "";

    if($scheme != "http" && $scheme != "https"){

        return "";

    }

    if($host == "youtu.be" || $host == "www.youtu.be"){

        $path_parts = explode("/", $path);
        $video_id = isset($path_parts[0]) ? $path_parts[0] : "";

    }elseif(
        $host == "youtube.com" ||
        $host == "www.youtube.com" ||
        $host == "m.youtube.com"
    ){

        if($path == "watch"){

            $query = array();

            if(isset($parts["query"])){

                parse_str($parts["query"], $query);

            }

            $video_id = isset($query["v"]) ? $query["v"] : "";

        }else{

            $path_parts = explode("/", $path);
            $path_type = isset($path_parts[0]) ? $path_parts[0] : "";

            if($path_type == "embed" || $path_type == "shorts"){

                $video_id = isset($path_parts[1]) ? $path_parts[1] : "";

            }

        }

    }

    $video_id = rawurldecode($video_id);

    if(!preg_match("/^[A-Za-z0-9_-]{11}$/", $video_id)){

        return "";

    }

    return $video_id;
}

function youtube_embed_url($youtube_url)
{
    $video_id = extract_youtube_video_id($youtube_url);

    if($video_id == ""){

        return "";

    }

    return "https://www.youtube.com/embed/" . $video_id;
}

function training_status_label($status)
{
    if($status == "en_attente"){

        return "En attente";

    }

    if($status == "en_cours"){

        return "En cours";

    }

    if($status == "terminee"){

        return "Terminée";

    }

    return "Inactive";
}

function training_status_badge_class($status)
{
    if($status == "en_attente"){

        return "orange";

    }

    if($status == "en_cours"){

        return "blue";

    }

    if($status == "terminee"){

        return "green";

    }

    return "grey";
}

function redirect_formations()
{
    header("Location: " . app_url("admin/formations"));
    exit();
}

function formations_pagination_url($page_number)
{
    $params = $_GET;
    $params["page"] = (int)$page_number;

    return app_url_with_query("admin/formations", $params);
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

function count_trainings_search($conn, $search_like)
{
    $total = 0;
    $sql = "
    SELECT COUNT(*) AS total
    FROM trainings t
    WHERE (
        t.title LIKE ?
        OR t.description LIKE ?
        OR t.duration LIKE ?
    )
    ";
    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        return $total;

    }

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $search_like,
        $search_like,
        $search_like
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$total;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

    $posted_csrf_token = isset($_POST["csrf_token"])
        ? $_POST["csrf_token"]
        : "";

    if(
        $posted_csrf_token == "" ||
        !hash_equals($_SESSION["csrf_token"], $posted_csrf_token)
    ){

        $_SESSION["error"] = "La session du formulaire a expire. Veuillez reessayer.";
        redirect_formations();

    }

    if($action == "add_training" || $action == "update_training"){

        $title = isset($_POST["title"])
            ? trim($_POST["title"])
            : "";

        $description = isset($_POST["description"])
            ? trim($_POST["description"])
            : "";

        $youtube_url = isset($_POST["youtube_url"])
            ? trim($_POST["youtube_url"])
            : "";

        $duration = isset($_POST["duration"])
            ? trim($_POST["duration"])
            : "";

        if($title == "" || $youtube_url == ""){

            $_SESSION["error"] = "Le titre et le lien YouTube sont obligatoires.";
            redirect_formations();

        }

        $youtube_embed_url = youtube_embed_url($youtube_url);

        if($youtube_embed_url == ""){

            $_SESSION["error"] = "Veuillez saisir un lien YouTube valide.";
            redirect_formations();

        }

        if($duration == ""){

            $duration = NULL;

        }

        if($description == ""){

            $description = NULL;

        }

        if($action == "add_training"){

            $sql = "
            INSERT INTO trainings(
                title,
                description,
                youtube_url,
                duration
            )
            VALUES(?, ?, ?, ?)
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $title,
                $description,
                $youtube_url,
                $duration
            );

            if(mysqli_stmt_execute($stmt)){

                $_SESSION["success"] = "Formation ajoutee avec succes.";

            }else{

                $_SESSION["error"] = "Erreur lors de l'ajout de la formation.";

            }

            mysqli_stmt_close($stmt);
            redirect_formations();

        }

        $training_id = isset($_POST["training_id"])
            ? (int)$_POST["training_id"]
            : 0;

        if($training_id <= 0){

            $_SESSION["error"] = "Formation introuvable.";
            redirect_formations();

        }

        $sql = "
        UPDATE trainings
        SET
            title = ?,
            description = ?,
            youtube_url = ?,
            duration = ?
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $title,
            $description,
            $youtube_url,
            $duration,
            $training_id
        );

        if(mysqli_stmt_execute($stmt)){

            $_SESSION["success"] = "Formation modifiee avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors de la modification de la formation.";

        }

        mysqli_stmt_close($stmt);
        redirect_formations();

    }

    if($action == "delete_training"){

        $training_id = isset($_POST["training_id"])
            ? (int)$_POST["training_id"]
            : 0;

        if($training_id <= 0){

            $_SESSION["error"] = "Formation introuvable.";
            redirect_formations();

        }

        $assigned_total = 0;

        $sql = "
        SELECT COUNT(*) AS total
        FROM candidate_trainings
        WHERE training_id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $training_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $assigned_total);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if($assigned_total > 0){

            $_SESSION["error"] = "Cette formation est deja attribuee et ne peut pas etre supprimee.";
            redirect_formations();

        }

        $sql = "
        DELETE FROM trainings
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $training_id);

        if(mysqli_stmt_execute($stmt)){

            $_SESSION["success"] = "Formation supprimee avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors de la suppression de la formation.";

        }

        mysqli_stmt_close($stmt);
        redirect_formations();

    }

    if($action == "deactivate_assignment"){

        $candidate_training_id = isset($_POST["candidate_training_id"])
            ? (int)$_POST["candidate_training_id"]
            : 0;

        if($candidate_training_id <= 0){

            $_SESSION["error"] = "Attribution de formation invalide.";
            redirect_formations();

        }

        $sql = "
        UPDATE candidate_trainings
        SET status = 'inactive'
        WHERE id = ?
        AND status IN ('en_attente', 'en_cours')
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            $_SESSION["error"] = "Erreur lors de la desactivation de l'attribution.";
            redirect_formations();

        }

        mysqli_stmt_bind_param($stmt, "i", $candidate_training_id);
        mysqli_stmt_execute($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if($affected_rows == 1){

            $_SESSION["success"] = "L'attribution a ete desactivee.";

        }else{

            $_SESSION["error"] = "Cette attribution ne peut pas etre desactivee.";

        }

        redirect_formations();

    }

    if($action == "assign_training"){

        $training_id = isset($_POST["training_id"])
            ? (int)$_POST["training_id"]
            : 0;

        $selected_candidates = array();

        if(isset($_POST["candidates"]) && is_array($_POST["candidates"])){

            $selected_candidates = $_POST["candidates"];

        }

        if($training_id <= 0){

            $_SESSION["error"] = "Formation introuvable.";
            redirect_formations();

        }

        if(count($selected_candidates) == 0){

            $_SESSION["error"] = "Veuillez selectionner au moins un intervenant.";
            redirect_formations();

        }

        $inserted = 0;
        $skipped = 0;

        foreach($selected_candidates as $candidate_id_raw){

            $candidate_id = (int)$candidate_id_raw;

            if($candidate_id <= 0){

                continue;

            }

            $exists_id = 0;

            $sql = "
            SELECT id
            FROM candidate_trainings
            WHERE candidate_id = ?
            AND training_id = ?
            LIMIT 1
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $candidate_id,
                $training_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $exists_id);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if($exists_id > 0){

                $skipped++;
                continue;

            }

            $sql = "
            INSERT INTO candidate_trainings(
                candidate_id,
                training_id,
                status,
                assigned_by
            )
            VALUES(?, ?, 'en_attente', ?)
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param(
                $stmt,
                "iii",
                $candidate_id,
                $training_id,
                $admin_id
            );

            if(mysqli_stmt_execute($stmt)){

                $inserted++;

            }

            mysqli_stmt_close($stmt);

        }

        $_SESSION["success"] =
            "Attribution terminee : " .
            $inserted .
            " ajout(s), " .
            $skipped .
            " doublon(s) ignore(s).";

        redirect_formations();

    }

}

$stats = array(
    "total_trainings" => count_query($conn, "SELECT COUNT(*) AS total FROM trainings"),
    "assigned_trainings" => count_query($conn, "SELECT COUNT(*) AS total FROM candidate_trainings"),
    "pending_trainings" => count_query($conn, "SELECT COUNT(*) AS total FROM candidate_trainings WHERE status = 'en_attente'"),
    "in_progress_trainings" => count_query($conn, "SELECT COUNT(*) AS total FROM candidate_trainings WHERE status = 'en_cours'"),
    "completed_trainings" => count_query($conn, "SELECT COUNT(*) AS total FROM candidate_trainings WHERE status = 'terminee'"),
    "candidates_with_training" => count_query($conn, "SELECT COUNT(DISTINCT candidate_id) AS total FROM candidate_trainings")
);

$trainings = array();
$limit = 50;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";
$search = substr($search, 0, 100);
$search_like = "%" . $search . "%";
$training_search_sql = "";

if($page < 1){

    $page = 1;

}

if($search != ""){

    $total_trainings = count_trainings_search($conn, $search_like);
    $training_search_sql = "
    WHERE (
        t.title LIKE ?
        OR t.description LIKE ?
        OR t.duration LIKE ?
    )";

}else{

    $total_trainings = count_query($conn, "SELECT COUNT(*) AS total FROM trainings");

}

$total_pages = (int)ceil($total_trainings / $limit);

if($total_pages > 0 && $page > $total_pages){

    $page = $total_pages;

}

if($total_pages < 1){

    $page = 1;

}

$offset = ($page - 1) * $limit;

$sql = "
SELECT
    t.id,
    t.title,
    t.description,
    t.youtube_url,
    t.duration,
    t.created_at,
    COUNT(ct.id) AS assigned_total,
    SUM(CASE WHEN ct.status = 'en_attente' THEN 1 ELSE 0 END) AS pending_total,
    SUM(CASE WHEN ct.status = 'en_cours' THEN 1 ELSE 0 END) AS in_progress_total,
    SUM(CASE WHEN ct.status = 'terminee' THEN 1 ELSE 0 END) AS completed_total
FROM trainings t
LEFT JOIN candidate_trainings ct
ON ct.training_id = t.id
" . $training_search_sql . "
GROUP BY
    t.id,
    t.title,
    t.description,
    t.youtube_url,
    t.duration,
    t.created_at
ORDER BY t.created_at DESC
LIMIT ?
OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt){

    if($search != ""){

        mysqli_stmt_bind_param(
            $stmt,
            "sssii",
            $search_like,
            $search_like,
            $search_like,
            $limit,
            $offset
        );

    }else{

        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);

    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result(
        $stmt,
        $training_id,
        $training_title,
        $training_description,
        $training_youtube_url,
        $training_duration,
        $training_created_at,
        $training_assigned_total,
        $training_pending_total,
        $training_in_progress_total,
        $training_completed_total
    );

    while(mysqli_stmt_fetch($stmt)){

        $trainings[] = array(
            "id" => $training_id,
            "title" => $training_title,
            "description" => $training_description,
            "youtube_url" => $training_youtube_url,
            "duration" => $training_duration,
            "created_at" => $training_created_at,
            "assigned_total" => $training_assigned_total,
            "pending_total" => $training_pending_total,
            "in_progress_total" => $training_in_progress_total,
            "completed_total" => $training_completed_total
        );

    }

    mysqli_stmt_close($stmt);

}

$candidates = array();

$sql = "
SELECT
    c.id AS candidate_id,
    c.verification_status,
    c.availability_status,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.status AS user_status
FROM candidates c
INNER JOIN users u
ON u.id = c.user_id
WHERE u.role_id = 3
AND u.status = 'active'
AND c.verification_status = 'verifie'
AND c.availability_status = 'disponible'
ORDER BY u.first_name ASC, u.last_name ASC
";

$result = mysqli_query($conn, $sql);

if($result){

    while($row = mysqli_fetch_assoc($result)){

        $candidates[] = $row;

    }

    mysqli_free_result($result);

}

$training_assignments = array();

$sql = "
SELECT
    ct.training_id,
    ct.id,
    ct.status,
    ct.created_at,
    u.first_name,
    u.last_name,
    u.email
FROM candidate_trainings ct
INNER JOIN candidates c
ON c.id = ct.candidate_id
INNER JOIN users u
ON u.id = c.user_id
ORDER BY ct.created_at DESC, ct.id DESC
";

$result = mysqli_query($conn, $sql);

if($result){

    while($row = mysqli_fetch_assoc($result)){

        $assignment_training_id = isset($row["training_id"])
            ? (int)$row["training_id"]
            : 0;

        if(!isset($training_assignments[$assignment_training_id])){

            $training_assignments[$assignment_training_id] = array();

        }

        $training_assignments[$assignment_training_id][] = $row;

    }

    mysqli_free_result($result);

}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Formations | INFINITIA</title>

    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

    <style>

        .admin-summary-card{
            background:#ffffff;
            border-radius:14px;
            padding:18px;
            box-shadow:0 8px 22px rgba(0,0,0,.08);
            min-height:120px;
        }

        .admin-summary-card h5{
            color:#2f3b55;
            font-size:15px;
            font-weight:600;
            margin:12px 0 6px;
        }

        .admin-summary-card h3{
            color:#081f78;
            font-size:30px;
            font-weight:800;
            margin:0;
        }

        .training-modal-list{
            max-height:320px;
            overflow:auto;
            border:1px solid #eeeeee;
            border-radius:10px;
            padding:12px;
        }

        .table-pagination{
            margin-top:25px;
            margin-bottom:20px;
            text-align:center;
        }

        .training-search-box{
            background:#ffffff;
            border-radius:12px;
            padding:15px;
            margin-bottom:20px;
            box-shadow:0 6px 18px rgba(0,0,0,.06);
        }

        .training-search-form{
            display:flex;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
        }

        .training-search-form .input-field{
            flex:1;
            min-width:250px;
            margin:0;
        }

        .training-result-summary{
            color:#616161;
            margin:0 0 15px;
        }

        .candidate-training-item{
            padding:8px 4px;
        }

        .candidate-no-result{
            display:none;
            color:#757575;
            padding:12px;
            text-align:center;
        }

        .training-video-wrapper{
            position:relative;
            width:100%;
            padding-top:56.25%;
            margin-top:18px;
            overflow:hidden;
            border-radius:12px;
            background:#000000;
        }

        .training-video-wrapper iframe{
            position:absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            border:0;
        }

        .training-status-badge{
            border-radius:12px;
            color:#ffffff;
            display:inline-block;
            font-size:12px;
            font-weight:600;
            line-height:1;
            padding:7px 10px;
        }

        @media(max-width:700px){
            .training-search-form{
                flex-direction:column;
                align-items:stretch;
            }

            .training-search-form .btn,
            .training-search-form .btn-flat{
                width:100%;
            }
        }

    </style>

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "formations";

    include("menuadmin.php");

    ?>

    <div class="main-content">

        <div class="topbar">

            <div>

                <div class="page-title">
                    Formations
                </div>

                <div class="welcome-text">
                    Gere les formations et leurs attributions aux intervenants.
                </div>

            </div>

            <a href="#modalAddTraining"
               class="btn waves-effect waves-light modal-trigger">
                <i class="material-icons left">add</i>
                Nouvelle formation
            </a>

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
                    <div class="card-icon blue-gradient">
                        <i class="material-icons">school</i>
                    </div>
                    <h5>Total formations</h5>
                    <h3><?php echo (int)$stats["total_trainings"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon pink-gradient">
                        <i class="material-icons">assignment_ind</i>
                    </div>
                    <h5>Formations attribuees</h5>
                    <h3><?php echo (int)$stats["assigned_trainings"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient">
                        <i class="material-icons">schedule</i>
                    </div>
                    <h5>En attente</h5>
                    <h3><?php echo (int)$stats["pending_trainings"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient">
                        <i class="material-icons">play_circle</i>
                    </div>
                    <h5>En cours</h5>
                    <h3><?php echo (int)$stats["in_progress_trainings"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient">
                        <i class="material-icons">check_circle</i>
                    </div>
                    <h5>Terminées</h5>
                    <h3><?php echo (int)$stats["completed_trainings"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient">
                        <i class="material-icons">groups</i>
                    </div>
                    <h5>Intervenants formes</h5>
                    <h3><?php echo (int)$stats["candidates_with_training"]; ?></h3>
                </div>
            </div>

        </div>

        <div class="training-search-box">
            <form method="GET" action="<?php echo app_url_html("admin/formations"); ?>" class="training-search-form">
                <div class="input-field">
                    <i class="material-icons prefix">search</i>
                    <input type="text"
                           name="search"
                           id="trainingSearch"
                           maxlength="100"
                           placeholder="Rechercher une formation par titre, description ou duree"
                           value="<?php echo safe_text($search); ?>">
                </div>
                <button type="submit" class="btn waves-effect waves-light">
                    Rechercher
                </button>
                <?php if($search != ""){ ?>
                    <a href="<?php echo app_url_html("admin/formations"); ?>" class="btn-flat">
                        Reinitialiser
                    </a>
                <?php } ?>
            </form>
        </div>

        <div class="table-card">

            <div class="table-header">

                <div class="table-title">
                    Liste des formations
                </div>

            </div>

            <p class="training-result-summary">
                <?php if($search != ""){ ?>
                    <?php echo (int)$total_trainings; ?> resultat(s) trouve(s) pour
                    &laquo; <?php echo safe_text($search); ?> &raquo;
                <?php }else{ ?>
                    <?php echo (int)$total_trainings; ?> formation(s)
                <?php } ?>
            </p>

            <table class="highlight responsive-table">

                <thead>

                    <tr>
                        <th>Titre</th>
                        <th>Duree</th>
                        <th>Lien YouTube</th>
                        <th>Total</th>
                        <th>En attente</th>
                        <th>En cours</th>
                        <th>Terminées</th>
                        <th>Date de creation</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if(count($trainings) > 0){ ?>

                        <?php foreach($trainings as $training){ ?>

                            <?php

                            $training_id = (int)$training["id"];
                            $title = isset($training["title"]) ? $training["title"] : "";
                            $description = isset($training["description"]) ? $training["description"] : "";
                            $youtube_url = isset($training["youtube_url"]) ? $training["youtube_url"] : "";
                            $duration = isset($training["duration"]) ? $training["duration"] : "";
                            $created_at = isset($training["created_at"]) ? $training["created_at"] : "";
                            $assigned_total = isset($training["assigned_total"]) ? (int)$training["assigned_total"] : 0;
                            $pending_total = isset($training["pending_total"]) ? (int)$training["pending_total"] : 0;
                            $in_progress_total = isset($training["in_progress_total"]) ? (int)$training["in_progress_total"] : 0;
                            $completed_total = isset($training["completed_total"]) ? (int)$training["completed_total"] : 0;

                            ?>

                            <tr>
                                <td><?php echo safe_text(display_value($title)); ?></td>
                                <td><?php echo safe_text(display_value($duration)); ?></td>
                                <td>
                                    <?php if($youtube_url != ""){ ?>
                                        <a href="<?php echo safe_text($youtube_url); ?>" target="_blank">
                                            Ouvrir
                                        </a>
                                    <?php }else{ ?>
                                        <?php echo safe_text("Non renseigne"); ?>
                                    <?php } ?>
                                </td>
                                <td><?php echo (int)$assigned_total; ?></td>
                                <td><?php echo (int)$pending_total; ?></td>
                                <td><?php echo (int)$in_progress_total; ?></td>
                                <td><?php echo (int)$completed_total; ?></td>
                                <td><?php echo safe_text(format_date_fr($created_at)); ?></td>
                                <td>
                                    <a href="#viewTraining<?php echo $training_id; ?>"
                                       class="modal-trigger green-text"
                                       title="Voir">
                                        <i class="material-icons">visibility</i>
                                    </a>

                                    <a href="#editTraining<?php echo $training_id; ?>"
                                       class="modal-trigger blue-text"
                                       title="Modifier">
                                        <i class="material-icons">edit</i>
                                    </a>

                                    <a href="#assignTraining<?php echo $training_id; ?>"
                                       class="modal-trigger orange-text"
                                       title="Attribuer">
                                        <i class="material-icons">person_add</i>
                                    </a>

                                    <form action="<?php echo app_url_html("admin/formations"); ?>"
                                          method="POST"
                                          style="display:inline;"
                                          onsubmit="return confirm('Voulez-vous supprimer cette formation ?');">
                                        <input type="hidden" name="action" value="delete_training">
                                        <input type="hidden" name="training_id" value="<?php echo $training_id; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo safe_text($csrf_token); ?>">
                                        <button type="submit"
                                                class="btn-flat red-text"
                                                title="Supprimer"
                                                style="padding:0 6px;">
                                            <i class="material-icons">delete</i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                        <?php } ?>

                    <?php }else{ ?>

                        <tr>
                            <td colspan="9" class="center-align">
                                <?php if($search != ""){ ?>
                                    Aucune formation ne correspond a votre recherche.
                                    <a href="<?php echo app_url_html("admin/formations"); ?>">Afficher toutes les formations</a>
                                <?php }else{ ?>
                                    Aucune formation n'est encore enregistree.
                                <?php } ?>
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
                                <a href="<?php echo safe_text(formations_pagination_url($page - 1)); ?>">Precedent</a>
                            </li>
                        <?php }else{ ?>
                            <li class="disabled"><a href="#!">Precedent</a></li>
                        <?php } ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if($start_page > 1){
                        ?>
                            <li class="waves-effect"><a href="<?php echo safe_text(formations_pagination_url(1)); ?>">1</a></li>
                            <?php if($start_page > 2){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                        <?php } ?>

                        <?php for($page_number = $start_page; $page_number <= $end_page; $page_number++){ ?>
                            <?php if($page_number == $page){ ?>
                                <li class="active"><a href="#!"><?php echo (int)$page_number; ?></a></li>
                            <?php }else{ ?>
                                <li class="waves-effect">
                                    <a href="<?php echo safe_text(formations_pagination_url($page_number)); ?>"><?php echo (int)$page_number; ?></a>
                                </li>
                            <?php } ?>
                        <?php } ?>

                        <?php if($end_page < $total_pages){ ?>
                            <?php if($end_page < $total_pages - 1){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                            <li class="waves-effect"><a href="<?php echo safe_text(formations_pagination_url($total_pages)); ?>"><?php echo (int)$total_pages; ?></a></li>
                        <?php } ?>

                        <?php if($page < $total_pages){ ?>
                            <li class="waves-effect">
                                <a href="<?php echo safe_text(formations_pagination_url($page + 1)); ?>">Suivant</a>
                            </li>
                        <?php }else{ ?>
                            <li class="disabled"><a href="#!">Suivant</a></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

        </div>

    </div>

</div>

<div id="modalAddTraining" class="modal modal-fixed-footer">
    <form action="<?php echo app_url_html("admin/formations"); ?>" method="POST">
        <input type="hidden" name="action" value="add_training">
        <input type="hidden" name="csrf_token" value="<?php echo safe_text($csrf_token); ?>">

        <div class="modal-content">
             <div style="
        background:linear-gradient(90deg,#1b2d8f,#e63b88);
        padding:28px 40px;
        border-radius:18px 18px 0 0;
    ">

        <h4 style="
            margin:0;
            color:#fff;
            font-size:38px;
            font-weight:700;
        ">
            Nouvelle formation
        </h4>

    </div>


            <div class="input-field">
                <input type="text" name="title" id="add_title" required>
                <label for="add_title">Titre</label>
            </div>

            <div class="input-field">
                <textarea name="description" id="add_description" class="materialize-textarea"></textarea>
                <label for="add_description">Description</label>
            </div>

            <div class="input-field">
                <input type="url" name="youtube_url" id="add_youtube_url" required>
                <label for="add_youtube_url">Lien YouTube</label>
                <span class="helper-text">
                    Collez simplement le lien normal de la vidéo YouTube. Le lien d'intégration sera généré automatiquement.
                </span>
            </div>

            <div class="input-field">
                <input type="text" name="duration" id="add_duration">
                <label for="add_duration">Duree</label>
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Annuler</a>
            <button type="submit" class="btn waves-effect waves-light">Enregistrer</button>
        </div>
    </form>
</div>

<?php foreach($trainings as $training){ ?>

    <?php

    $training_id = (int)$training["id"];
    $title = isset($training["title"]) ? $training["title"] : "";
    $description = isset($training["description"]) ? $training["description"] : "";
    $youtube_url = isset($training["youtube_url"]) ? $training["youtube_url"] : "";
    $youtube_embed_url = youtube_embed_url($youtube_url);
    $duration = isset($training["duration"]) ? $training["duration"] : "";
    $created_at = isset($training["created_at"]) ? $training["created_at"] : "";
    $assigned_total = isset($training["assigned_total"]) ? (int)$training["assigned_total"] : 0;
    $current_assignments = isset($training_assignments[$training_id])
        ? $training_assignments[$training_id]
        : array();

    ?>

    <div id="viewTraining<?php echo $training_id; ?>" class="modal modal-fixed-footer">
        <div class="modal-content" style="font-size:17px;line-height:1.9;color:#555;text-align:justify;">

        <div style="
                    background:linear-gradient(90deg,#1b2d8f,#e63b88);
                    padding:28px 40px;
                    border-radius:18px 18px 0 0;
                ">

                    <h4 style="
                        margin:0;
                        color:#fff;
                        font-size:38px;
                        font-weight:700;
                    ">
                        <?php echo safe_text(display_value($title)); ?>
                    </h4>

                </div>


            <p>
                <strong>Description :</strong><br>
                <?php echo nl2br(safe_text(display_value($description))); ?>
            </p>

            <p>
                <strong>Duree :</strong>
                <?php echo safe_text(display_value($duration)); ?>
            </p>

            <p>
                <strong>Lien YouTube :</strong>
                <?php echo safe_text(display_value($youtube_url)); ?>
            </p>

            <?php if($youtube_embed_url != ""){ ?>
                <div class="training-video-wrapper">
                    <iframe
                        src="<?php echo safe_text($youtube_embed_url); ?>"
                        title="<?php echo safe_text(display_value($title)); ?>"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
            <?php } ?>

            <p>
                <strong>Date de creation :</strong>
                <?php echo safe_text(format_date_fr($created_at)); ?>
            </p>

            <p>
                <strong>Intervenants assignes :</strong>
                <?php echo (int)$assigned_total; ?>
            </p>

            <?php if(count($current_assignments) > 0){ ?>
                <table class="highlight responsive-table">
                    <thead>
                        <tr>
                            <th>Intervenant</th>
                            <th>E-mail</th>
                            <th>Statut</th>
                            <th>Date d'attribution</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($current_assignments as $assignment){ ?>
                            <?php
                            $assignment_status = isset($assignment["status"]) ? $assignment["status"] : "";
                            $assignment_name = trim(
                                (isset($assignment["first_name"]) ? $assignment["first_name"] : "") .
                                " " .
                                (isset($assignment["last_name"]) ? $assignment["last_name"] : "")
                            );
                            ?>
                            <tr>
                                <td><?php echo safe_text(display_value($assignment_name)); ?></td>
                                <td><?php echo safe_text(display_value($assignment["email"])); ?></td>
                                <td>
                                    <span class="training-status-badge <?php echo safe_text(training_status_badge_class($assignment_status)); ?>">
                                        <?php echo safe_text(training_status_label($assignment_status)); ?>
                                    </span>
                                </td>
                                <td><?php echo safe_text(format_date_fr($assignment["created_at"])); ?></td>
                                <td>
                                    <?php if($assignment_status == "en_attente" || $assignment_status == "en_cours"){ ?>
                                        <form action="<?php echo app_url_html("admin/formations"); ?>" method="POST">
                                            <input type="hidden" name="action" value="deactivate_assignment">
                                            <input type="hidden" name="candidate_training_id" value="<?php echo (int)$assignment["id"]; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo safe_text($csrf_token); ?>">
                                            <button type="submit"
                                                    class="btn-small grey"
                                                    onclick="return confirm('Desactiver cette attribution ?');">
                                                Desactiver
                                            </button>
                                        </form>
                                    <?php }else{ ?>
                                        -
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php }else{ ?>
                <p class="grey-text">Aucun intervenant n'est encore attribue a cette formation.</p>
            <?php } ?>
        </div>

        <div class="modal-footer">
            <?php if($youtube_url != ""){ ?>
                <a href="<?php echo safe_text($youtube_url); ?>"
                   target="_blank"
                   class="btn waves-effect waves-light">
                    Ouvrir la video
                </a>
            <?php } ?>
            <a href="#!" class="modal-close btn-flat">Fermer</a>
        </div>
    </div>

    <div id="editTraining<?php echo $training_id; ?>" class="modal modal-fixed-footer">
        <form action="<?php echo app_url_html("admin/formations"); ?>" method="POST">
            <input type="hidden" name="action" value="update_training">
            <input type="hidden" name="training_id" value="<?php echo $training_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo safe_text($csrf_token); ?>">

            <div class="modal-content" >

             <div style="
                    background:linear-gradient(90deg,#1b2d8f,#e63b88);
                    padding:28px 40px;
                    border-radius:18px 18px 0 0;
                ">

                    <h4 style="
                        margin:0;
                        color:#fff;
                        font-size:38px;
                        font-weight:700;
                    ">
                        Modifier la formation
                    </h4>

                </div>


                <div class="input-field">
                    <input type="text"
                           name="title"
                           id="edit_title<?php echo $training_id; ?>"
                           value="<?php echo safe_text($title); ?>"
                           required>
                    <label class="active" for="edit_title<?php echo $training_id; ?>">Titre</label>
                </div>

                <div class="input-field">
                    <textarea name="description"
                              id="edit_description<?php echo $training_id; ?>"
                              class="materialize-textarea"><?php echo safe_text($description); ?></textarea>
                    <label class="active" for="edit_description<?php echo $training_id; ?>">Description</label>
                </div>

                <div class="input-field">
                    <input type="url"
                           name="youtube_url"
                           id="edit_youtube<?php echo $training_id; ?>"
                           value="<?php echo safe_text($youtube_url); ?>"
                           required>
                    <label class="active" for="edit_youtube<?php echo $training_id; ?>">Lien YouTube</label>
                    <span class="helper-text">
                        Collez simplement le lien normal de la vidéo YouTube. Le lien d'intégration sera généré automatiquement.
                    </span>
                </div>

                <div class="input-field">
                    <input type="text"
                           name="duration"
                           id="edit_duration<?php echo $training_id; ?>"
                           value="<?php echo safe_text($duration); ?>">
                    <label class="active" for="edit_duration<?php echo $training_id; ?>">Duree</label>
                </div>
            </div>

            <div class="modal-footer">
                <a href="#!" class="modal-close btn-flat">Annuler</a>
                <button type="submit" class="btn waves-effect waves-light">Modifier</button>
            </div>
        </form>
    </div>

    <div id="assignTraining<?php echo $training_id; ?>" class="modal modal-fixed-footer">
        <form action="<?php echo app_url_html("admin/formations"); ?>" method="POST">
            <input type="hidden" name="action" value="assign_training">
            <input type="hidden" name="training_id" value="<?php echo $training_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo safe_text($csrf_token); ?>">

            <div class="modal-content">
                <h4>Attribuer la formation</h4>
                <p><?php echo safe_text(display_value($title)); ?></p>

                <?php if(count($candidates) > 0){ ?>
                    <div class="input-field">
                        <i class="material-icons prefix">search</i>
                        <input type="text"
                               class="candidate-training-search"
                               id="candidateSearch<?php echo $training_id; ?>"
                               data-target-list="candidateList<?php echo $training_id; ?>"
                               placeholder="Rechercher un intervenant">
                    </div>
                <?php } ?>

                <div class="training-modal-list"
                     id="candidateList<?php echo $training_id; ?>">

                    <?php if(count($candidates) > 0){ ?>

                        <?php foreach($candidates as $candidate){ ?>

                            <?php

                            $candidate_id = isset($candidate["candidate_id"]) ? (int)$candidate["candidate_id"] : 0;
                            $first_name = isset($candidate["first_name"]) ? $candidate["first_name"] : "";
                            $last_name = isset($candidate["last_name"]) ? $candidate["last_name"] : "";
                            $email = isset($candidate["email"]) ? $candidate["email"] : "";
                            $phone = isset($candidate["phone"]) ? $candidate["phone"] : "";
                            $verification_status = isset($candidate["verification_status"]) ? $candidate["verification_status"] : "";
                            $full_name = trim($first_name . " " . $last_name);

                            if($full_name == ""){

                                $full_name = "Intervenant";

                            }

                            ?>

                            <p class="candidate-training-item"
                               data-search="<?php echo safe_text($first_name . " " . $last_name . " " . $full_name . " " . $email . " " . $phone); ?>">
                                <label>
                                    <input type="checkbox"
                                           name="candidates[]"
                                           value="<?php echo $candidate_id; ?>">
                                    <span>
                                        <?php echo safe_text($full_name); ?>
                                        -
                                        <?php echo safe_text(display_value($email)); ?>
                                        -
                                        <?php echo safe_text(display_value($verification_status)); ?>
                                    </span>
                                </label>
                            </p>

                        <?php } ?>

                        <p class="candidate-no-result">
                            Aucun intervenant ne correspond a votre recherche.
                        </p>

                    <?php }else{ ?>

                        <p class="grey-text">
                            Aucun intervenant actif, verifie et disponible.
                        </p>

                    <?php } ?>

                </div>
            </div>

            <div class="modal-footer">
                <a href="#!" class="modal-close btn-flat">Annuler</a>
                <button type="submit" class="btn waves-effect waves-light">Attribuer</button>
            </div>
        </form>
    </div>

<?php } ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    M.Modal.init(document.querySelectorAll('.modal'), {
        onCloseEnd: function(modalElement) {
            var youtubeIframe = modalElement.querySelector('.training-video-wrapper iframe');

            if(youtubeIframe){

                var iframeSource = youtubeIframe.getAttribute('src');
                youtubeIframe.setAttribute('src', '');

                window.setTimeout(function() {
                    youtubeIframe.setAttribute('src', iframeSource);
                }, 0);

            }
        }
    });
    M.FormSelect.init(document.querySelectorAll('select'));
    M.updateTextFields();

    var candidateSearchFields = document.querySelectorAll('.candidate-training-search');

    Array.prototype.forEach.call(candidateSearchFields, function(searchField) {
        searchField.addEventListener('input', function() {
            var targetList = document.getElementById(searchField.getAttribute('data-target-list'));
            var searchTerm = searchField.value.toLowerCase();
            var items = targetList.querySelectorAll('.candidate-training-item');
            var visibleTotal = 0;
            var noResult = targetList.querySelector('.candidate-no-result');

            Array.prototype.forEach.call(items, function(item) {
                var candidateText = item.getAttribute('data-search').toLowerCase();
                var isVisible = candidateText.indexOf(searchTerm) !== -1;

                item.style.display = isVisible ? '' : 'none';

                if(isVisible){

                    visibleTotal++;

                }
            });

            if(noResult){

                noResult.style.display = visibleTotal === 0 ? 'block' : 'none';

            }
        });
    });
});
</script>

</body>
</html>
