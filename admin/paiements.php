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

function payment_reference($id)
{
    return "PAY-" . str_pad((int)$id, 5, "0", STR_PAD_LEFT);
}

function mission_reference($id)
{
    return "MIS-" . str_pad((int)$id, 5, "0", STR_PAD_LEFT);
}

function redirect_paiements()
{
    header("Location: " . app_url("admin/paiements"));
    exit();
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

function sum_query($conn, $sql)
{
    $total = 0;
    $result = mysqli_query($conn, $sql);

    if($result){

        $row = mysqli_fetch_assoc($result);

        if($row && isset($row["total"]) && $row["total"] !== NULL){

            $total = (float)$row["total"];

        }

        mysqli_free_result($result);

    }

    return $total;
}

function bind_params($stmt, $types, $params)
{
    if($types == ""){

        return true;

    }

    $bind_names = array();
    $bind_names[] = $types;
    $i = 0;

    for($i = 0; $i < count($params); $i++){

        $bind_names[] = &$params[$i];

    }

    return call_user_func_array(array($stmt, "bind_param"), $bind_names);
}

function status_label($status)
{
    if($status == "en_attente"){

        return "En attente";

    }

    if($status == "en_traitement"){

        return "En traitement";

    }

    if($status == "paye"){

        return "Paye";

    }

    if($status == "echoue"){

        return "Echoue";

    }

    if($status == "annule"){

        return "Annule";

    }

    return display_value($status);
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

function pagination_url($page, $search, $status, $periode)
{
    $params = array("page" => (int)$page);

    if($search != ""){

        $params["search"] = $search;

    }

    if($status != ""){

        $params["status"] = $status;

    }

    if($periode != ""){

        $params["periode"] = $periode;

    }

    return app_url_with_query("admin/paiements", $params);
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"]) ? $_POST["action"] : "";
    $payment_id = isset($_POST["payment_id"]) ? (int)$_POST["payment_id"] : 0;

    if($payment_id <= 0){

        $_SESSION["error"] = "Paiement invalide.";
        redirect_paiements();

    }

    $current_status = "";
    $existing_id = 0;

    $sql = "
    SELECT id, status
    FROM payments
    WHERE id = ?
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        die("Erreur SQL : " . mysqli_error($conn));

    }

    mysqli_stmt_bind_param($stmt, "i", $payment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $existing_id, $current_status);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if($existing_id <= 0){

        $_SESSION["error"] = "Paiement introuvable.";
        redirect_paiements();

    }

    if($current_status != "en_attente" && $current_status != "en_traitement"){

        $_SESSION["error"] = "Ce paiement ne peut plus etre modifie.";
        redirect_paiements();

    }

    if($action == "validate_payment"){

        $sql = "
        UPDATE payments
        SET
            status = 'paye',
            paid_at = IF(paid_at IS NULL, NOW(), paid_at)
        WHERE id = ?
        AND status IN ('en_attente', 'en_traitement')
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $payment_id);

        if(mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0){

            $_SESSION["success"] = "Paiement valide avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors de la validation du paiement.";

        }

        mysqli_stmt_close($stmt);
        redirect_paiements();

    }

    if($action == "reject_payment"){

        $sql = "
        UPDATE payments
        SET status = 'echoue'
        WHERE id = ?
        AND status IN ('en_attente', 'en_traitement')
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $payment_id);

        if(mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0){

            $_SESSION["success"] = "Paiement rejete avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors du rejet du paiement.";

        }

        mysqli_stmt_close($stmt);
        redirect_paiements();

    }

    $_SESSION["error"] = "Action invalide.";
    redirect_paiements();

}

$stats = array(
    "total" => count_query($conn, "SELECT COUNT(*) AS total FROM payments"),
    "pending" => count_query($conn, "SELECT COUNT(*) AS total FROM payments WHERE status = 'en_attente'"),
    "processing" => count_query($conn, "SELECT COUNT(*) AS total FROM payments WHERE status = 'en_traitement'"),
    "paid" => count_query($conn, "SELECT COUNT(*) AS total FROM payments WHERE status = 'paye'"),
    "failed" => count_query($conn, "SELECT COUNT(*) AS total FROM payments WHERE status = 'echoue'"),
    "collected" => sum_query($conn, "SELECT SUM(amount) AS total FROM payments WHERE status = 'paye'")
);

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$status_filter = isset($_GET["status"]) ? trim($_GET["status"]) : "";
$periode = isset($_GET["periode"]) ? trim($_GET["periode"]) : "";
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$per_page = 10;

