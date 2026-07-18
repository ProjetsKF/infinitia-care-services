<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"])){

    header("Location: ../login.php");
    exit();

}

if(!isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 2){

    header("Location: ../login.php");
    exit();

}

$user_id = (int)$_SESSION["user_id"];
$client_id = 0;
$missions_en_cours = 0;
$missions_terminees = 0;
$missions = array();
$page = isset($_GET["page"])
    ? (int)$_GET["page"]
    : 1;
$limit = 20;
$total_rows = 0;
$total_pages = 1;

if($page < 1){

    $page = 1;

}

$offset = ($page - 1) * $limit;

function safe_text($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function array_value($row, $key)
{
    if(isset($row[$key]) && $row[$key] !== NULL){

        return $row[$key];

    }

    return "";
}

function format_date_fr($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    $timestamp = strtotime($value);

    if($timestamp === false){

        return "";

    }

    return date("d/m/Y", $timestamp);
}

function mission_status_label($status)
{
    if($status == "en_cours"){

        return "En cours";

    }

    if($status == "terminee"){

        return "Terminee";

    }

    return "Non renseigne";
}

function mission_status_class($status)
{
    if($status == "en_cours"){

        return "progress";

    }

    if($status == "terminee"){

        return "completed";

    }

    return "pending";
}

function count_missions_by_status($conn, $client_id, $status)
{
    $total = 0;

    $sql = "
    SELECT COUNT(*)
    FROM missions m
    INNER JOIN service_requests sr
    ON sr.id = m.service_request_id
    WHERE sr.client_id = ?
    AND m.mission_status = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if($stmt){

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $client_id,
            $status
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $total);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

    }

    return (int)$total;
}

function missions_pagination_url($page)
{
    $params = $_GET;
    $params["page"] = (int)$page;

    return "missions.php?" . http_build_query($params);
}

$sql = "

SELECT id
FROM clients
WHERE user_id = ?
LIMIT 1

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $client_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if($client_id <= 0){

    header("Location: ../login.php");
    exit();

}

$missions_en_cours = count_missions_by_status(
    $conn,
    $client_id,
    "en_cours"
);

$missions_terminees = count_missions_by_status(
    $conn,
    $client_id,
    "terminee"
);

$sql = "

SELECT COUNT(*)
FROM service_requests sr
INNER JOIN missions m
ON m.service_request_id = sr.id
WHERE sr.client_id = ?
AND m.mission_status IN ('en_cours', 'terminee')

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total_rows);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$total_rows = (int)$total_rows;
$total_pages = (int)ceil($total_rows / $limit);

if($total_pages < 1){

    $total_pages = 1;

}

if($page > $total_pages){

    $page = $total_pages;
    $offset = ($page - 1) * $limit;

}

$sql = "

SELECT
    m.id AS mission_id,
    m.mission_status,
    m.start_time,
    m.end_time,
    m.notes,

    sr.title,
    sr.description,
    sr.location,
    sr.service_date,
    sr.duration,
    sr.budget,
    sr.urgency_level,

    u.first_name,
    u.last_name

FROM service_requests sr

INNER JOIN missions m
ON m.service_request_id = sr.id

INNER JOIN candidates c
ON c.id = m.candidate_id

INNER JOIN users u
ON u.id = c.user_id

WHERE sr.client_id = ?
AND m.mission_status IN ('en_cours', 'terminee')

ORDER BY sr.service_date DESC, m.created_at DESC
LIMIT ?
OFFSET ?

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

mysqli_stmt_bind_param(
    $stmt,
    "iii",
    $client_id,
    $limit,
    $offset
);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result(
    $stmt,
    $mission_id,
    $mission_status,
    $start_time,
    $end_time,
    $notes,
    $title,
    $description,
    $location,
    $service_date,
    $duration,
    $budget,
    $urgency_level,
    $first_name,
    $last_name
);

