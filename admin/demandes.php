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

function request_reference($id)
{
    return "REQ-" . str_pad((int)$id, 5, "0", STR_PAD_LEFT);
}

function redirect_demandes()
{
    header("Location: demandes.php");
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

function enum_values($conn, $table, $field)
{
    $values = array();
    $table = mysqli_real_escape_string($conn, $table);
    $field = mysqli_real_escape_string($conn, $field);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $field . "'");

    if($result){

        $row = mysqli_fetch_assoc($result);

        if($row && isset($row["Type"]) && strpos($row["Type"], "enum(") === 0){

            $enum = substr($row["Type"], 5, -1);
            $parts = explode(",", $enum);
            $i = 0;

            for($i = 0; $i < count($parts); $i++){

                $values[] = trim($parts[$i], "'");

            }

        }

        mysqli_free_result($result);

    }

    return $values;
}

function has_status($statuses, $status)
{
    return in_array($status, $statuses);
}

function status_label($status, $mission_total)
{
    if($mission_total > 0){

        return "Affectee";

    }

    if($status == "en_attente"){

        return "En attente";

    }

    if($status == "validee" || $status == "attribuee"){

        return "Validee";

    }

    if($status == "affectee"){

        return "Affectee";

    }

    if($status == "rejetee" || $status == "annulee"){

        return "Rejetee";

    }

    if($status == "en_cours"){

        return "En cours";

    }

    if($status == "terminee"){

        return "Terminee";

    }

    return display_value($status);
}

function status_badge_class($status, $mission_total)
{
    if($mission_total > 0 || $status == "affectee" || $status == "en_cours" || $status == "terminee"){

        return "green";

    }

    if($status == "en_attente"){

        return "orange";

    }

    if($status == "validee" || $status == "attribuee"){

        return "blue";

    }

    if($status == "rejetee"){

        return "red";

    }

    if($status == "annulee"){

        return "grey";

    }

    return "grey";
}

$status_values = enum_values($conn, "service_requests", "status");

$validated_status = "validee";
$rejected_status = "rejetee";

if(!has_status($status_values, $validated_status)){

    if(has_status($status_values, "attribuee")){

        $validated_status = "attribuee";

    }else{

        $validated_status = "en_attente";

    }

}

if(!has_status($status_values, $rejected_status)){

    if(has_status($status_values, "annulee")){

        $rejected_status = "annulee";

    }else{

        $rejected_status = "en_attente";

    }

}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

    if($action == "validate_request" || $action == "reject_request"){

        $request_id = isset($_POST["request_id"])
            ? (int)$_POST["request_id"]
            : 0;

        if($request_id <= 0){

            $_SESSION["error"] = "Demande invalide.";
            redirect_demandes();

        }

        $current_status = "";
        $existing_id = 0;

        $sql = "
        SELECT id, status
        FROM service_requests
        WHERE id = ?
        LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $existing_id, $current_status);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if($existing_id <= 0){

            $_SESSION["error"] = "Demande introuvable.";
            redirect_demandes();

        }

        if($action == "validate_request"){

            if($current_status != "en_attente"){

                $_SESSION["error"] = "Cette demande ne peut plus etre validee.";
                redirect_demandes();

            }

            $sql = "
            UPDATE service_requests
            SET status = ?
            WHERE id = ?
            AND status = 'en_attente'
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param($stmt, "si", $validated_status, $request_id);

            if(mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0){

                $_SESSION["success"] = "Demande validee avec succes.";

            }else{

                $_SESSION["error"] = "Erreur lors de la validation de la demande.";

            }

            mysqli_stmt_close($stmt);
            redirect_demandes();

        }

        if($action == "reject_request"){

            if($current_status != "en_attente" && $current_status != $validated_status && $current_status != "validee" && $current_status != "attribuee"){

                $_SESSION["error"] = "Cette demande ne peut plus etre rejetee.";
                redirect_demandes();

            }

            $sql = "
            UPDATE service_requests
            SET status = ?
            WHERE id = ?
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param($stmt, "si", $rejected_status, $request_id);

            if(mysqli_stmt_execute($stmt)){

                $_SESSION["success"] = "Demande rejetee avec succes.";

            }else{

                $_SESSION["error"] = "Erreur lors du rejet de la demande.";

            }

            mysqli_stmt_close($stmt);
            redirect_demandes();

        }

    }

}