if($page <= 0){

    $page = 1;

}

if($status_filter != "en_attente" && $status_filter != "en_traitement" && $status_filter != "paye" && $status_filter != "echoue"){

    $status_filter = "";

}

if($periode != "today" && $periode != "week" && $periode != "month" && $periode != "year"){

    $periode = "";

}

$where_parts = array();
$params = array();
$types = "";

if($search != ""){

    $where_parts[] = "(
        cu.first_name LIKE ?
        OR cu.last_name LIKE ?
        OR iu.first_name LIKE ?
        OR iu.last_name LIKE ?
        OR cu.phone LIKE ?
        OR iu.phone LIKE ?
        OR p.phone_number LIKE ?
        OR p.transaction_reference LIKE ?
    )";

    $search_like = "%" . $search . "%";
    $i = 0;

    for($i = 0; $i < 8; $i++){

        $params[] = $search_like;

    }

    $types .= "ssssssss";

}

if($status_filter != ""){

    $where_parts[] = "p.status = ?";
    $params[] = $status_filter;
    $types .= "s";

}

if($periode == "today"){

    $where_parts[] = "DATE(p.created_at) = CURDATE()";

}elseif($periode == "week"){

    $where_parts[] = "YEARWEEK(p.created_at, 1) = YEARWEEK(CURDATE(), 1)";

}elseif($periode == "month"){

    $where_parts[] = "YEAR(p.created_at) = YEAR(CURDATE()) AND MONTH(p.created_at) = MONTH(CURDATE())";

}elseif($periode == "year"){

    $where_parts[] = "YEAR(p.created_at) = YEAR(CURDATE())";

}

$where_sql = "";

if(count($where_parts) > 0){

    $where_sql = "WHERE " . implode(" AND ", $where_parts);

}

$from_sql = "
FROM payments p
INNER JOIN missions m
ON m.id = p.mission_id
INNER JOIN service_requests sr
ON sr.id = m.service_request_id
INNER JOIN clients cl
ON cl.id = p.client_id
INNER JOIN users cu
ON cu.id = cl.user_id
INNER JOIN candidates ca
ON ca.id = m.candidate_id
INNER JOIN users iu
ON iu.id = ca.user_id
";

$total_filtered = 0;

$sql = "
SELECT COUNT(*) AS total
" . $from_sql . "
" . $where_sql;

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

bind_params($stmt, $types, $params);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total_filtered);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$total_pages = (int)ceil($total_filtered / $per_page);

if($total_pages < 1){

    $total_pages = 1;

}

if($page > $total_pages){

    $page = $total_pages;

}

$offset = ($page - 1) * $per_page;
$list_params = $params;
$list_types = $types . "ii";
$list_params[] = $per_page;
$list_params[] = $offset;
$payments = array();

$sql = "
SELECT
    p.id,
    p.mission_id,
    p.client_id,
    p.amount,
    p.currency,
    p.payment_method,
    p.transaction_reference,
    p.external_transaction_id,
    p.phone_number,
    p.status,
    p.paid_at,
    p.created_at,
    sr.title,
    sr.location,
    sr.service_date,
    sr.budget,
    cu.first_name AS client_first_name,
    cu.last_name AS client_last_name,
    cu.email AS client_email,
    cu.phone AS client_phone,
    iu.first_name AS candidate_first_name,
    iu.last_name AS candidate_last_name,
    iu.phone AS candidate_phone
" . $from_sql . "
" . $where_sql . "
ORDER BY p.created_at DESC
LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

bind_params($stmt, $list_types, $list_params);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result(
    $stmt,
    $result_id,
    $result_mission_id,
    $result_client_id,
    $result_amount,
    $result_currency,
    $result_payment_method,
    $result_transaction_reference,
    $result_external_transaction_id,
    $result_phone_number,
    $result_status,
    $result_paid_at,
    $result_created_at,
    $result_title,
    $result_location,
    $result_service_date,
    $result_budget,
    $result_client_first_name,
    $result_client_last_name,
    $result_client_email,
    $result_client_phone,
    $result_candidate_first_name,
    $result_candidate_last_name,
    $result_candidate_phone
);

