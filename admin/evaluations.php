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

function review_reference($id)
{
    return "REV-" . str_pad((int)$id, 5, "0", STR_PAD_LEFT);
}

function mission_reference($id)
{
    return "MIS-" . str_pad((int)$id, 5, "0", STR_PAD_LEFT);
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

function avg_query($conn, $sql)
{
    $average = 0;
    $result = mysqli_query($conn, $sql);

    if($result){

        $row = mysqli_fetch_assoc($result);

        if($row && isset($row["average_value"]) && $row["average_value"] !== NULL){

            $average = (float)$row["average_value"];

        }

        mysqli_free_result($result);

    }

    return $average;
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

function note_badge_class($note)
{
    $note = (int)$note;

    if($note >= 5){

        return "green";

    }

    if($note == 4){

        return "blue";

    }

    if($note == 3){

        return "orange";

    }

    return "red";
}

function note_label($note)
{
    $note = (int)$note;

    if($note >= 5){

        return "Excellent";

    }

    if($note == 4){

        return "Bon";

    }

    if($note == 3){

        return "Moyen";

    }

    return "Faible";
}

function pagination_url($page, $search, $note, $periode)
{
    $params = array("page" => (int)$page);

    if($search != ""){

        $params["search"] = $search;

    }

    if($note != ""){

        $params["note"] = $note;

    }

    if($periode != ""){

        $params["periode"] = $periode;

    }

    return "evaluations.php?" . http_build_query($params);
}

$stats = array(
    "total" => count_query($conn, "SELECT COUNT(*) AS total FROM service_reviews"),
    "average_general" => avg_query($conn, "SELECT AVG(note_generale) AS average_value FROM service_reviews"),
    "average_punctuality" => avg_query($conn, "SELECT AVG(note_ponctualite) AS average_value FROM service_reviews"),
    "average_professionalism" => avg_query($conn, "SELECT AVG(note_professionnalisme) AS average_value FROM service_reviews"),
    "average_quality" => avg_query($conn, "SELECT AVG(note_qualite_service) AS average_value FROM service_reviews"),
    "rated_candidates" => count_query($conn, "SELECT COUNT(DISTINCT candidate_id) AS total FROM service_reviews")
);

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$note_filter = isset($_GET["note"]) ? trim($_GET["note"]) : "";
$periode = isset($_GET["periode"]) ? trim($_GET["periode"]) : "";
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$per_page = 10;

if($page <= 0){

    $page = 1;

}

if($note_filter != "5" && $note_filter != "4" && $note_filter != "3" && $note_filter != "2" && $note_filter != "1"){

    $note_filter = "";

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
        OR sr.title LIKE ?
        OR rv.commentaire LIKE ?
    )";

    $search_like = "%" . $search . "%";
    $i = 0;

    for($i = 0; $i < 6; $i++){

        $params[] = $search_like;

    }

    $types .= "ssssss";

}

if($note_filter != ""){

    $where_parts[] = "rv.note_generale = ?";
    $params[] = (int)$note_filter;
    $types .= "i";

}

if($periode == "today"){

    $where_parts[] = "DATE(rv.created_at) = CURDATE()";

}elseif($periode == "week"){

    $where_parts[] = "YEARWEEK(rv.created_at, 1) = YEARWEEK(CURDATE(), 1)";

}elseif($periode == "month"){

    $where_parts[] = "YEAR(rv.created_at) = YEAR(CURDATE()) AND MONTH(rv.created_at) = MONTH(CURDATE())";

}elseif($periode == "year"){

    $where_parts[] = "YEAR(rv.created_at) = YEAR(CURDATE())";

}

$where_sql = "";

if(count($where_parts) > 0){

    $where_sql = "WHERE " . implode(" AND ", $where_parts);

}

$from_sql = "
FROM service_reviews rv
INNER JOIN missions m
ON m.id = rv.mission_id
INNER JOIN service_requests sr
ON sr.id = m.service_request_id
INNER JOIN clients cl
ON cl.id = rv.client_id
INNER JOIN users cu
ON cu.id = cl.user_id
INNER JOIN candidates ca
ON ca.id = rv.candidate_id
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
$reviews = array();

$sql = "
SELECT
    rv.id,
    rv.mission_id,
    rv.note_generale,
    rv.note_ponctualite,
    rv.note_professionnalisme,
    rv.note_qualite_service,
    rv.commentaire,
    rv.created_at,
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
ORDER BY rv.created_at DESC
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
    $result_note_generale,
    $result_note_ponctualite,
    $result_note_professionnalisme,
    $result_note_qualite_service,
    $result_commentaire,
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

    $reviews[] = array(
        "id" => $result_id,
        "mission_id" => $result_mission_id,
        "note_generale" => $result_note_generale,
        "note_ponctualite" => $result_note_ponctualite,
        "note_professionnalisme" => $result_note_professionnalisme,
        "note_qualite_service" => $result_note_qualite_service,
        "commentaire" => $result_commentaire,
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

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Evaluations | INFINITIA</title>

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

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

        .search-card,
        .review-section{
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

        .section-title{
            align-items:center;
            color:#081f78;
            display:flex;
            font-size:18px;
            font-weight:700;
            gap:8px;
            margin:0 0 14px;
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

<body>

<div class="dashboard">

    <?php

    $current_page = "evaluations";

    include("menuadmin.php");

    ?>

    <div class="main-content">

        <div class="topbar">
            <div>
                <div class="page-title">Evaluations</div>
                <div class="welcome-text">
                    Analyse qualite des evaluations laissees apres mission.
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">reviews</i></div>
                    <div><h5>Total evaluations</h5><h3><?php echo (int)$stats["total"]; ?></h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">star</i></div>
                    <div><h5>Note moyenne generale</h5><h3><?php echo safe_text(number_format((float)$stats["average_general"], 1)); ?>/5</h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">schedule</i></div>
                    <div><h5>Moyenne ponctualite</h5><h3><?php echo safe_text(number_format((float)$stats["average_punctuality"], 1)); ?>/5</h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon pink-gradient"><i class="material-icons">workspace_premium</i></div>
                    <div><h5>Moyenne professionnalisme</h5><h3><?php echo safe_text(number_format((float)$stats["average_professionalism"], 1)); ?>/5</h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">verified</i></div>
                    <div><h5>Moyenne qualite du service</h5><h3><?php echo safe_text(number_format((float)$stats["average_quality"], 1)); ?>/5</h3></div>
                </div>
            </div>
            <div class="col s12 m6 l4">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">groups</i></div>
                    <div><h5>Intervenants evalues</h5><h3><?php echo (int)$stats["rated_candidates"]; ?></h3></div>
                </div>
            </div>
        </div>

        <div class="search-card">
            <form action="evaluations.php" method="GET">
                <div class="row" style="margin-bottom:0;">
                    <div class="input-field col s12 l4">
                        <i class="material-icons prefix">search</i>
                        <input type="text" name="search" id="search" value="<?php echo safe_text($search); ?>">
                        <label for="search" class="<?php if($search != ""){ echo "active"; } ?>">Client, intervenant, service, commentaire</label>
                    </div>
                    <div class="input-field col s12 m6 l3">
                        <select name="note">
                            <option value="" <?php if($note_filter == ""){ echo "selected"; } ?>>Toutes les notes</option>
                            <option value="5" <?php if($note_filter == "5"){ echo "selected"; } ?>>5</option>
                            <option value="4" <?php if($note_filter == "4"){ echo "selected"; } ?>>4</option>
                            <option value="3" <?php if($note_filter == "3"){ echo "selected"; } ?>>3</option>
                            <option value="2" <?php if($note_filter == "2"){ echo "selected"; } ?>>2</option>
                            <option value="1" <?php if($note_filter == "1"){ echo "selected"; } ?>>1</option>
                        </select>
                        <label>Note generale</label>
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
                        <button type="submit" class="btn waves-effect waves-light">Filtrer</button>
                        <a href="evaluations.php" class="btn-flat">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <?php if(count($reviews) > 0){ ?>
            <div class="table-card">
                <div class="table-title">
                    Liste des evaluations
                    <span class="grey-text" style="font-size:14px; font-weight:400;">
                        (<?php echo (int)$total_filtered; ?> resultat(s))
                    </span>
                </div>

                <table class="highlight responsive-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Client</th>
                            <th>Intervenant</th>
                            <th>Service</th>
                            <th>Note generale</th>
                            <th>Ponctualite</th>
                            <th>Professionnalisme</th>
                            <th>Qualite</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reviews as $review){ ?>
                            <?php
                            $review_id = (int)$review["id"];
                            $client_name = trim($review["client_first_name"] . " " . $review["client_last_name"]);
                            $candidate_name = trim($review["candidate_first_name"] . " " . $review["candidate_last_name"]);
                            ?>
                            <tr>
                                <td><?php echo safe_text(review_reference($review_id)); ?></td>
                                <td><?php echo safe_text(display_value($client_name)); ?></td>
                                <td><?php echo safe_text(display_value($candidate_name)); ?></td>
                                <td><?php echo safe_text(display_value($review["title"])); ?></td>
                                <td><span class="new badge <?php echo safe_text(note_badge_class($review["note_generale"])); ?>" data-badge-caption=""><?php echo (int)$review["note_generale"]; ?>/5 <?php echo safe_text(note_label($review["note_generale"])); ?></span></td>
                                <td><?php echo (int)$review["note_ponctualite"]; ?>/5</td>
                                <td><?php echo (int)$review["note_professionnalisme"]; ?>/5</td>
                                <td><?php echo (int)$review["note_qualite_service"]; ?>/5</td>
                                <td><?php echo safe_text(format_date_fr($review["created_at"])); ?></td>
                                <td>
                                    <a href="#viewReview<?php echo $review_id; ?>" class="btn-small green modal-trigger">Voir</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <div class="pagination-wrap">
                    <?php if($page > 1){ ?>
                        <a class="btn-flat" href="<?php echo safe_text(pagination_url($page - 1, $search, $note_filter, $periode)); ?>">Precedent</a>
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
                            <a class="btn-flat" href="<?php echo safe_text(pagination_url($page_number, $search, $note_filter, $periode)); ?>"><?php echo (int)$page_number; ?></a>
                        <?php } ?>
                    <?php } ?>

                    <?php if($page < $total_pages){ ?>
                        <a class="btn-flat" href="<?php echo safe_text(pagination_url($page + 1, $search, $note_filter, $periode)); ?>">Suivant</a>
                    <?php }else{ ?>
                        <span class="btn-flat disabled grey-text">Suivant</span>
                    <?php } ?>
                </div>
            </div>
        <?php }else{ ?>
            <div class="card">
                <div class="card-content center">
                    <i class="material-icons large blue-text text-darken-4">reviews</i>
                    <h5>Aucune evaluation ne correspond aux criteres.</h5>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php foreach($reviews as $review){ ?>
    <?php
    $review_id = (int)$review["id"];
    $client_name = trim($review["client_first_name"] . " " . $review["client_last_name"]);
    $candidate_name = trim($review["candidate_first_name"] . " " . $review["candidate_last_name"]);
    ?>
    <div id="viewReview<?php echo $review_id; ?>" class="modal modal-fixed-footer modal-wide">
        <div class="modal-content">
            <h4><?php echo safe_text(review_reference($review_id)); ?></h4>

            <div class="review-section">
                <h5 class="section-title"><i class="material-icons">person</i>Client</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Nom</span><?php echo safe_text(display_value($client_name)); ?></div>
                    <div class="detail-item"><span class="detail-label">Telephone</span><?php echo safe_text(display_value($review["client_phone"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Email</span><?php echo safe_text(display_value($review["client_email"])); ?></div>
                </div>
            </div>

            <div class="review-section">
                <h5 class="section-title"><i class="material-icons">engineering</i>Intervenant</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Nom</span><?php echo safe_text(display_value($candidate_name)); ?></div>
                    <div class="detail-item"><span class="detail-label">Telephone</span><?php echo safe_text(display_value($review["candidate_phone"])); ?></div>
                </div>
            </div>

            <div class="review-section">
                <h5 class="section-title"><i class="material-icons">assignment</i>Mission</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Reference</span><?php echo safe_text(mission_reference($review["mission_id"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Service</span><?php echo safe_text(display_value($review["title"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Lieu</span><?php echo safe_text(display_value($review["location"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Date prevue</span><?php echo safe_text(format_date_fr($review["service_date"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Budget</span><?php echo safe_text(number_format((float)$review["budget"], 2)); ?></div>
                </div>
            </div>

            <div class="review-section">
                <h5 class="section-title"><i class="material-icons">star</i>Notes detaillees</h5>
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Note generale</span><?php echo (int)$review["note_generale"]; ?>/5 - <?php echo safe_text(note_label($review["note_generale"])); ?></div>
                    <div class="detail-item"><span class="detail-label">Ponctualite</span><?php echo (int)$review["note_ponctualite"]; ?>/5</div>
                    <div class="detail-item"><span class="detail-label">Professionnalisme</span><?php echo (int)$review["note_professionnalisme"]; ?>/5</div>
                    <div class="detail-item"><span class="detail-label">Qualite du service</span><?php echo (int)$review["note_qualite_service"]; ?>/5</div>
                    <div class="detail-item"><span class="detail-label">Date evaluation</span><?php echo safe_text(format_date_fr($review["created_at"])); ?></div>
                </div>
                <p style="margin-top:16px;">
                    <strong>Commentaire :</strong><br>
                    <?php echo nl2br(safe_text(display_value($review["commentaire"]))); ?>
                </p>
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
