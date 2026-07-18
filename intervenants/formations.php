<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$candidate_id = 0;
$formations = array();
$total_formations = 0;
$total_duration_minutes = 0;
$limit = 20;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if($page < 1)
{
    $page = 1;
}

function formations_pagination_url($page_number)
{
    $params = $_GET;
    $params["page"] = (int)$page_number;

    return "formations.php?" . http_build_query($params);
}

function duration_to_minutes($duration)
{
    if($duration === NULL || $duration === "")
    {
        return 0;
    }

    $value = strtolower((string)$duration);
    $minutes = 0;

    if(preg_match('/([0-9]+)\s*h/', $value, $matches))
    {
        $minutes += ((int)$matches[1]) * 60;
    }

    if(preg_match('/([0-9]+)\s*min/', $value, $matches))
    {
        $minutes += (int)$matches[1];
    }

    if($minutes == 0 && preg_match('/^[0-9]+$/', trim($value)))
    {
        $minutes = (int)trim($value);
    }

    return $minutes;
}

function format_duration_minutes($minutes)
{
    $minutes = (int)$minutes;

    if($minutes <= 0)
    {
        return "0 min";
    }

    $hours = (int)floor($minutes / 60);
    $remaining = $minutes % 60;

    if($hours > 0 && $remaining > 0)
    {
        return $hours . " h " . $remaining . " min";
    }

    if($hours > 0)
    {
        return $hours . " h";
    }

    return $remaining . " min";
}

function youtube_embed_url($url)
{
    if($url === NULL || $url === "")
    {
        return "";
    }

    $url = trim($url);

    if(strpos($url, "youtube.com/embed/") !== false)
    {
        return $url;
    }

    $video_id = "";

    if(preg_match('/youtu\.be\/([^?&]+)/', $url, $matches))
    {
        $video_id = $matches[1];
    }
    elseif(preg_match('/[?&]v=([^?&]+)/', $url, $matches))
    {
        $video_id = $matches[1];
    }

    if($video_id == "")
    {
        return $url;
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

if($stmt)
{
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($result))
    {
        $candidate_id = (int)$row["id"];
    }

    mysqli_stmt_close($stmt);
}

if($candidate_id <= 0)
{
    header("Location: ../login.php");
    exit();
}

$sql = "
SELECT COUNT(*) AS total
FROM candidate_trainings ct
INNER JOIN trainings t
ON t.id = ct.training_id
WHERE ct.candidate_id = ?
AND ct.status = 'active'
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt)
{
    mysqli_stmt_bind_param($stmt, "i", $candidate_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($result))
    {
        $total_formations = (int)$row["total"];
    }

    mysqli_stmt_close($stmt);
}

$total_pages = (int)ceil($total_formations / $limit);

if($total_pages > 0 && $page > $total_pages)
{
    $page = $total_pages;
}

if($total_pages < 1)
{
    $page = 1;
}

$offset = ($page - 1) * $limit;

$sql = "
SELECT t.duration
FROM candidate_trainings ct
INNER JOIN trainings t
ON t.id = ct.training_id
WHERE ct.candidate_id = ?
AND ct.status = 'active'
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt)
{
    mysqli_stmt_bind_param($stmt, "i", $candidate_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while($row = mysqli_fetch_assoc($result))
    {
        $duration = isset($row["duration"]) ? $row["duration"] : "";
        $total_duration_minutes += duration_to_minutes($duration);
    }

    mysqli_stmt_close($stmt);
}

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
AND ct.status = 'active'
ORDER BY ct.created_at DESC
LIMIT ?
OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt)
{
    mysqli_stmt_bind_param($stmt, "iii", $candidate_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while($row = mysqli_fetch_assoc($result))
    {
        $formations[] = $row;
    }

    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        Mes formations | INFINITIA

    </title>

    <link rel="icon"
          type="image/x-icon"
          href="../assets/images/ico.ico">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="../assets/css/style.css">

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

    <div class="row">

        <div class="col s12 m6">
            <div class="card blue darken-3 white-text">
                <div class="card-content">
                    <span class="card-title">
                        Formations disponibles
                    </span>
                    <h3>
                        <?php echo (int)$total_formations; ?>
                    </h3>
                </div>
            </div>
        </div>

        <div class="col s12 m6">
            <div class="card green darken-2 white-text">
                <div class="card-content">
                    <span class="card-title">
                        Durée totale
                    </span>
                    <h3>
                        <?php echo htmlspecialchars(format_duration_minutes($total_duration_minutes)); ?>
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="table-card">

        <div class="table-title">
            Mes formations
        </div>

        <table class="highlight responsive-table">

            <thead>

                <tr>
                    <th>Formation</th>
                    <th>Description</th>
                    <th>Durée</th>
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
                        ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($title); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($description); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($duration); ?>
                            </td>

                            <td>

                                <?php if($youtube_url != ""){ ?>

                                    <a href="#videoModal<?php echo $training_id; ?>"
                                       class="btn blue modal-trigger">

                                        <i class="material-icons left">
                                            play_circle
                                        </i>

                                        Suivre

                                    </a>

                                <?php }else{ ?>

                                    <span class="grey-text">
                                        Video indisponible
                                    </span>

                                <?php } ?>

                            </td>

                        </tr>

                    <?php } ?>

                <?php }else{ ?>

                    <tr>

                        <td colspan="4" class="center-align">
                            Aucune formation disponible.
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
    $embed_url = youtube_embed_url($youtube_url);
    ?>

    <?php if($youtube_url != ""){ ?>

        <div id="videoModal<?php echo $training_id; ?>" class="modal">

            <div class="modal-content">

                <h5><?php echo htmlspecialchars($title); ?></h5>

                <iframe
                    src="<?php echo htmlspecialchars($embed_url); ?>"
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