$stats = array(
    "total" => count_query($conn, "SELECT COUNT(*) AS total FROM service_requests"),
    "pending" => count_query($conn, "SELECT COUNT(*) AS total FROM service_requests WHERE status = 'en_attente'"),
    "validated" => count_query($conn, "SELECT COUNT(*) AS total FROM service_requests WHERE status IN ('validee', 'attribuee') AND NOT EXISTS(SELECT 1 FROM missions WHERE missions.service_request_id = service_requests.id)"),
    "assigned" => count_query($conn, "SELECT COUNT(DISTINCT service_request_id) AS total FROM missions"),
    "rejected" => count_query($conn, "SELECT COUNT(*) AS total FROM service_requests WHERE status IN ('rejetee', 'annulee')")
);

$requests = array();

$sql = "
SELECT
    sr.id,
    sr.title,
    sr.description,
    sr.location,
    sr.service_date,
    sr.duration,
    sr.budget,
    sr.urgency_level,
    sr.status,
    sr.created_at,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    sc.name AS category_name,
    COUNT(m.id) AS mission_total
FROM service_requests sr
INNER JOIN clients c
ON c.id = sr.client_id
INNER JOIN users u
ON u.id = c.user_id
LEFT JOIN service_categories sc
ON sc.id = sr.category_id
LEFT JOIN missions m
ON m.service_request_id = sr.id
GROUP BY
    sr.id,
    sr.title,
    sr.description,
    sr.location,
    sr.service_date,
    sr.duration,
    sr.budget,
    sr.urgency_level,
    sr.status,
    sr.created_at,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    sc.name
ORDER BY sr.created_at DESC
";

$result = mysqli_query($conn, $sql);

