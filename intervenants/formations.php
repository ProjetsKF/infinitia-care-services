<?php

session_start();

require_once("../config/database.php");

if(
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role_id"]) ||
    $_SESSION["role_id"] != 3
){
    header("Location: " . app_url("login"));
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$candidate_id = 0;
$formations = array();
$total_formations = 0;
$pending_formations = 0;
$in_progress_formations = 0;
$completed_formations = 0;
$limit = 20;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if(!isset($_SESSION["csrf_token"]) || $_SESSION["csrf_token"] == ""){

    $csrf_bytes = openssl_random_pseudo_bytes(32);

    if($csrf_bytes === false){

        $csrf_bytes = uniqid((string)mt_rand(), true);

    }

    $_SESSION["csrf_token"] = bin2hex($csrf_bytes);
}

$csrf_token = $_SESSION["csrf_token"];

if($page < 1){

    $page = 1;

}

function safe_text($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function formations_pagination_url($page_number)
{
    $params = $_GET;
    $params["page"] = (int)$page_number;

    return app_url_with_query("intervenant/formations", $params);
}

function redirect_formations()
{
    header("Location: " . app_url("intervenant/formations"));
    exit();
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

        return "Formation terminée";

    }

    return "Formation indisponible";
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

function extract_youtube_video_id($youtube_url)
{
    $parts = parse_url(trim((string)$youtube_url));

    if($parts === false || !isset($parts["host"])){

        return "";

    }

    $host = strtolower($parts["host"]);
    $path = isset($parts["path"]) ? trim($parts["path"], "/") : "";
    $video_id = "";

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
            parse_str(isset($parts["query"]) ? $parts["query"] : "", $query);
            $video_id = isset($query["v"]) ? $query["v"] : "";

        }else{

            $path_parts = explode("/", $path);
            $path_type = isset($path_parts[0]) ? $path_parts[0] : "";

            if($path_type == "embed" || $path_type == "shorts"){

                $video_id = isset($path_parts[1]) ? $path_parts[1] : "";

            }

        }

    }

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

$sql = "
SELECT id
FROM candidates
WHERE user_id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt){

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $candidate_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

}

if($candidate_id <= 0){

    header("Location: " . app_url("login"));
    exit();

}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"]) ? $_POST["action"] : "";
    $candidate_training_id = isset($_POST["candidate_training_id"])
        ? (int)$_POST["candidate_training_id"]
        : 0;
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

    if($candidate_training_id <= 0){

        $_SESSION["error"] = "Attribution de formation invalide.";
        redirect_formations();

    }

    if($action == "start_training"){

        $sql = "
        UPDATE candidate_trainings ct
        INNER JOIN candidates c
        ON c.id = ct.candidate_id
        SET ct.status = 'en_cours'
        WHERE ct.id = ?
        AND c.user_id = ?
        AND ct.status = 'en_attente'
        ";
        $success_message = "La formation a été commencée.";

    }elseif($action == "complete_training"){

        $sql = "
        UPDATE candidate_trainings ct
        INNER JOIN candidates c
        ON c.id = ct.candidate_id
        SET ct.status = 'terminee'
        WHERE ct.id = ?
        AND c.user_id = ?
        AND ct.status = 'en_cours'
        ";
        $success_message = "La formation a été marquée comme terminée.";

    }else{

        $_SESSION["error"] = "Action de formation invalide.";
        redirect_formations();

    }

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        $_SESSION["error"] = "Erreur lors de la mise a jour de la formation.";
        redirect_formations();

    }

    mysqli_stmt_bind_param($stmt, "ii", $candidate_training_id, $user_id);
    mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if($affected_rows == 1){

        $_SESSION["success"] = $success_message;

    }else{

        $_SESSION["error"] = "Cette transition n'est pas autorisee ou cette formation ne vous appartient pas.";

    }

    redirect_formations();

}

$sql = "
SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'en_attente' THEN 1 ELSE 0 END) AS pending_total,
    SUM(CASE WHEN status = 'en_cours' THEN 1 ELSE 0 END) AS in_progress_total,
    SUM(CASE WHEN status = 'terminee' THEN 1 ELSE 0 END) AS completed_total
