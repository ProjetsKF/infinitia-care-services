<?php

session_start();

require_once("../config/database.php");

/* =====================================
   VERIFICATION CONNEXION
===================================== */

if(!isset($_SESSION['user_id']))
{
    header("Location: " . app_url("login"));
    exit();
}

$user_id = (int)$_SESSION['user_id'];

function missions_pagination_url($page_number)
{
    $params = $_GET;
    $params["page"] = (int)$page_number;

    return app_url_with_query("intervenant/missions", $params);
}

/* =====================================
   RECUPERATION CANDIDAT CONNECTE
===================================== */

$candidate_id = 0;

$sql = "
SELECT id
FROM candidates
WHERE user_id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt)
{
    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($result))
    {
        $candidate_id = (int)$row['id'];
    }

    mysqli_stmt_close($stmt);
}

/* =====================================
   MISSIONS EN COURS
===================================== */

$missions_en_cours = 0;

$sql = "
SELECT COUNT(*) AS total
FROM missions
WHERE candidate_id = ?
AND mission_status = 'en_cours'
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt)
{
    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $candidate_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($result))
    {
        $missions_en_cours = (int)$row['total'];
    }

    mysqli_stmt_close($stmt);
}

/* =====================================
   MISSIONS TERMINEES
===================================== */

$missions_terminees = 0;

$sql = "
SELECT COUNT(*) AS total
FROM missions
WHERE candidate_id = ?
AND mission_status = 'terminee'
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt)
{
    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $candidate_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($result))
    {
        $missions_terminees = (int)$row['total'];
    }

    mysqli_stmt_close($stmt);
}

/* =====================================
   LISTE DES MISSIONS
===================================== */

$missions = array();
$limit = 20;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if($page < 1)
{
    $page = 1;
}

$total_missions = 0;

$sql = "
SELECT COUNT(*) AS total
FROM missions
WHERE candidate_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt)
{
    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $candidate_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($result))
    {
        $total_missions = (int)$row['total'];
    }

    mysqli_stmt_close($stmt);
}

$total_pages = (int)ceil($total_missions / $limit);

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

SELECT

    m.id,
    m.start_time,
    m.end_time,
    m.mission_status,
    m.notes,

    sr.title,
    sr.description,
    sr.location,
    sr.service_date,
    sr.duration,
    sr.budget,
    sr.urgency_level

FROM missions m

INNER JOIN service_requests sr
    ON sr.id = m.service_request_id

WHERE m.candidate_id = ?

ORDER BY sr.service_date DESC

LIMIT ?
OFFSET ?

";

$stmt = mysqli_prepare($conn, $sql);

