<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1){

    header("Location: ../login.php");
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

function input_datetime_value($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    $timestamp = strtotime($value);

    if($timestamp === false){

        return "";

    }

    return date("Y-m-d\TH:i", $timestamp);
}

function mission_reference($id)
{
    return "MIS-" . str_pad((int)$id, 5, "0", STR_PAD_LEFT);
}

function redirect_missions()
{
    header("Location: missions.php");
    exit();
}

function missions_admin_pagination_url($page_number)
{
    $params = $_GET;
    $params["page"] = (int)$page_number;

    return "missions.php?" . http_build_query($params);
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

function mission_badge_class($status)
{
    if($status == "affectee"){

        return "blue";

    }

    if($status == "en_cours"){

        return "green";

    }

    if($status == "terminee"){

        return "grey";

    }

    if($status == "annulee"){

        return "red";

    }

    return "orange";
}

function payment_badge_class($status)
{
    if($status == "en_attente"){

        return "orange";

    }

    if($status == "en_traitement"){

        return "blue";

    }

    if($status == "paye"){

        return "green";

    }

    if($status == "echoue"){

        return "red";

    }

    return "grey";
}

function status_label($value)
{
    if($value == "affectee"){

        return "Affectee";

    }

    if($value == "en_cours"){

        return "En cours";

    }

    if($value == "terminee"){

        return "Terminee";

    }

    if($value == "annulee"){

        return "Annulee";

    }

    if($value == "en_attente"){

        return "En attente";

    }

    if($value == "en_traitement"){

        return "En traitement";

    }

    if($value == "paye"){

        return "Paye";

    }

    if($value == "echoue"){

        return "Echoue";

    }

    return display_value($value);
}

function get_mission_status($conn, $mission_id, &$start_time, &$end_time)
{
    $status = "";
    $start_time = "";
    $end_time = "";

    $sql = "
    SELECT mission_status, start_time, end_time
    FROM missions
    WHERE id = ?
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        die("Erreur SQL : " . mysqli_error($conn));

    }

    mysqli_stmt_bind_param($stmt, "i", $mission_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $status, $start_time, $end_time);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $status;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

    $mission_id = isset($_POST["mission_id"])
        ? (int)$_POST["mission_id"]
        : 0;

    if($mission_id <= 0){

        $_SESSION["error"] = "Mission invalide.";
        redirect_missions();

    }

    $current_start = "";
    $current_end = "";
    $current_status = get_mission_status($conn, $mission_id, $current_start, $current_end);

    if($current_status == ""){

        $_SESSION["error"] = "Mission introuvable.";
        redirect_missions();

    }

    if($action == "update_mission"){

        $start_time = isset($_POST["start_time"]) ? trim($_POST["start_time"]) : "";
        $end_time = isset($_POST["end_time"]) ? trim($_POST["end_time"]) : "";
        $notes = isset($_POST["notes"]) ? trim($_POST["notes"]) : "";

        if($start_time == ""){

            $start_time = NULL;

        }else{

            $start_time = str_replace("T", " ", $start_time) . ":00";

        }

        if($end_time == ""){

            $end_time = NULL;

        }else{

            $end_time = str_replace("T", " ", $end_time) . ":00";

        }

        if($notes == ""){

            $notes = NULL;

        }

        $sql = "
        UPDATE missions
        SET
            start_time = ?,
            end_time = ?,
            notes = ?,
            updated_at = NOW()
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "sssi", $start_time, $end_time, $notes, $mission_id);

        if(mysqli_stmt_execute($stmt)){

            $_SESSION["success"] = "Mission modifiee avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors de la modification de la mission.";

        }

        mysqli_stmt_close($stmt);
        redirect_missions();

    }

    if($action == "start_mission"){

        if($current_status != "affectee"){

            $_SESSION["error"] = "Cette mission ne peut pas etre demarree.";
            redirect_missions();

        }

        $sql = "
        UPDATE missions
        SET
            mission_status = 'en_cours',
            start_time = IF(start_time IS NULL, NOW(), start_time),
            updated_at = NOW()
        WHERE id = ?
        AND mission_status = 'affectee'
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $mission_id);

        if(mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0){

            $_SESSION["success"] = "Mission demarree avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors du demarrage de la mission.";

        }

        mysqli_stmt_close($stmt);
        redirect_missions();

    }

    if($action == "close_mission"){

        if($current_status != "en_cours"){

            $_SESSION["error"] = "Cette mission ne peut pas etre cloturee.";
            redirect_missions();

        }

        $sql = "
        UPDATE missions
        SET
            mission_status = 'terminee',
            end_time = IF(end_time IS NULL, NOW(), end_time),
            updated_at = NOW()
        WHERE id = ?
        AND mission_status = 'en_cours'
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $mission_id);

        if(mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0){

            $_SESSION["success"] = "Mission cloturee avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors de la cloture de la mission.";

        }

        mysqli_stmt_close($stmt);
        redirect_missions();

    }

    if($action == "cancel_mission"){

        if($current_status == "terminee"){

            $_SESSION["error"] = "Une mission terminee ne peut pas etre annulee.";
            redirect_missions();

        }

        $cancel_note = "[Annulation admin " . date("d/m/Y H:i") . "] Mission annulee depuis l'administration.";

        $sql = "
        UPDATE missions
        SET
            mission_status = 'annulee',
            notes = CONCAT(IFNULL(notes, ''), IF(notes IS NULL OR notes = '', '', '\n'), ?),
            updated_at = NOW()
        WHERE id = ?
        AND mission_status <> 'terminee'
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "si", $cancel_note, $mission_id);

        if(mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0){

            $_SESSION["success"] = "Mission annulee avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors de l'annulation de la mission.";

        }

        mysqli_stmt_close($stmt);
        redirect_missions();

    }

}