while(mysqli_stmt_fetch($stmt)){

    $payments[] = array(
        "id" => $result_id,
        "mission_id" => $result_mission_id,
        "client_id" => $result_client_id,
        "amount" => $result_amount,
        "currency" => $result_currency,
        "payment_method" => $result_payment_method,
        "transaction_reference" => $result_transaction_reference,
        "external_transaction_id" => $result_external_transaction_id,
        "phone_number" => $result_phone_number,
        "status" => $result_status,
        "paid_at" => $result_paid_at,
        "created_at" => $result_created_at,
        "title" => $result_title,
        "location" => $result_location,
        "service_date" => $result_service_date,
        "budget" => $result_budget,
        "client_first_name" => $result_client_first_name,
        "client_last_name" => $result_client_last_name,
        "client_email" => $result_client_email,
        "client_phone" => $result_client_phone,
        "candidate_first_name" => $result_candidate_first_name,
        "candidate_last_name" => $result_candidate_last_name,
        "candidate_phone" => $result_candidate_phone
    );

}

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(dirname(__DIR__) . "/includes/pwa-head.php"); ?>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Paiements | INFINITIA</title>

    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

    <style>
        .admin-summary-card{
            align-items:center;
            background:#ffffff;
            border-radius:14px;
            box-shadow:0 8px 22px rgba(0,0,0,.08);
            display:flex;
            gap:14px;
            min-height:104px;
            padding:16px;
        }

        .admin-summary-card h5{
            color:#2f3b55;
            font-size:14px;
            font-weight:600;
            margin:0 0 5px;
        }

        .admin-summary-card h3{
            color:#081f78;
            font-size:27px;
            font-weight:800;
            margin:0;
        }

        .search-card{
            background:#ffffff;
            border-radius:14px;
            box-shadow:0 8px 22px rgba(0,0,0,.08);
            margin-bottom:22px;
            padding:18px;
        }

        .modal-wide{
            width:82%;
            max-height:86%;
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

        .payment-section{
            background:#ffffff;
            border:1px solid #eeeeee;
            border-radius:14px;
            box-shadow:0 6px 18px rgba(0,0,0,.05);
            margin-bottom:18px;
            padding:18px;
        }

        .section-title{
            align-items:center;
            color:#081f78;
            display:flex;
            font-size:18px;
            font-weight:700;
            gap:8px;
            margin:0 0 14px;
        }

        .actions-wrap{
            display:flex;
            flex-wrap:wrap;
            gap:7px;
        }

        .actions-wrap form{
            margin:0;
        }

        .pagination-wrap{
            align-items:center;
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            justify-content:center;
            margin-top:22px;
        }
    </style>

</head>

<body class="admin-module">

<div class="dashboard">

    <?php

    $current_page = "paiements";

    include("menuadmin.php");

    ?>

    <div class="main-content">

        <div class="topbar">
            <div>
                <div class="page-title">Paiements</div>
                <div class="welcome-text">
                    Suivi et controle administratif des paiements clients.
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

        <div class="row intervenant-stat-grid admin-stat-grid">
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">payments</i></div>
                    <div><h5>Total paiements</h5><h3><?php echo (int)$stats["total"]; ?></h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">schedule</i></div>
                    <div><h5>Paiements en attente</h5><h3><?php echo (int)$stats["pending"]; ?></h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">sync</i></div>
                    <div><h5>Paiements en traitement</h5><h3><?php echo (int)$stats["processing"]; ?></h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">task_alt</i></div>
                    <div><h5>Paiements payes</h5><h3><?php echo (int)$stats["paid"]; ?></h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon pink-gradient"><i class="material-icons">cancel</i></div>
                    <div><h5>Paiements echoues</h5><h3><?php echo (int)$stats["failed"]; ?></h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">account_balance_wallet</i></div>
                    <div><h5>Montant encaisse</h5><h3><?php echo safe_text(number_format((float)$stats["collected"], 2)); ?></h3></div>
                </div>
            </div>
        </div>

        <div class="search-card">
            <form action="<?php echo app_url_html("admin/paiements"); ?>" method="GET">
                <div class="row" style="margin-bottom:0;">
                    <div class="input-field col s12 l4">
                        <i class="material-icons prefix">search</i>
                        <input type="text" name="search" id="search" value="<?php echo safe_text($search); ?>">
                        <label for="search" class="<?php if($search != ""){ echo "active"; } ?>">
                            Client, intervenant, telephone, reference
                        </label>
                    </div>
                    <div class="input-field col s12 m6 l3">
                        <select name="status">
                            <option value="" <?php if($status_filter == ""){ echo "selected"; } ?>>Tous les statuts</option>
                            <option value="en_attente" <?php if($status_filter == "en_attente"){ echo "selected"; } ?>>en_attente</option>
                            <option value="en_traitement" <?php if($status_filter == "en_traitement"){ echo "selected"; } ?>>en_traitement</option>
                            <option value="paye" <?php if($status_filter == "paye"){ echo "selected"; } ?>>paye</option>
                            <option value="echoue" <?php if($status_filter == "echoue"){ echo "selected"; } ?>>echoue</option>
                        </select>
                        <label>Statut</label>
                    </div>
                    <div class="input-field col s12 m6 l3">
                        <select name="periode">
                            <option value="" <?php if($periode == ""){ echo "selected"; } ?>>Toutes les periodes</option>
                            <option value="today" <?php if($periode == "today"){ echo "selected"; } ?>>Aujourd'hui</option>
                            <option value="week" <?php if($periode == "week"){ echo "selected"; } ?>>Cette semaine</option>
                            <option value="month" <?php if($periode == "month"){ echo "selected"; } ?>>Ce mois</option>
                            <option value="year" <?php if($periode == "year"){ echo "selected"; } ?>>Cette annee</option>
                        </select>
                        <label>Periode</label>
                    </div>
                    <div class="col s12 l2" style="padding-top:22px;">
                        <button type="submit" class="btn waves-effect waves-light">
                            Filtrer
                        </button>
                        <a href="<?php echo app_url_html("admin/paiements"); ?>" class="btn-flat">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <?php if(count($payments) > 0){ ?>
            <div class="table-card">
                <div class="table-title">
                    Liste des paiements
                    <span class="grey-text" style="font-size:14px; font-weight:400;">
                        (<?php echo (int)$total_filtered; ?> resultat(s))
                    </span>
                </div>

                <table class="highlight responsive-table intervenant-table mobile-card-table admin-responsive-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Mission</th>
                            <th>Client</th>
                            <th>Intervenant</th>
                            <th>Service</th>
                            <th>Montant</th>
                            <th>Methode</th>
                            <th>Statut</th>
                            <th>Date paiement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $payment){ ?>
                            <?php
                            $payment_id = (int)$payment["id"];
                            $status = isset($payment["status"]) ? $payment["status"] : "";
                            $client_name = trim($payment["client_first_name"] . " " . $payment["client_last_name"]);
                            $candidate_name = trim($payment["candidate_first_name"] . " " . $payment["candidate_last_name"]);
                            ?>
                            <tr class="mobile-card-row">
                                <td data-label="Référence"><?php echo safe_text(payment_reference($payment_id)); ?></td>
                                <td data-label="Mission"><?php echo safe_text(mission_reference((int)$payment["mission_id"])); ?></td>
                                <td data-label="Client"><?php echo safe_text(display_value($client_name)); ?></td>
                                <td data-label="Intervenant"><?php echo safe_text(display_value($candidate_name)); ?></td>
                                <td data-label="Mission"><?php echo safe_text(display_value($payment["title"])); ?></td>
                                <td data-label="Montant">
                                    <?php echo safe_text(number_format((float)$payment["amount"], 2)); ?>
                                    <?php echo safe_text(display_value($payment["currency"])); ?>
                                </td>
                                <td data-label="Mode"><?php echo safe_text(display_value($payment["payment_method"])); ?></td>
                                <td data-label="Statut">
                                    <span class="new badge <?php echo safe_text(payment_badge_class($status)); ?>" data-badge-caption="">
                                        <?php echo safe_text(status_label($status)); ?>
                                    </span>
                                </td>
                                <td data-label="Date"><?php echo safe_text(format_date_fr($payment["paid_at"])); ?></td>
                                <td data-label="Actions">
                                    <div class="actions-wrap admin-actions">
                                        <a href="#viewPayment<?php echo $payment_id; ?>" class="btn-small green modal-trigger">Voir</a>
                                        <?php if($status == "en_attente" || $status == "en_traitement"){ ?>
                                            <form action="<?php echo app_url_html("admin/paiements"); ?>" method="POST">
                                                <input type="hidden" name="action" value="validate_payment">
                                                <input type="hidden" name="payment_id" value="<?php echo $payment_id; ?>">
                                                <button type="submit" class="btn-small blue">Valider</button>
                                            </form>
                                            <form action="<?php echo app_url_html("admin/paiements"); ?>" method="POST">
                                                <input type="hidden" name="action" value="reject_payment">
                                                <input type="hidden" name="payment_id" value="<?php echo $payment_id; ?>">
                                                <button type="submit" class="btn-small red">Rejeter</button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <div class="pagination-wrap">
                    <?php if($page > 1){ ?>
                        <a class="btn-flat" href="<?php echo safe_text(pagination_url($page - 1, $search, $status_filter, $periode)); ?>">Precedent</a>
                    <?php }else{ ?>
                        <span class="btn-flat disabled grey-text">Precedent</span>
                    <?php } ?>

                    <?php
                    $page_number = 1;
                    for($page_number = 1; $page_number <= $total_pages; $page_number++){
                    ?>
                        <?php if($page_number == $page){ ?>
                            <span class="btn blue-gradient white-text"><?php echo (int)$page_number; ?></span>
                        <?php }else{ ?>
                            <a class="btn-flat" href="<?php echo safe_text(pagination_url($page_number, $search, $status_filter, $periode)); ?>">
                                <?php echo (int)$page_number; ?>
                            </a>
                        <?php } ?>
                    <?php } ?>

                    <?php if($page < $total_pages){ ?>
                        <a class="btn-flat" href="<?php echo safe_text(pagination_url($page + 1, $search, $status_filter, $periode)); ?>">Suivant</a>
                    <?php }else{ ?>
                        <span class="btn-flat disabled grey-text">Suivant</span>
                    <?php } ?>
                </div>
            </div>
        <?php }else{ ?>
            <div class="card">
                <div class="card-content center">
                    <i class="material-icons large blue-text text-darken-4">payments</i>
                    <h5>Aucun paiement ne correspond aux criteres.</h5>
                </div>
            </div>
        <?php } ?>

    </div>
</div>

<?php foreach($payments as $payment){ ?>
    <?php
    $payment_id = (int)$payment["id"];
    $client_name = trim($payment["client_first_name"] . " " . $payment["client_last_name"]);
    $candidate_name = trim($payment["candidate_first_name"] . " " . $payment["candidate_last_name"]);
    ?>
    <div id="viewPayment<?php echo $payment_id; ?>" class="modal modal-fixed-footer modal-wide">
        <div class="modal-content">
            <h4><?php echo safe_text(payment_reference($payment_id)); ?></h4>

            <div class="payment-section">
                <h5 class="section-title"><i class="material-icons">person</i>Client</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Nom</span><?php echo safe_text(display_value($client_name)); ?></div>
                    <div class="detail-item"><span class="detail-label">Telephone</span><?php echo safe_text(display_value($payment["client_phone"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Email</span><?php echo safe_text(display_value($payment["client_email"])); ?></div>
                </div>
            </div>

            <div class="payment-section">
                <h5 class="section-title"><i class="material-icons">engineering</i>Intervenant</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Nom</span><?php echo safe_text(display_value($candidate_name)); ?></div>
                    <div class="detail-item"><span class="detail-label">Telephone</span><?php echo safe_text(display_value($payment["candidate_phone"])); ?></div>
                </div>
            </div>

            <div class="payment-section">
                <h5 class="section-title"><i class="material-icons">assignment</i>Mission</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Reference</span><?php echo safe_text(mission_reference($payment["mission_id"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Service</span><?php echo safe_text(display_value($payment["title"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Lieu</span><?php echo safe_text(display_value($payment["location"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Date prevue</span><?php echo safe_text(format_date_fr($payment["service_date"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Budget</span><?php echo safe_text(number_format((float)$payment["budget"], 2)); ?></div>
                </div>
            </div>

            <div class="payment-section">
                <h5 class="section-title"><i class="material-icons">payments</i>Paiement</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Montant</span><?php echo safe_text(number_format((float)$payment["amount"], 2)); ?></div>
                    <div class="detail-item"><span class="detail-label">Devise</span><?php echo safe_text(display_value($payment["currency"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Methode</span><?php echo safe_text(display_value($payment["payment_method"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Telephone utilise</span><?php echo safe_text(display_value($payment["phone_number"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Reference transaction</span><?php echo safe_text(display_value($payment["transaction_reference"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Reference externe</span><?php echo safe_text(display_value($payment["external_transaction_id"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Statut</span><?php echo safe_text(status_label($payment["status"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Date paiement</span><?php echo safe_text(format_date_fr($payment["paid_at"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Date creation</span><?php echo safe_text(format_date_fr($payment["created_at"])); ?></div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Fermer</a>
        </div>
    </div>
<?php } ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    M.Modal.init(document.querySelectorAll('.modal'));
    M.FormSelect.init(document.querySelectorAll('select'));
});
</script>

</body>
</html>