if($stmt)
{
    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $candidate_id,
        $limit,
        $offset
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while($row = mysqli_fetch_assoc($result))
    {
        $missions[] = $row;
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

        Mes misions | INFINITIA

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

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "missions";

    include("menuin.php");

    ?>

<div class="main-content">

    <div class="topbar">

        <div class="page-title">
            Mes Missions
        </div>

    </div>

    <div class="row intervenant-stat-grid">

        <div class="col s12 m6">

            <div class="card green darken-2 white-text">

                <div class="card-content">

                    <span class="card-title">
                        Missions en cours
                    </span>

                    <h3>
                        <?php echo $missions_en_cours; ?>
                    </h3>

                </div>

            </div>

        </div>

        <div class="col s12 m6">

            <div class="card blue darken-3 white-text">

                <div class="card-content">

                    <span class="card-title">
                        Missions terminées
                    </span>

                    <h3>
                        <?php echo $missions_terminees; ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>

    <div class="table-card">

        <div class="table-title">
            Liste de mes missions
        </div>

        <table class="highlight responsive-table intervenant-table mobile-card-table">

            <thead>

                <tr>

                    <th>Référence</th>
                    <th>Mission</th>
                    <th>Lieu</th>
                    <th>Date du service</th>
                    <th>Durée</th>
                    <th>Statut</th>
                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

            <?php if(count($missions) > 0){ ?>

                <?php foreach($missions as $mission){ ?>

                    <tr class="mobile-card-row">

                        <td data-label="Référence">
                            #<?php echo $mission['id']; ?>
                        </td>

                        <td data-label="Mission">
                            <?php echo htmlspecialchars($mission['title']); ?>
                        </td>

                        <td data-label="Lieu">
                            <?php echo htmlspecialchars($mission['location']); ?>
                        </td>

                        <td data-label="Date du service">

                            <?php

                            if(!empty($mission['service_date']))
                            {
                                echo date(
                                    'd/m/Y',
                                    strtotime($mission['service_date'])
                                );
                            }

                            ?>

                        </td>

                        <td data-label="Durée">

                            <?php

                            if(!empty($mission['duration']))
                            {
                                echo (int)$mission['duration'] . ' h';
                            }

                            ?>

                        </td>

                        <td data-label="Statut">

                            <?php

                            if($mission['mission_status'] == 'en_attente')
                            {
                                echo '<span class="new badge orange" data-badge-caption="">En attente</span>';
                            }
                            elseif($mission['mission_status'] == 'affectee')
                            {
                                echo '<span class="new badge blue" data-badge-caption="">Affectée</span>';
                            }
                            elseif($mission['mission_status'] == 'en_cours')
                            {
                                echo '<span class="new badge green" data-badge-caption="">En cours</span>';
                            }
                            elseif($mission['mission_status'] == 'terminee')
                            {
                                echo '<span class="new badge grey" data-badge-caption="">Terminée</span>';
                            }

                            ?>

                        </td>

                        <td data-label="Action">

                            <a
                            href="#modalMission<?php echo $mission['id']; ?>"
                            class="btn-small blue modal-trigger">

                                <i class="material-icons">
                                    visibility
                                </i>

                            </a>

                        </td>

                    </tr>

                <?php } ?>

            <?php }else{ ?>

                <tr class="intervenant-empty-row">

                    <td colspan="7" class="center-align intervenant-empty-state-cell">
                        <i class="material-icons" aria-hidden="true">assignment</i>
                        <span>Aucune mission ne vous a encore été attribuée.</span>

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
                            <a href="<?php echo htmlspecialchars(missions_pagination_url($page - 1)); ?>">
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
                            <a href="<?php echo htmlspecialchars(missions_pagination_url(1)); ?>">1</a>
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
                                <a href="<?php echo htmlspecialchars(missions_pagination_url($page_number)); ?>">
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
                            <a href="<?php echo htmlspecialchars(missions_pagination_url($total_pages)); ?>">
                                <?php echo (int)$total_pages; ?>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if($page < $total_pages){ ?>
                        <li class="waves-effect">
                            <a href="<?php echo htmlspecialchars(missions_pagination_url($page + 1)); ?>">
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


<?php if(count($missions) > 0){ ?>

    <?php foreach($missions as $mission){ ?>

        <div
        id="modalMission<?php echo $mission['id']; ?>"
        class="modal">

            <div class="modal-content">

                <h4>

                    <?php echo htmlspecialchars($mission['title']); ?>

                </h4>

                <p>

                    <strong>Description :</strong><br>

                    <?php echo nl2br(htmlspecialchars($mission['description'])); ?>

                </p>

                <br>

                <p>

                    <strong>Lieu :</strong>

                    <?php echo htmlspecialchars($mission['location']); ?>

                </p>

                <p>

                    <strong>Date :</strong>

                    <?php

                    if(!empty($mission['service_date']))
                    {
                        echo date(
                            'd/m/Y',
                            strtotime($mission['service_date'])
                        );
                    }

                    ?>

                </p>

                <p>

                    <strong>Durée :</strong>

                    <?php echo (int)$mission['duration']; ?> heure(s)

                </p>

                <p>

                    <strong>Budget :</strong>

                    <?php echo number_format($mission['budget'], 2); ?>

                </p>

                <p>

                    <strong>Niveau d'urgence :</strong>

                    <?php echo ucfirst($mission['urgency_level']); ?>

                </p>

                <?php if(!empty($mission['notes'])){ ?>

                    <p>

                        <strong>Notes :</strong><br>

                        <?php echo nl2br(htmlspecialchars($mission['notes'])); ?>

                    </p>

                <?php } ?>

            </div>

            <div class="modal-footer">

                <a
                href="#!"
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

    M.Modal.init(elems);

});

</script>

</body>
</html>