$stats = array(
    "total" => count_query($conn, "SELECT COUNT(*) AS total FROM missions"),
    "assigned" => count_query($conn, "SELECT COUNT(*) AS total FROM missions WHERE mission_status = 'affectee'"),
    "ongoing" => count_query($conn, "SELECT COUNT(*) AS total FROM missions WHERE mission_status = 'en_cours'"),
    "completed" => count_query($conn, "SELECT COUNT(*) AS total FROM missions WHERE mission_status = 'terminee'"),
    "cancelled" => count_query($conn, "SELECT COUNT(*) AS total FROM missions WHERE mission_status = 'annulee'"),
    "pending_payment" => count_query($conn, "SELECT COUNT(DISTINCT mission_id) AS total FROM payments WHERE status = 'en_attente'")
);

$missions = array();
$payments_by_mission = array();
$reviews_by_mission = array();
$limit = 50;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if($page < 1){

    $page = 1;

}

$total_missions = count_query($conn, "SELECT COUNT(*) AS total FROM missions");
$total_pages = (int)ceil($total_missions / $limit);

if($total_pages > 0 && $page > $total_pages){

    $page = $total_pages;

}

if($total_pages < 1){

    $page = 1;

}

$offset = ($page - 1) * $limit;

$sql = "
SELECT
    m.id,
    m.service_request_id,
    m.candidate_id,
    m.start_time,
    m.end_time,
    m.mission_status,
    m.notes,
    m.created_at,
    sr.title,
    sr.description,
    sr.location,
    sr.service_date,
    sr.duration,
    sr.budget,
    sr.urgency_level,
    sc.name AS category_name,
    cu.first_name AS client_first_name,
    cu.last_name AS client_last_name,
    cu.email AS client_email,
    cu.phone AS client_phone,
    iu.first_name AS candidate_first_name,
    iu.last_name AS candidate_last_name,
    iu.email AS candidate_email,
    iu.phone AS candidate_phone,
    c.city AS candidate_city,
    c.availability_status,
    p.status AS payment_status,
    p.amount AS payment_amount,
    p.currency AS payment_currency,
    p.payment_method,
    p.transaction_reference,
    p.paid_at,
    AVG(rv.note_generale) AS average_review,
    COUNT(rv.id) AS review_total
