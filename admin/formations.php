<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1){

    header("Location: ../login.php");
    exit();

}

$admin_id = (int)$_SESSION["user_id"];

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

function redirect_formations()
{
    header("Location: formations.php");
    exit();
}

function formations_pagination_url($page_number)
{
    $params = $_GET;
    $params["page"] = (int)$page_number;

    return "formations.php?" . http_build_query($params);
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

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

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

    if($action == "assign_training"){

        $training_id = isset($_POST["training_id"])
            ? (int)$_POST["training_id"]
            : 0;

        $assignment_status = isset($_POST["status"])
            ? $_POST["status"]
            : "active";

        $selected_candidates = array();

        if(isset($_POST["candidates"]) && is_array($_POST["candidates"])){

            $selected_candidates = $_POST["candidates"];

        }

        if($training_id <= 0){

            $_SESSION["error"] = "Formation introuvable.";
            redirect_formations();

        }

        if($assignment_status != "active" && $assignment_status != "inactive"){

            $assignment_status = "active";

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
            VALUES(?, ?, ?, ?)
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param(
                $stmt,
                "iisi",
                $candidate_id,
                $training_id,
                $assignment_status,
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
    "active_trainings" => count_query($conn, "SELECT COUNT(*) AS total FROM candidate_trainings WHERE status = 'active'"),
    "candidates_with_training" => count_query($conn, "SELECT COUNT(DISTINCT candidate_id) AS total FROM candidate_trainings")
);

$trainings = array();
$limit = 50;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if($page < 1){

    $page = 1;

}

$total_trainings = count_query($conn, "SELECT COUNT(*) AS total FROM trainings");
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
    COUNT(ct.id) AS assigned_total
FROM trainings t
LEFT JOIN candidate_trainings ct
ON ct.training_id = t.id
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

    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $trainings[] = $row;

        }

        mysqli_free_result($result);

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
ORDER BY u.first_name ASC, u.last_name ASC
";

$result = mysqli_query($conn, $sql);

if($result){

    while($row = mysqli_fetch_assoc($result)){

        $candidates[] = $row;

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

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

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

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient">
                        <i class="material-icons">school</i>
                    </div>
                    <h5>Total formations</h5>
                    <h3><?php echo (int)$stats["total_trainings"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon pink-gradient">
                        <i class="material-icons">assignment_ind</i>
                    </div>
                    <h5>Formations attribuees</h5>
                    <h3><?php echo (int)$stats["assigned_trainings"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient">
                        <i class="material-icons">play_circle</i>
                    </div>
                    <h5>Formations actives</h5>
                    <h3><?php echo (int)$stats["active_trainings"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient">
                        <i class="material-icons">groups</i>
                    </div>
                    <h5>Intervenants formes</h5>
                    <h3><?php echo (int)$stats["candidates_with_training"]; ?></h3>
                </div>
            </div>

        </div>

        <div class="table-card">

            <div class="table-header">

                <div class="table-title">
                    Liste des formations
                </div>

            </div>

            <table class="highlight responsive-table">

                <thead>

                    <tr>
                        <th>Titre</th>
                        <th>Duree</th>
                        <th>Lien YouTube</th>
                        <th>Intervenants assignes</th>
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

                                    <form action="formations.php"
                                          method="POST"
                                          style="display:inline;"
                                          onsubmit="return confirm('Voulez-vous supprimer cette formation ?');">
                                        <input type="hidden" name="action" value="delete_training">
                                        <input type="hidden" name="training_id" value="<?php echo $training_id; ?>">
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
                            <td colspan="6" class="center-align">
                                Aucune formation n'est encore enregistree.
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
    <form action="formations.php" method="POST">
        <input type="hidden" name="action" value="add_training">

        <div class="modal-content">
            <h4>Nouvelle formation</h4>

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
    $duration = isset($training["duration"]) ? $training["duration"] : "";
    $created_at = isset($training["created_at"]) ? $training["created_at"] : "";
    $assigned_total = isset($training["assigned_total"]) ? (int)$training["assigned_total"] : 0;

    ?>

    <div id="viewTraining<?php echo $training_id; ?>" class="modal modal-fixed-footer">
        <div class="modal-content">
            <h4><?php echo safe_text(display_value($title)); ?></h4>

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

            <p>
                <strong>Date de creation :</strong>
                <?php echo safe_text(format_date_fr($created_at)); ?>
            </p>

            <p>
                <strong>Intervenants assignes :</strong>
                <?php echo (int)$assigned_total; ?>
            </p>
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
        <form action="formations.php" method="POST">
            <input type="hidden" name="action" value="update_training">
            <input type="hidden" name="training_id" value="<?php echo $training_id; ?>">

            <div class="modal-content">
                <h4>Modifier la formation</h4>

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
        <form action="formations.php" method="POST">
            <input type="hidden" name="action" value="assign_training">
            <input type="hidden" name="training_id" value="<?php echo $training_id; ?>">

            <div class="modal-content">
                <h4>Attribuer la formation</h4>
                <p><?php echo safe_text(display_value($title)); ?></p>

                <div class="input-field">
                    <select name="status" required>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <label>Statut de l'attribution</label>
                </div>

                <div class="training-modal-list">

                    <?php if(count($candidates) > 0){ ?>

                        <?php foreach($candidates as $candidate){ ?>

                            <?php

                            $candidate_id = isset($candidate["candidate_id"]) ? (int)$candidate["candidate_id"] : 0;
                            $first_name = isset($candidate["first_name"]) ? $candidate["first_name"] : "";
                            $last_name = isset($candidate["last_name"]) ? $candidate["last_name"] : "";
                            $email = isset($candidate["email"]) ? $candidate["email"] : "";
                            $verification_status = isset($candidate["verification_status"]) ? $candidate["verification_status"] : "";
                            $full_name = trim($first_name . " " . $last_name);

                            if($full_name == ""){

                                $full_name = "Intervenant";

                            }

                            ?>

                            <p>
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

                    <?php }else{ ?>

                        <p class="grey-text">
                            Aucun intervenant disponible.
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
    M.Modal.init(document.querySelectorAll('.modal'));
    M.FormSelect.init(document.querySelectorAll('select'));
    M.updateTextFields();
});
</script>

</body>
</html>