FROM candidate_trainings
WHERE candidate_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt){

    mysqli_stmt_bind_param($stmt, "i", $candidate_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result(
        $stmt,
        $total_formations,
        $pending_formations,
        $in_progress_formations,
        $completed_formations
    );
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

}

$total_formations = (int)$total_formations;
$pending_formations = (int)$pending_formations;
$in_progress_formations = (int)$in_progress_formations;
$completed_formations = (int)$completed_formations;
$total_pages = (int)ceil($total_formations / $limit);

if($total_pages > 0 && $page > $total_pages){

    $page = $total_pages;

}

if($total_pages < 1){

    $page = 1;

}

$offset = ($page - 1) * $limit;

$sql = "
SELECT
    ct.id AS candidate_training_id,
    t.id AS training_id,
    t.title,
    t.description,
    t.youtube_url,
    t.duration,
    ct.status,
    ct.created_at
FROM candidate_trainings ct
INNER JOIN trainings t
ON t.id = ct.training_id
WHERE ct.candidate_id = ?
ORDER BY ct.created_at DESC, ct.id DESC
LIMIT ?
OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt){

    mysqli_stmt_bind_param($stmt, "iii", $candidate_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result(
        $stmt,
        $candidate_training_id,
        $training_id,
        $training_title,
        $training_description,
        $training_youtube_url,
        $training_duration,
        $training_status,
        $training_created_at
    );

    while(mysqli_stmt_fetch($stmt)){

        $formations[] = array(
            "candidate_training_id" => $candidate_training_id,
            "training_id" => $training_id,
            "title" => $training_title,
            "description" => $training_description,
            "youtube_url" => $training_youtube_url,
            "duration" => $training_duration,
            "status" => $training_status,
            "created_at" => $training_created_at
        );

    }

    mysqli_stmt_close($stmt);

}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(dirname(__DIR__) . "/includes/pwa-head.php"); ?>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        Mes formations | INFINITIA

    </title>

    <link rel="icon"
          type="image/x-icon"
          href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="<?php echo app_url_html("assets/css/style.css"); ?>">

    <style>
        .training-status-badge{
            border-radius:12px;
            color:#ffffff;
            display:inline-block;
            font-size:12px;
            font-weight:600;
            line-height:1;
            padding:7px 10px;
        }

        .training-actions{
            align-items:center;
            display:flex;
            flex-wrap:wrap;
            gap:8px;
        }

        .training-actions form{
            margin:0;
        }
    </style>

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "formations";

    include("menuin.php");

    ?>


<div class="main-content">

    <div class="topbar">

        <div class="page-title">
            Mes Formations
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

    <div class="row intervenant-stat-grid">

        <div class="col s12 m6 l3">
            <div class="card blue darken-3 white-text">
                <div class="card-content">
                    <span class="card-title">
                        Total attribue
                    </span>
                    <h3>
                        <?php echo (int)$total_formations; ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col s12 m6 l3">
            <div class="card orange darken-2 white-text">
                <div class="card-content">
                    <span class="card-title">
                        En attente
                    </span>
                    <h3>
                        <?php echo (int)$pending_formations; ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col s12 m6 l3">
            <div class="card blue white-text">
                <div class="card-content">
                    <span class="card-title">
                        En cours
                    </span>
                    <h3>
                        <?php echo (int)$in_progress_formations; ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col s12 m6 l3">
            <div class="card green darken-2 white-text">
                <div class="card-content">
                    <span class="card-title">
                        Terminées
                    </span>
                    <h3>
                        <?php echo (int)$completed_formations; ?>
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="table-card">

        <div class="table-title">
            Mes formations
        </div>

        <table class="highlight responsive-table intervenant-table mobile-card-table">

            <thead>

                <tr>
                    <th>Formation</th>
                    <th>Description</th>
                    <th>Durée</th>
                    <th>Statut</th>
                    <th>Video</th>
                    <th>Action</th>
                </tr>

            </thead>

            <tbody>

                <?php if(count($formations) > 0){ ?>

                    <?php foreach($formations as $formation){ ?>

                        <?php
                        $training_id = isset($formation["training_id"]) ? (int)$formation["training_id"] : 0;
                        $title = isset($formation["title"]) ? $formation["title"] : "";
                        $description = isset($formation["description"]) ? $formation["description"] : "";
                        $duration = isset($formation["duration"]) ? $formation["duration"] : "";
                        $youtube_url = isset($formation["youtube_url"]) ? $formation["youtube_url"] : "";
                        $candidate_training_id = isset($formation["candidate_training_id"])
                            ? (int)$formation["candidate_training_id"]
                            : 0;
                        $status = isset($formation["status"]) ? $formation["status"] : "";
                        ?>

                        <tr class="mobile-card-row">

                            <td data-label="Formation">
                                <?php echo safe_text($title); ?>
                            </td>

                            <td data-label="Description">
                                <?php echo safe_text($description); ?>
                            </td>

                            <td data-label="Durée">
                                <?php echo safe_text($duration); ?>
                            </td>

                            <td data-label="Statut">
                                <span class="training-status-badge <?php echo safe_text(training_status_badge_class($status)); ?>">
                                    <?php echo safe_text(training_status_label($status)); ?>
                                </span>
                            </td>

                            <td data-label="Vidéo">
                                <?php if($status == "inactive"){ ?>

                                    <span class="grey-text">Video indisponible</span>

                                <?php }elseif($youtube_url != "" && youtube_embed_url($youtube_url) != ""){ ?>

                                    <a href="#videoModal<?php echo $training_id; ?>"
                                       class="btn blue modal-trigger">

                                        <i class="material-icons left">
                                            play_circle
                                        </i>

                                        Voir la video

                                    </a>

                                <?php }else{ ?>

                                    <span class="grey-text">
                                        Video indisponible
                                    </span>

                                <?php } ?>

                            </td>

                            <td data-label="Action">
                                <div class="training-actions">
                                    <?php if($status == "en_attente"){ ?>
                                        <form action="<?php echo app_url_html("intervenant/formations"); ?>" method="POST">
                                            <input type="hidden" name="action" value="start_training">
                                            <input type="hidden" name="candidate_training_id" value="<?php echo $candidate_training_id; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo safe_text($csrf_token); ?>">
                                            <button type="submit" class="btn orange waves-effect waves-light">
                                                Commencer la formation
                                            </button>
                                        </form>
                                    <?php }elseif($status == "en_cours"){ ?>
                                        <form action="<?php echo app_url_html("intervenant/formations"); ?>" method="POST">
                                            <input type="hidden" name="action" value="complete_training">
                                            <input type="hidden" name="candidate_training_id" value="<?php echo $candidate_training_id; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo safe_text($csrf_token); ?>">
                                            <button type="submit" class="btn green waves-effect waves-light">
                                                Marquer comme terminée
                                            </button>
                                        </form>
                                    <?php }elseif($status == "terminee"){ ?>
                                        <span class="green-text">Formation terminée</span>
                                    <?php }else{ ?>
                                        <span class="grey-text">Formation indisponible</span>
                                    <?php } ?>
                                </div>
                            </td>

                        </tr>

                    <?php } ?>

                <?php }else{ ?>

                    <tr class="intervenant-empty-row">

                        <td colspan="6" class="center-align intervenant-empty-state-cell">
                            <i class="material-icons" aria-hidden="true">school</i>
                            <span>Aucune formation disponible pour le moment.</span>
                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

        <?php if($total_pages > 1){ ?>

            <div class="table-pagination" style="margin-top:25px; margin-bottom:20px; text-align:center;">

                <ul class="pagination center-align">

                    <?php if($page > 1){ ?>
                        <li class="waves-effect">
                            <a href="<?php echo htmlspecialchars(formations_pagination_url($page - 1)); ?>">
                                PrÃ©cÃ©dent
                            </a>
                        </li>
                    <?php }else{ ?>
                        <li class="disabled">
                            <a href="#!">PrÃ©cÃ©dent</a>
                        </li>
                    <?php } ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if($start_page > 1){
                    ?>
                        <li class="waves-effect">
                            <a href="<?php echo htmlspecialchars(formations_pagination_url(1)); ?>">1</a>
                        </li>
                        <?php if($start_page > 2){ ?>
                            <li class="disabled"><a href="#!">...</a></li>
                        <?php } ?>
                    <?php } ?>

                    <?php for($page_number = $start_page; $page_number <= $end_page; $page_number++){ ?>
                        <?php if($page_number == $page){ ?>
                            <li class="active">
                                <a href="#!"><?php echo (int)$page_number; ?></a>
                            </li>
                        <?php }else{ ?>
                            <li class="waves-effect">
                                <a href="<?php echo htmlspecialchars(formations_pagination_url($page_number)); ?>">
                                    <?php echo (int)$page_number; ?>
                                </a>
                            </li>
                        <?php } ?>
                    <?php } ?>

                    <?php if($end_page < $total_pages){ ?>
                        <?php if($end_page < $total_pages - 1){ ?>
                            <li class="disabled"><a href="#!">...</a></li>
                        <?php } ?>
                        <li class="waves-effect">
                            <a href="<?php echo htmlspecialchars(formations_pagination_url($total_pages)); ?>">
                                <?php echo (int)$total_pages; ?>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if($page < $total_pages){ ?>
                        <li class="waves-effect">
                            <a href="<?php echo htmlspecialchars(formations_pagination_url($page + 1)); ?>">
                                Suivant
                            </a>
                        </li>
                    <?php }else{ ?>
                        <li class="disabled">
                            <a href="#!">Suivant</a>
                        </li>
                    <?php } ?>

                </ul>

            </div>

        <?php } ?>

    </div>

</div>



<?php foreach($formations as $formation){ ?>

    <?php
    $training_id = isset($formation["training_id"]) ? (int)$formation["training_id"] : 0;
    $title = isset($formation["title"]) ? $formation["title"] : "";
    $youtube_url = isset($formation["youtube_url"]) ? $formation["youtube_url"] : "";
    $status = isset($formation["status"]) ? $formation["status"] : "";
    $embed_url = youtube_embed_url($youtube_url);
    ?>

    <?php if($status != "inactive" && $embed_url != ""){ ?>

        <div id="videoModal<?php echo $training_id; ?>" class="modal">

            <div class="modal-content">

                <h5><?php echo safe_text($title); ?></h5>

                <iframe
                    src="<?php echo safe_text($embed_url); ?>"
                    style="
                        width:100%;
                        height:75vh;
                        border:none;
                        border-radius:8px;
                    "
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>

            </div>

            <div class="modal-footer">

                <a href="#!"
                   class="modal-close btn grey">
                    Fermer
                </a>

            </div>

        </div>

    <?php } ?>

<?php } ?>


</div>





<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function() {

    var elems = document.querySelectorAll('.modal');

    M.Modal.init(elems, {
        opacity: 0.7,
        inDuration: 250,
        outDuration: 250
    });

});

</script>

</body>
</html>
