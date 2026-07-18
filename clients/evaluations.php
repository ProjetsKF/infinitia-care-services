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

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
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

function note_value($value)
{
    $note = (int)$value;

    if($note < 1 || $note > 5){

        return "";

    }

    return $note;
}

function note_label($value)
{
    $note = note_value($value);

    if($note === ""){

        return "-";

    }

    return $note . "/5";
}

function valid_note($value)
{
    $note = (int)$value;

    return ($note >= 1 && $note <= 5);
}

function evaluations_pagination_url($page)
{
    $params = $_GET;
    $params["page"] = (int)$page;

    return "evaluations.php?" . http_build_query($params);
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

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $mission_id = isset($_POST["mission_id"])
        ? (int)$_POST["mission_id"]
        : 0;

    $note_generale = isset($_POST["note_generale"])
        ? (int)$_POST["note_generale"]
        : 0;

    $note_ponctualite = isset($_POST["note_ponctualite"])
        ? (int)$_POST["note_ponctualite"]
        : 0;

    $note_professionnalisme = isset($_POST["note_professionnalisme"])
        ? (int)$_POST["note_professionnalisme"]
        : 0;

    $note_qualite_service = isset($_POST["note_qualite_service"])
        ? (int)$_POST["note_qualite_service"]
        : 0;

    $commentaire = isset($_POST["commentaire"])
        ? trim($_POST["commentaire"])
        : "";

    if(
        $mission_id <= 0 ||
        !valid_note($note_generale) ||
        !valid_note($note_ponctualite) ||
        !valid_note($note_professionnalisme) ||
        !valid_note($note_qualite_service)
    ){

        $_SESSION["error"] = "Veuillez renseigner des notes entre 1 et 5.";
        header("Location: evaluations.php");
        exit();

    }

    $candidate_id = 0;

    $sql = "

    SELECT
        m.candidate_id
    FROM missions m
    INNER JOIN service_requests sr
    ON sr.id = m.service_request_id
    WHERE m.id = ?
    AND sr.client_id = ?
    AND m.mission_status = 'terminee'
    LIMIT 1

    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        die("Erreur SQL : " . mysqli_error($conn));

    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $mission_id,
        $client_id
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $candidate_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if($candidate_id <= 0){

        $_SESSION["error"] = "Mission introuvable ou non autorisee.";
        header("Location: evaluations.php");
        exit();

    }

    $review_id = 0;

    $sql = "

    SELECT id
    FROM service_reviews
    WHERE mission_id = ?
    AND client_id = ?
    AND candidate_id = ?
    LIMIT 1

    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        die("Erreur SQL : " . mysqli_error($conn));

    }

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $mission_id,
        $client_id,
        $candidate_id
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $review_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if($review_id > 0){

        $sql = "

        UPDATE service_reviews
        SET
            note_generale = ?,
            note_ponctualite = ?,
            note_professionnalisme = ?,
            note_qualite_service = ?,
            commentaire = ?
        WHERE id = ?
        AND mission_id = ?
        AND client_id = ?
        AND candidate_id = ?

        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param(
            $stmt,
            "iiiisiiii",
            $note_generale,
            $note_ponctualite,
            $note_professionnalisme,
            $note_qualite_service,
            $commentaire,
            $review_id,
            $mission_id,
            $client_id,
            $candidate_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION["success"] = "Evaluation modifiee avec succes.";

    }else{

        $sql = "

        INSERT INTO service_reviews(
            mission_id,
            client_id,
            candidate_id,
            note_generale,
            note_ponctualite,
            note_professionnalisme,
            note_qualite_service,
            commentaire
        )
        VALUES(?, ?, ?, ?, ?, ?, ?, ?)

        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param(
            $stmt,
            "iiiiiiis",
            $mission_id,
            $client_id,
            $candidate_id,
            $note_generale,
            $note_ponctualite,
            $note_professionnalisme,
            $note_qualite_service,
            $commentaire
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION["success"] = "Evaluation enregistree avec succes.";

    }

    header("Location: evaluations.php");
    exit();

}

$sql = "

SELECT COUNT(*)
FROM service_requests sr
INNER JOIN missions m
ON m.service_request_id = sr.id
WHERE sr.client_id = ?
AND m.mission_status = 'terminee'

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
    m.candidate_id,
    m.mission_status,

    sr.title,
    sr.service_date,

    u.first_name,
    u.last_name,

    r.id AS review_id,
    r.note_generale,
    r.note_ponctualite,
    r.note_professionnalisme,
    r.note_qualite_service,
    r.commentaire,
    r.created_at

FROM service_requests sr

INNER JOIN missions m
ON m.service_request_id = sr.id

INNER JOIN candidates c
ON c.id = m.candidate_id

INNER JOIN users u
ON u.id = c.user_id

LEFT JOIN service_reviews r
ON r.mission_id = m.id
AND r.client_id = sr.client_id
AND r.candidate_id = m.candidate_id

WHERE sr.client_id = ?
AND m.mission_status = 'terminee'

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
    $candidate_id,
    $mission_status,
    $title,
    $service_date,
    $first_name,
    $last_name,
    $review_id,
    $note_generale,
    $note_ponctualite,
    $note_professionnalisme,
    $note_qualite_service,
    $commentaire,
    $created_at
);