while(mysqli_stmt_fetch($stmt)){

    $missions[] = array(
        "mission_id" => $mission_id,
        "mission_status" => $mission_status,
        "start_time" => $start_time,
        "end_time" => $end_time,
        "notes" => $notes,
        "title" => $title,
        "description" => $description,
        "location" => $location,
        "service_date" => $service_date,
        "duration" => $duration,
        "budget" => $budget,
        "urgency_level" => $urgency_level,
        "first_name" => $first_name,
        "last_name" => $last_name
    );

}

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Missions | INFINITIA

    </title>

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

    <!-- MATERIALIZE -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- MATERIAL ICONS -->

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- GOOGLE FONT -->

    <link rel="preconnect"
    href="https://fonts.googleapis.com">

    <link rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <div class="dashboard">

       <?php

        $current_page = "missions";

        include("menucli.php");

        ?>

        <!-- =========================================
             MAIN CONTENT
        ========================================= -->

        <div class="main-content">

            <!-- TOPBAR -->

            <div class="topbar">

                <div>

                    <div class="page-title">

                        <i class="material-icons left">
                            assignment
                        </i>

                        Missions

                    </div>

                    <div class="welcome-text">

                        Consultez les missions affectees a vos demandes.

                    </div>

                </div>

            </div>

            <!-- STATISTIQUES -->

            <div class="row">

                <div class="col s12 m6">

                    <div class="dashboard-card">

                        <div class="card-icon pink-gradient">

                            <i class="material-icons">
                                engineering
                            </i>

                        </div>

                        <h5>
                            Missions en cours
                        </h5>

                        <h3>
                            <?php echo (int)$missions_en_cours; ?>
                        </h3>

                    </div>

                </div>

                <div class="col s12 m6">

                    <div class="dashboard-card">

                        <div class="card-icon gold-gradient">

                            <i class="material-icons">
                                task_alt
                            </i>

                        </div>

                        <h5>
                            Missions terminees
                        </h5>

                        <h3>
                            <?php echo (int)$missions_terminees; ?>
                        </h3>

                    </div>

                </div>

            </div>

            <?php if(count($missions) > 0){ ?>

                <!-- LISTE DES MISSIONS -->

                <div class="table-card">

                    <div class="table-header">

                        <div class="table-title">

                            Liste des missions

                        </div>

                    </div>

                    <table class="highlight responsive-table">

                        <thead>

                            <tr>

                                <th>Reference</th>
                                <th>Service demande</th>
                                <th>Intervenant</th>
                                <th>Date prevue</th>
                                <th>Lieu</th>
                                <th>Statut</th>
                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach($missions as $mission){ ?>

                                <?php

                                $mission_id_value = (int)array_value($mission, "mission_id");
                                $title_value = array_value($mission, "title");
                                $first_name_value = array_value($mission, "first_name");
                                $last_name_value = array_value($mission, "last_name");
                                $full_name_value = trim($first_name_value . " " . $last_name_value);
                                $service_date_value = array_value($mission, "service_date");
                                $location_value = array_value($mission, "location");
                                $mission_status_value = array_value($mission, "mission_status");

                                if($full_name_value == ""){

                                    $full_name_value = "Intervenant";

                                }

                                ?>

                                <tr>

                                    <td>
                                        MIS-<?php echo str_pad($mission_id_value, 3, "0", STR_PAD_LEFT); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text($title_value != "" ? $title_value : "Non renseigne"); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text($full_name_value); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(format_date_fr($service_date_value) != "" ? format_date_fr($service_date_value) : "Non renseigne"); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text($location_value != "" ? $location_value : "Non renseigne"); ?>
                                    </td>

                                    <td>

                                        <span class="status <?php echo mission_status_class($mission_status_value); ?>">

                                            <?php echo safe_text(mission_status_label($mission_status_value)); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <a href="#mission<?php echo $mission_id_value; ?>"
                                           class="modal-trigger blue-text"
                                           title="Voir">

                                            <i class="material-icons">
                                                visibility
                                            </i>

                                        </a>

                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                    <div class="center" style="margin-top:25px;">

                        <ul class="pagination">

                            <li class="<?php echo ($page <= 1) ? 'disabled' : 'waves-effect'; ?>">

                                <a href="<?php echo ($page <= 1) ? '#!' : safe_text(missions_pagination_url($page - 1)); ?>">

                                    <i class="material-icons">
                                        chevron_left
                                    </i>

                                </a>

                            </li>

                            <?php for($i = 1; $i <= $total_pages; $i++){ ?>

                                <li class="<?php echo ($i == $page) ? 'active' : 'waves-effect'; ?>">

                                    <a href="<?php echo safe_text(missions_pagination_url($i)); ?>">

                                        <?php echo (int)$i; ?>

                                    </a>

                                </li>

                            <?php } ?>

                            <li class="<?php echo ($page >= $total_pages) ? 'disabled' : 'waves-effect'; ?>">

                                <a href="<?php echo ($page >= $total_pages) ? '#!' : safe_text(missions_pagination_url($page + 1)); ?>">

                                    <i class="material-icons">
                                        chevron_right
                                    </i>

                                </a>

                            </li>

                        </ul>

                    </div>

                </div>

                <?php foreach($missions as $mission){ ?>

                    <?php

                    $mission_id_value = (int)array_value($mission, "mission_id");
                    $title_value = array_value($mission, "title");
                    $description_value = array_value($mission, "description");
                    $first_name_value = array_value($mission, "first_name");
                    $last_name_value = array_value($mission, "last_name");
                    $full_name_value = trim($first_name_value . " " . $last_name_value);
                    $service_date_value = array_value($mission, "service_date");
                    $location_value = array_value($mission, "location");
                    $duration_value = array_value($mission, "duration");
                    $budget_value = array_value($mission, "budget");
                    $urgency_value = array_value($mission, "urgency_level");
                    $mission_status_value = array_value($mission, "mission_status");
                    $start_time_value = array_value($mission, "start_time");
                    $end_time_value = array_value($mission, "end_time");
                    $notes_value = array_value($mission, "notes");

                    if($full_name_value == ""){

                        $full_name_value = "Intervenant";

                    }

                    $budget_label = "Non renseigne";

                    if($budget_value !== ""){

                        $budget_label = number_format((float)$budget_value, 2) . " USD";

                    }

                    ?>

                    <div id="mission<?php echo $mission_id_value; ?>"
                         class="modal">

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
                                Mission Details
                                </h4>

                            </div>

                        <div class="modal-content" style="font-size:17px;line-height:1.9;color:#555;text-align:justify;">

                            <h4>
                                <?php echo safe_text($title_value != "" ? $title_value : "Mission"); ?>
                            </h4>

                            <p>
                                <strong>Reference :</strong>
                                MIS-<?php echo str_pad($mission_id_value, 3, "0", STR_PAD_LEFT); ?>
                            </p>

                            <p>
                                <strong>Intervenant :</strong>
                                <?php echo safe_text($full_name_value); ?>
                            </p>

                            <p>
                                <strong>Description :</strong><br>
                                <?php echo nl2br(safe_text($description_value != "" ? $description_value : "Non renseigne")); ?>
                            </p>

                            <p>
                                <strong>Date prevue :</strong>
                                <?php echo safe_text(format_date_fr($service_date_value) != "" ? format_date_fr($service_date_value) : "Non renseigne"); ?>
                            </p>

                            <p>
                                <strong>Lieu :</strong>
                                <?php echo safe_text($location_value != "" ? $location_value : "Non renseigne"); ?>
                            </p>

                            <p>
                                <strong>Duree :</strong>
                                <?php echo safe_text($duration_value !== "" ? $duration_value . " h" : "Non renseigne"); ?>
                            </p>

                            <p>
                                <strong>Budget :</strong>
                                <?php echo safe_text($budget_label); ?>
                            </p>

                            <p>
                                <strong>Urgence :</strong>
                                <?php echo safe_text($urgency_value != "" ? ucfirst($urgency_value) : "Non renseigne"); ?>
                            </p>

                            <p>
                                <strong>Statut :</strong>
                                <?php echo safe_text(mission_status_label($mission_status_value)); ?>
                            </p>

                            <p>
                                <strong>Debut :</strong>
                                <?php echo safe_text(format_date_fr($start_time_value) != "" ? format_date_fr($start_time_value) : "Non renseigne"); ?>
                            </p>

                            <p>
                                <strong>Fin :</strong>
                                <?php echo safe_text(format_date_fr($end_time_value) != "" ? format_date_fr($end_time_value) : "Non renseigne"); ?>
                            </p>

                            <p>
                                <strong>Notes :</strong><br>
                                <?php echo nl2br(safe_text($notes_value != "" ? $notes_value : "Non renseigne")); ?>
                            </p>

                        </div>

                        <div class="modal-footer">

                            <a href="#!"
                               class="modal-close btn grey">

                                Fermer

                            </a>

                        </div>

                    </div>

                <?php } ?>

            <?php }else{ ?>

                <div class="card">

                    <div class="card-content center">

                        <i class="material-icons large blue-text text-darken-4">
                            assignment
                        </i>

                        <h5>
                            Vous n'avez actuellement aucune mission.
                        </h5>

                        <p class="grey-text text-darken-1">
                            Les missions apparaitront ici des qu'un intervenant sera affecte a l'une de vos demandes.
                        </p>

                    </div>

                    <div class="card-action center">

                        <a href="mes-demandes.php"
                          class="btn modal-trigger waves-effect waves-light new-request-btn">

                            Mes demandes

                        </a>

                    </div>

                </div>

            <?php } ?>

        </div>

    </div>

    <!-- MATERIALIZE JS -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <script>

    document.addEventListener('DOMContentLoaded', function() {

        M.Modal.init(
            document.querySelectorAll('.modal')
        );

    });

    </script>

</body>

</html>