if($result){

    while($row = mysqli_fetch_assoc($result)){

        $requests[] = $row;

    }

    mysqli_free_result($result);

}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Demandes | INFINITIA</title>

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

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

        .request-actions{
            display:flex;
            align-items:center;
            gap:8px;
            flex-wrap:wrap;
        }

        .request-actions form{
            display:inline-block;
            margin:0;
        }

        .detail-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:14px;
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
    </style>

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "demandes";

    include("menuadmin.php");

    ?>

    <div class="main-content">

        <div class="topbar">

            <div>
                <div class="page-title">Demandes clients</div>
                <div class="welcome-text">
                    Analyse, validation et redirection des demandes vers l'affectation.
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
                    <h5>Total demandes</h5>
                    <h3><?php echo (int)$stats["total"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">hourglass_empty</i></div>
                    <h5>En attente</h5>
                    <h3><?php echo (int)$stats["pending"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">task_alt</i></div>
                    <h5>Validees</h5>
                    <h3><?php echo (int)$stats["validated"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon pink-gradient"><i class="material-icons">engineering</i></div>
                    <h5>Affectees</h5>
                    <h3><?php echo (int)$stats["assigned"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l2">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">block</i></div>
                    <h5>Rejetees</h5>
                    <h3><?php echo (int)$stats["rejected"]; ?></h3>
                </div>
            </div>
        </div>

        <?php if(count($requests) > 0){ ?>

            <div class="table-card">
                <div class="table-title">Liste des demandes</div>

                <table class="highlight responsive-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Client</th>
                            <th>Categorie</th>
                            <th>Service demande</th>
                            <th>Date prevue</th>
                            <th>Urgence</th>
                            <th>Budget</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $request){ ?>
                            <?php
                            $request_id = isset($request["id"]) ? (int)$request["id"] : 0;
                            $mission_total = isset($request["mission_total"]) ? (int)$request["mission_total"] : 0;
                            $status = isset($request["status"]) ? $request["status"] : "";
                            $client_name = trim(display_value($request["first_name"]) . " " . display_value($request["last_name"]));
                            $badge_class = status_badge_class($status, $mission_total);
                            ?>
                            <tr>
                                <td><?php echo safe_text(request_reference($request_id)); ?></td>
                                <td><?php echo safe_text(display_value($client_name)); ?></td>
                                <td><?php echo safe_text(display_value($request["category_name"])); ?></td>
                                <td><?php echo safe_text(display_value($request["title"])); ?></td>
                                <td><?php echo safe_text(format_date_fr($request["service_date"], false)); ?></td>
                                <td><?php echo safe_text(display_value($request["urgency_level"])); ?></td>
                                <td><?php echo safe_text(number_format((float)$request["budget"], 2)); ?></td>
                                <td>
                                    <span class="new badge <?php echo safe_text($badge_class); ?>" data-badge-caption="">
                                        <?php echo safe_text(status_label($status, $mission_total)); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="request-actions">
                                        <a href="#viewRequest<?php echo $request_id; ?>"
                                           class="btn-small green modal-trigger">
                                            Voir
                                        </a>

                                        <?php if($status == "en_attente"){ ?>
                                            <form action="demandes.php" method="POST">
                                                <input type="hidden" name="action" value="validate_request">
                                                <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                                                <button type="submit" class="btn-small blue">
                                                    Valider
                                                </button>
                                            </form>
                                        <?php } ?>

                                        <?php if($status == "en_attente" || $status == $validated_status || $status == "validee" || $status == "attribuee"){ ?>
                                            <a href="#rejectRequest<?php echo $request_id; ?>"
                                               class="btn-small red modal-trigger">
                                                Rejeter
                                            </a>
                                        <?php } ?>

                                        <?php if(($status == $validated_status || $status == "validee" || $status == "attribuee") && $mission_total <= 0){ ?>
                                            <a href="affectations.php?request_id=<?php echo $request_id; ?>"
                                               class="btn-small orange">
                                                Affecter
                                            </a>
                                        <?php } ?>

                                        <?php if($mission_total > 0){ ?>
                                            <span class="new badge green" data-badge-caption="">
                                                Deja affectee
                                            </span>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        <?php }else{ ?>

            <div class="card">
                <div class="card-content center">
                    <i class="material-icons large blue-text text-darken-4">assignment</i>
                    <h5>Aucune demande client n'existe pour le moment.</h5>
                </div>
            </div>

        <?php } ?>

    </div>
</div>

<?php foreach($requests as $request){ ?>
    <?php
    $request_id = isset($request["id"]) ? (int)$request["id"] : 0;
    $mission_total = isset($request["mission_total"]) ? (int)$request["mission_total"] : 0;
    $status = isset($request["status"]) ? $request["status"] : "";
    $client_name = trim(display_value($request["first_name"]) . " " . display_value($request["last_name"]));
    ?>

    <div id="viewRequest<?php echo $request_id; ?>" class="modal modal-fixed-footer">
        <div class="modal-content">
            <h4><?php echo safe_text(request_reference($request_id)); ?></h4>

            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Client</span>
                    <?php echo safe_text(display_value($client_name)); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Telephone</span>
                    <?php echo safe_text(display_value($request["phone"])); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    <?php echo safe_text(display_value($request["email"])); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Categorie</span>
                    <?php echo safe_text(display_value($request["category_name"])); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Titre</span>
                    <?php echo safe_text(display_value($request["title"])); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Lieu</span>
                    <?php echo safe_text(display_value($request["location"])); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Date souhaitee</span>
                    <?php echo safe_text(format_date_fr($request["service_date"], false)); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Duree</span>
                    <?php echo safe_text(display_value($request["duration"])); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Budget</span>
                    <?php echo safe_text(number_format((float)$request["budget"], 2)); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Urgence</span>
                    <?php echo safe_text(display_value($request["urgency_level"])); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Statut</span>
                    <?php echo safe_text(status_label($status, $mission_total)); ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Date de creation</span>
                    <?php echo safe_text(format_date_fr($request["created_at"], true)); ?>
                </div>
            </div>

            <p style="margin-top:18px;">
                <strong>Description :</strong><br>
                <?php echo nl2br(safe_text(display_value($request["description"]))); ?>
            </p>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Fermer</a>
        </div>
    </div>

    <div id="rejectRequest<?php echo $request_id; ?>" class="modal">
        <form action="demandes.php" method="POST">
            <input type="hidden" name="action" value="reject_request">
            <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">

            <div class="modal-content">
                <h4>Rejeter la demande</h4>
                <p>
                    Confirmez-vous le rejet de la demande
                    <strong><?php echo safe_text(request_reference($request_id)); ?></strong> ?
                </p>
                <p class="grey-text">
                    La demande ne sera pas supprimee.
                </p>
            </div>

            <div class="modal-footer">
                <a href="#!" class="modal-close btn-flat">Annuler</a>
                <button type="submit" class="btn red waves-effect waves-light">
                    Rejeter
                </button>
            </div>
        </form>
    </div>
<?php } ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    M.Modal.init(document.querySelectorAll('.modal'));
});
</script>

</body>
</html>