while(mysqli_stmt_fetch($stmt)){

    $missions[] = array(
        "mission_id" => $mission_id,
        "candidate_id" => $candidate_id,
        "mission_status" => $mission_status,
        "title" => $title,
        "service_date" => $service_date,
        "first_name" => $first_name,
        "last_name" => $last_name,
        "review_id" => $review_id,
        "note_generale" => $note_generale,
        "note_ponctualite" => $note_ponctualite,
        "note_professionnalisme" => $note_professionnalisme,
        "note_qualite_service" => $note_qualite_service,
        "commentaire" => $commentaire,
        "created_at" => $created_at
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

        Evaluations

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

        $current_page = "evaluations";

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

                        <i class="material-icons left"
                           style="vertical-align:middle; margin-right:8px;">

                            star

                        </i>

                        Evaluation des services

                    </div>

                    <div class="welcome-text">

                        Notez les prestations terminees realisees par vos intervenants.

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

            <?php if(count($missions) > 0){ ?>

                <!-- LISTE DES EVALUATIONS -->

                <div class="table-card">

                    <div class="table-header">

                        <div class="table-title">
                            Historique des evaluations
                        </div>

                    </div>

                    <table class="highlight responsive-table">

                        <thead>

                            <tr>

                                <th>Mission</th>
                                <th>Service</th>
                                <th>Intervenant</th>
                                <th>Note generale</th>
                                <th>Ponctualite</th>
                                <th>Professionnalisme</th>
                                <th>Qualite</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>

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
                                $review_id_value = (int)array_value($mission, "review_id");
                                $note_generale_value = array_value($mission, "note_generale");
                                $note_ponctualite_value = array_value($mission, "note_ponctualite");
                                $note_professionnalisme_value = array_value($mission, "note_professionnalisme");
                                $note_qualite_service_value = array_value($mission, "note_qualite_service");
                                $created_at_value = array_value($mission, "created_at");

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
                                        <?php echo safe_text(note_label($note_generale_value)); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(note_label($note_ponctualite_value)); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(note_label($note_professionnalisme_value)); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(note_label($note_qualite_service_value)); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(format_date_fr($created_at_value) != "" ? format_date_fr($created_at_value) : "-"); ?>
                                    </td>

                                    <td>

                                        <?php if($review_id_value > 0){ ?>

                                            <span class="status completed">
                                                Evaluee
                                            </span>

                                        <?php }else{ ?>

                                            <span class="status pending">
                                                A evaluer
                                            </span>

                                        <?php } ?>

                                    </td>

                                    <td>

                                        <a href="#evaluation<?php echo $mission_id_value; ?>"
                                           class="<?php echo $review_id_value > 0 ? 'blue-text' : 'green-text'; ?> modal-trigger"
                                           title="<?php echo $review_id_value > 0 ? 'Modifier' : 'Evaluer'; ?>">

                                            <i class="material-icons">
                                                <?php echo $review_id_value > 0 ? 'edit' : 'star'; ?>
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

                                <a href="<?php echo ($page <= 1) ? '#!' : safe_text(evaluations_pagination_url($page - 1)); ?>">

                                    <i class="material-icons">
                                        chevron_left
                                    </i>

                                </a>

                            </li>

                            <?php for($i = 1; $i <= $total_pages; $i++){ ?>

                                <li class="<?php echo ($i == $page) ? 'active' : 'waves-effect'; ?>">

                                    <a href="<?php echo safe_text(evaluations_pagination_url($i)); ?>">

                                        <?php echo (int)$i; ?>

                                    </a>

                                </li>

                            <?php } ?>

                            <li class="<?php echo ($page >= $total_pages) ? 'disabled' : 'waves-effect'; ?>">

                                <a href="<?php echo ($page >= $total_pages) ? '#!' : safe_text(evaluations_pagination_url($page + 1)); ?>">

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
                    $first_name_value = array_value($mission, "first_name");
                    $last_name_value = array_value($mission, "last_name");
                    $full_name_value = trim($first_name_value . " " . $last_name_value);
                    $review_id_value = (int)array_value($mission, "review_id");
                    $note_generale_value = note_value(array_value($mission, "note_generale"));
                    $note_ponctualite_value = note_value(array_value($mission, "note_ponctualite"));
                    $note_professionnalisme_value = note_value(array_value($mission, "note_professionnalisme"));
                    $note_qualite_service_value = note_value(array_value($mission, "note_qualite_service"));
                    $commentaire_value = array_value($mission, "commentaire");

                    if($full_name_value == ""){

                        $full_name_value = "Intervenant";

                    }

                    ?>

                    <div id="evaluation<?php echo $mission_id_value; ?>"
                         class="modal modal-fixed-footer">

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
                                Evaluation de la mission
                                </h4>

                            </div>

                        <form action="evaluations.php" method="POST">

                            <input type="hidden"
                                   name="mission_id"
                                   value="<?php echo $mission_id_value; ?>">

                            <div class="modal-content" style="font-size:17px;line-height:1.9;color:#555;text-align:justify;">

                                <h4>
                                    <?php echo $review_id_value > 0 ? "Modifier l'evaluation" : "Evaluer la mission"; ?>
                                </h4>

                                <p>
                                    <strong>Mission :</strong>
                                    <?php echo safe_text($title_value != "" ? $title_value : "Mission"); ?>
                                </p>

                                <p>
                                    <strong>Intervenant :</strong>
                                    <?php echo safe_text($full_name_value); ?>
                                </p>

                               <div class="row">

    <div class="input-field col s12 m6">

        <input type="number"
               min="1"
               max="5"
               name="note_generale"
               id="note_generale<?php echo $mission_id_value; ?>"
               value="<?php echo safe_text($note_generale_value); ?>"
               placeholder="Attribuez une note de 1 à 5"
               required>

        <label class="active"
               for="note_generale<?php echo $mission_id_value; ?>">
            Note générale
        </label>

        <span class="helper-text">
            Saisissez une note comprise entre <strong>1</strong> (Très mauvais) et <strong>5</strong> (Excellent).
        </span>

    </div>

    <div class="input-field col s12 m6">

        <input type="number"
               min="1"
               max="5"
               name="note_ponctualite"
               id="note_ponctualite<?php echo $mission_id_value; ?>"
               value="<?php echo safe_text($note_ponctualite_value); ?>"
               placeholder="Attribuez une note de 1 à 5"
               required>

        <label class="active"
               for="note_ponctualite<?php echo $mission_id_value; ?>">
            Ponctualité
        </label>

        <span class="helper-text">
            Saisissez une note comprise entre <strong>1</strong> (Très mauvais) et <strong>5</strong> (Excellent).
        </span>

    </div>

    <div class="input-field col s12 m6">

        <input type="number"
               min="1"
               max="5"
               name="note_professionnalisme"
               id="note_professionnalisme<?php echo $mission_id_value; ?>"
               value="<?php echo safe_text($note_professionnalisme_value); ?>"
               placeholder="Attribuez une note de 1 à 5"
               required>

        <label class="active"
               for="note_professionnalisme<?php echo $mission_id_value; ?>">
            Professionnalisme
        </label>

        <span class="helper-text">
            Saisissez une note comprise entre <strong>1</strong> (Très mauvais) et <strong>5</strong> (Excellent).
        </span>

    </div>

    <div class="input-field col s12 m6">

        <input type="number"
               min="1"
               max="5"
               name="note_qualite_service"
               id="note_qualite_service<?php echo $mission_id_value; ?>"
               value="<?php echo safe_text($note_qualite_service_value); ?>"
               placeholder="Attribuez une note de 1 à 5"
               required>

        <label class="active"
               for="note_qualite_service<?php echo $mission_id_value; ?>">
            Qualité du service
        </label>

        <span class="helper-text">
            Saisissez une note comprise entre <strong>1</strong> (Très mauvais) et <strong>5</strong> (Excellent).
        </span>

    </div>

    <div class="input-field col s12">

        <textarea name="commentaire"
                  id="commentaire<?php echo $mission_id_value; ?>"
                  class="materialize-textarea"
                  placeholder="Partagez votre expérience avec l'intervenant (facultatif)."><?php echo safe_text($commentaire_value); ?></textarea>

        <label class="active"
               for="commentaire<?php echo $mission_id_value; ?>">
            Commentaire
        </label>

        <span class="helper-text">
            Décrivez les points positifs, les difficultés rencontrées ou vos suggestions d'amélioration.
        </span>

    </div>

</div>

                            </div>

                            <div class="modal-footer">

                                <a href="#!"
                                   class="modal-close btn-flat">

                                    Annuler

                                </a>

                                <button type="submit"
                                        class="btn waves-effect waves-light">

                                    <?php echo $review_id_value > 0 ? "Modifier" : "Evaluer"; ?>

                                </button>

                            </div>

                        </form>

                    </div>

                <?php } ?>

            <?php }else{ ?>

                <div class="card">

                    <div class="card-content center">

                        <i class="material-icons large blue-text text-darken-4">
                            star
                        </i>

                        <h5>
                            Aucune mission terminee n'est disponible pour evaluation.
                        </h5>

                        <p class="grey-text text-darken-1">
                            Les missions terminees apparaitront ici des qu'elles pourront etre evaluees.
                        </p>

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

        M.updateTextFields();

    });

    </script>

</body>

</html>