FROM missions m
INNER JOIN service_requests sr
ON sr.id = m.service_request_id
INNER JOIN clients cl
ON cl.id = sr.client_id
INNER JOIN users cu
ON cu.id = cl.user_id
INNER JOIN candidates c
ON c.id = m.candidate_id
INNER JOIN users iu
ON iu.id = c.user_id
LEFT JOIN service_categories sc
ON sc.id = sr.category_id
LEFT JOIN payments p
ON p.id = (
    SELECT MAX(p2.id)
    FROM payments p2
    WHERE p2.mission_id = m.id
)
LEFT JOIN service_reviews rv
ON rv.mission_id = m.id
GROUP BY
    m.id,
    m.service_request_id,
    m.candidate_id,
    m.start_time,
    m.end_time,
    m.mission_status,
    m.notes,
    m.created_at,
    sr.title,
    sr.description,
    sr.location,
    sr.service_date,
    sr.duration,
    sr.budget,
    sr.urgency_level,
    sc.name,
    cu.first_name,
    cu.last_name,
    cu.email,
    cu.phone,
    iu.first_name,
    iu.last_name,
    iu.email,
    iu.phone,
    c.city,
    c.availability_status,
    p.status,
    p.amount,
    p.currency,
    p.payment_method,
    p.transaction_reference,
    p.paid_at
ORDER BY m.created_at DESC
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

            $mission_id = (int)$row["id"];
            $missions[] = $row;
            $payments_by_mission[$mission_id] = array();
            $reviews_by_mission[$mission_id] = array();

        }

        mysqli_free_result($result);

    }

    mysqli_stmt_close($stmt);

}

if(count($missions) > 0){

    $result = mysqli_query($conn, "SELECT * FROM payments ORDER BY created_at DESC");

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $mission_id = isset($row["mission_id"]) ? (int)$row["mission_id"] : 0;

            if(isset($payments_by_mission[$mission_id])){

                $payments_by_mission[$mission_id][] = $row;

            }

        }

        mysqli_free_result($result);

    }

    $result = mysqli_query($conn, "SELECT * FROM service_reviews ORDER BY created_at DESC");

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $mission_id = isset($row["mission_id"]) ? (int)$row["mission_id"] : 0;

            if(isset($reviews_by_mission[$mission_id])){

                $reviews_by_mission[$mission_id][] = $row;

            }

        }

        mysqli_free_result($result);

    }

}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Missions | INFINITIA</title>

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

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

        .modal-wide{
            width:88%;
            max-height:88%;
        }

        .mission-section{
            background:#ffffff;
            border:1px solid #eeeeee;
            border-radius:14px;
            box-shadow:0 6px 18px rgba(0,0,0,.05);
            margin-bottom:18px;
            padding:18px;
        }

        .mission-section-title{
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
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
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
    </style>

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "missions";

    include("menuadmin.php");

    ?>

    <div class="main-content">

        <div class="topbar">
            <div>
                <div class="page-title">Missions</div>
                <div class="welcome-text">
                    Supervision des prestations apres affectation.
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
                    <div class="card-icon blue-gradient"><i class="material-icons">assignment</i></div>
                    <h5>Total missions</h5>
                    <h3><?php echo (int)$stats["total"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">engineering</i></div>
                    <h5>Affectees</h5>
                    <h3><?php echo (int)$stats["assigned"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">play_circle</i></div>
                    <h5>En cours</h5>
                    <h3><?php echo (int)$stats["ongoing"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">task_alt</i></div>
                    <h5>Terminees</h5>
                    <h3><?php echo (int)$stats["completed"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon pink-gradient"><i class="material-icons">cancel</i></div>
                    <h5>Annulees</h5>
                    <h3><?php echo (int)$stats["cancelled"]; ?></h3>
                </div>
            </div>
            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">payments</i></div>
                    <h5>Paiements attente</h5>
                    <h3><?php echo (int)$stats["pending_payment"]; ?></h3>
                </div>
            </div>
        </div>

        <?php if(count($missions) > 0){ ?>

            <div class="table-card">
                <div class="table-title">Liste des missions</div>

                <table class="highlight responsive-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Client</th>
                            <th>Intervenant</th>
                            <th>Service</th>
                            <th>Date prevue</th>
                            <th>Debut</th>
                            <th>Fin</th>
                            <th>Statut mission</th>
                            <th>Statut paiement</th>
                            <th>Evaluation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($missions as $mission){ ?>
                            <?php
                            $mission_id = (int)$mission["id"];
                            $client_name = trim($mission["client_first_name"] . " " . $mission["client_last_name"]);
                            $candidate_name = trim($mission["candidate_first_name"] . " " . $mission["candidate_last_name"]);
                            $mission_status = isset($mission["mission_status"]) ? $mission["mission_status"] : "";
                            $payment_status = isset($mission["payment_status"]) ? $mission["payment_status"] : "";
                            $average_review = isset($mission["average_review"]) ? $mission["average_review"] : NULL;
                            ?>
                            <tr>
                                <td><?php echo safe_text(mission_reference($mission_id)); ?></td>
                                <td><?php echo safe_text(display_value($client_name)); ?></td>
                                <td><?php echo safe_text(display_value($candidate_name)); ?></td>
                                <td><?php echo safe_text(display_value($mission["title"])); ?></td>
                                <td><?php echo safe_text(format_date_fr($mission["service_date"], false)); ?></td>
                                <td><?php echo safe_text(format_date_fr($mission["start_time"], true)); ?></td>
                                <td><?php echo safe_text(format_date_fr($mission["end_time"], true)); ?></td>
                                <td>
                                    <span class="new badge <?php echo safe_text(mission_badge_class($mission_status)); ?>" data-badge-caption="">
                                        <?php echo safe_text(status_label($mission_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="new badge <?php echo safe_text(payment_badge_class($payment_status)); ?>" data-badge-caption="">
                                        <?php echo safe_text($payment_status == "" ? "Non genere" : status_label($payment_status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($average_review !== NULL){ ?>
                                        <span class="new badge green" data-badge-caption="">
                                            <?php echo safe_text(number_format((float)$average_review, 1)); ?>/5
                                        </span>
                                    <?php }else{ ?>
                                        <span class="new badge grey" data-badge-caption="">Non evaluee</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="actions-wrap">
                                        <a href="#viewMission<?php echo $mission_id; ?>"
                                           class="btn-small green modal-trigger">
                                            Voir
                                        </a>
                                        <a href="#editMission<?php echo $mission_id; ?>"
                                           class="btn-small blue modal-trigger">
                                            Modifier
                                        </a>

                                        <?php if($mission_status == "affectee"){ ?>
                                            <form action="missions.php" method="POST">
                                                <input type="hidden" name="action" value="start_mission">
                                                <input type="hidden" name="mission_id" value="<?php echo $mission_id; ?>">
                                                <button type="submit" class="btn-small orange">Demarrer</button>
                                            </form>
                                        <?php } ?>

                                        <?php if($mission_status == "en_cours"){ ?>
                                            <form action="missions.php" method="POST">
                                                <input type="hidden" name="action" value="close_mission">
                                                <input type="hidden" name="mission_id" value="<?php echo $mission_id; ?>">
                                                <button type="submit" class="btn-small grey darken-1">Cloturer</button>
                                            </form>
                                        <?php } ?>

                                        <?php if($mission_status != "terminee" && $mission_status != "annulee"){ ?>
                                            <a href="#cancelMission<?php echo $mission_id; ?>"
                                               class="btn-small red modal-trigger">
                                                Annuler
                                            </a>
                                        <?php } ?>
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
                                    <a href="<?php echo safe_text(missions_admin_pagination_url($page - 1)); ?>">Precedent</a>
                                </li>
                            <?php }else{ ?>
                                <li class="disabled"><a href="#!">Precedent</a></li>
                            <?php } ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if($start_page > 1){
                            ?>
                                <li class="waves-effect"><a href="<?php echo safe_text(missions_admin_pagination_url(1)); ?>">1</a></li>
                                <?php if($start_page > 2){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                            <?php } ?>

                            <?php for($page_number = $start_page; $page_number <= $end_page; $page_number++){ ?>
                                <?php if($page_number == $page){ ?>
                                    <li class="active"><a href="#!"><?php echo (int)$page_number; ?></a></li>
                                <?php }else{ ?>
                                    <li class="waves-effect">
                                        <a href="<?php echo safe_text(missions_admin_pagination_url($page_number)); ?>"><?php echo (int)$page_number; ?></a>
                                    </li>
                                <?php } ?>
                            <?php } ?>

                            <?php if($end_page < $total_pages){ ?>
                                <?php if($end_page < $total_pages - 1){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                                <li class="waves-effect"><a href="<?php echo safe_text(missions_admin_pagination_url($total_pages)); ?>"><?php echo (int)$total_pages; ?></a></li>
                            <?php } ?>

                            <?php if($page < $total_pages){ ?>
                                <li class="waves-effect">
                                    <a href="<?php echo safe_text(missions_admin_pagination_url($page + 1)); ?>">Suivant</a>
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
                    <i class="material-icons large blue-text text-darken-4">assignment</i>
                    <h5>Aucune mission n'existe pour le moment.</h5>
                </div>
            </div>

        <?php } ?>

    </div>
</div>

<?php foreach($missions as $mission){ ?>
    <?php
    $mission_id = (int)$mission["id"];
    $client_name = trim($mission["client_first_name"] . " " . $mission["client_last_name"]);
    $candidate_name = trim($mission["candidate_first_name"] . " " . $mission["candidate_last_name"]);
    $mission_status = isset($mission["mission_status"]) ? $mission["mission_status"] : "";
    $mission_payments = isset($payments_by_mission[$mission_id]) ? $payments_by_mission[$mission_id] : array();
    $mission_reviews = isset($reviews_by_mission[$mission_id]) ? $reviews_by_mission[$mission_id] : array();
    ?>

    <div id="viewMission<?php echo $mission_id; ?>" class="modal modal-fixed-footer modal-wide">
        <div class="modal-content">
            <h4><?php echo safe_text(mission_reference($mission_id)); ?></h4>

            <div class="mission-section">
                <h5 class="mission-section-title"><i class="material-icons">person</i>Client</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Nom</span><?php echo safe_text(display_value($client_name)); ?></div>
                    <div class="detail-item"><span class="detail-label">Telephone</span><?php echo safe_text(display_value($mission["client_phone"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Email</span><?php echo safe_text(display_value($mission["client_email"])); ?></div>
                </div>
            </div>

            <div class="mission-section">
                <h5 class="mission-section-title"><i class="material-icons">engineering</i>Intervenant</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Nom</span><?php echo safe_text(display_value($candidate_name)); ?></div>
                    <div class="detail-item"><span class="detail-label">Telephone</span><?php echo safe_text(display_value($mission["candidate_phone"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Ville</span><?php echo safe_text(display_value($mission["candidate_city"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Disponibilite</span><?php echo safe_text(display_value($mission["availability_status"])); ?></div>
                </div>
            </div>

            <div class="mission-section">
                <h5 class="mission-section-title"><i class="material-icons">request_quote</i>Demande</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Categorie</span><?php echo safe_text(display_value($mission["category_name"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Titre</span><?php echo safe_text(display_value($mission["title"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Lieu</span><?php echo safe_text(display_value($mission["location"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Date prevue</span><?php echo safe_text(format_date_fr($mission["service_date"], false)); ?></div>
                    <div class="detail-item"><span class="detail-label">Duree</span><?php echo safe_text(display_value($mission["duration"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Budget</span><?php echo safe_text(number_format((float)$mission["budget"], 2)); ?></div>
                    <div class="detail-item"><span class="detail-label">Urgence</span><?php echo safe_text(display_value($mission["urgency_level"])); ?></div>
                </div>
                <p style="margin-top:16px;"><strong>Description :</strong><br><?php echo nl2br(safe_text(display_value($mission["description"]))); ?></p>
            </div>

            <div class="mission-section">
                <h5 class="mission-section-title"><i class="material-icons">assignment</i>Mission</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Debut</span><?php echo safe_text(format_date_fr($mission["start_time"], true)); ?></div>
                    <div class="detail-item"><span class="detail-label">Fin</span><?php echo safe_text(format_date_fr($mission["end_time"], true)); ?></div>
                    <div class="detail-item"><span class="detail-label">Statut</span><?php echo safe_text(status_label($mission_status)); ?></div>
                    <div class="detail-item"><span class="detail-label">Creation</span><?php echo safe_text(format_date_fr($mission["created_at"], true)); ?></div>
                </div>
                <p style="margin-top:16px;"><strong>Notes :</strong><br><?php echo nl2br(safe_text(display_value($mission["notes"]))); ?></p>
            </div>

            <div class="mission-section">
                <h5 class="mission-section-title"><i class="material-icons">payments</i>Paiement lie</h5>
                <?php if(count($mission_payments) > 0){ ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Montant</th>
                                <th>Devise</th>
                                <th>Methode</th>
                                <th>Statut</th>
                                <th>Reference</th>
                                <th>Date paiement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($mission_payments as $payment){ ?>
                                <tr>
                                    <td><?php echo safe_text(number_format((float)$payment["amount"], 2)); ?></td>
                                    <td><?php echo safe_text(display_value($payment["currency"])); ?></td>
                                    <td><?php echo safe_text(display_value($payment["payment_method"])); ?></td>
                                    <td><?php echo safe_text(status_label($payment["status"])); ?></td>
                                    <td><?php echo safe_text(display_value($payment["transaction_reference"])); ?></td>
                                    <td><?php echo safe_text(format_date_fr($payment["paid_at"], true)); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php }else{ ?>
                    <p class="grey-text">Aucun paiement genere.</p>
                <?php } ?>
            </div>

            <div class="mission-section">
                <h5 class="mission-section-title"><i class="material-icons">star</i>Evaluation liee</h5>
                <?php if(count($mission_reviews) > 0){ ?>
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
                            <?php foreach($mission_reviews as $review){ ?>
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
                    <p class="grey-text">Mission non evaluee.</p>
                <?php } ?>
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Fermer</a>
        </div>
    </div>

    <div id="editMission<?php echo $mission_id; ?>" class="modal modal-fixed-footer">
        <form action="missions.php" method="POST">
            <input type="hidden" name="action" value="update_mission">
            <input type="hidden" name="mission_id" value="<?php echo $mission_id; ?>">

            <div class="modal-content">
                <h4>Modifier la mission</h4>

                <div class="input-field">
                    <input type="datetime-local"
                           name="start_time"
                           id="start_time<?php echo $mission_id; ?>"
                           value="<?php echo safe_text(input_datetime_value($mission["start_time"])); ?>">
                    <label class="active" for="start_time<?php echo $mission_id; ?>">Debut</label>
                </div>

                <div class="input-field">
                    <input type="datetime-local"
                           name="end_time"
                           id="end_time<?php echo $mission_id; ?>"
                           value="<?php echo safe_text(input_datetime_value($mission["end_time"])); ?>">
                    <label class="active" for="end_time<?php echo $mission_id; ?>">Fin</label>
                </div>

                <div class="input-field">
                    <textarea name="notes"
                              id="notes<?php echo $mission_id; ?>"
                              class="materialize-textarea"><?php echo safe_text($mission["notes"]); ?></textarea>
                    <label class="active" for="notes<?php echo $mission_id; ?>">Notes</label>
                </div>
            </div>

            <div class="modal-footer">
                <a href="#!" class="modal-close btn-flat">Annuler</a>
                <button type="submit" class="btn waves-effect waves-light">Enregistrer</button>
            </div>
        </form>
    </div>

    <div id="cancelMission<?php echo $mission_id; ?>" class="modal">
        <form action="missions.php" method="POST">
            <input type="hidden" name="action" value="cancel_mission">
            <input type="hidden" name="mission_id" value="<?php echo $mission_id; ?>">

            <div class="modal-content">
                <h4>Annuler la mission</h4>
                <p>
                    Confirmez-vous l'annulation de la mission
                    <strong><?php echo safe_text(mission_reference($mission_id)); ?></strong> ?
                </p>
                <p class="grey-text">
                    Les notes existantes seront conservees et une note d'annulation sera ajoutee.
                </p>
            </div>

            <div class="modal-footer">
                <a href="#!" class="modal-close btn-flat">Fermer</a>
                <button type="submit" class="btn red waves-effect waves-light">Annuler la mission</button>
            </div>
        </form>
    </div>
<?php } ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    M.Modal.init(document.querySelectorAll('.modal'));
    M.updateTextFields();
});
</script>

</body>
</html>
