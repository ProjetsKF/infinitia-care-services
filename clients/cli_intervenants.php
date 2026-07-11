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
$intervenants = array();

function safe_text($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function array_text($row, $key)
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

function mission_status_class($status)
{
    if($status == "en_attente"){

        return "orange";

    }

    if($status == "affectee"){

        return "blue";

    }

    if($status == "en_cours"){

        return "green";

    }

    if($status == "terminee"){

        return "grey";

    }

    return "grey";
}

function availability_status_class($status)
{
    if($status == "disponible"){

        return "green";

    }

    if($status == "hors_ligne"){

        return "red";

    }

    if($status != ""){

        return "orange";

    }

    return "grey";
}

function status_label($status)
{
    if($status == "en_attente"){

        return "En attente";

    }

    if($status == "affectee"){

        return "Affectee";

    }

    if($status == "en_cours"){

        return "En cours";

    }

    if($status == "terminee"){

        return "Terminee";

    }

    if($status == "annulee"){

        return "Annulee";

    }

    if($status == "disponible"){

        return "Disponible";

    }

    if($status == "hors_ligne"){

        return "Hors ligne";

    }

    if($status != ""){

        return ucfirst(str_replace("_", " ", $status));

    }

    return "Non renseigne";
}

function urgency_label($urgency)
{
    if($urgency == "low"){

        return "Faible";

    }

    if($urgency == "medium"){

        return "Moyenne";

    }

    if($urgency == "high"){

        return "Elevee";

    }

    return "Non renseigne";
}

function profile_photo_path($profile_photo)
{
    if($profile_photo === NULL || $profile_photo === ""){

        return "../assets/images/default-user.png";

    }

    if(strpos($profile_photo, "uploads/") === 0){

        return "../" . $profile_photo;

    }

    return "../uploads/profiles/" . $profile_photo;
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

$sql = "

SELECT
    m.id AS mission_id,
    m.mission_status,
    m.start_time,
    m.end_time,

    sr.id AS request_id,
    sr.title,
    sr.service_date,
    sr.location,
    sr.budget,
    sr.urgency_level,

    c.id AS candidate_id,
    c.gender,
    c.city,
    c.experience_years,
    c.availability_status,

    u.first_name,
    u.last_name,
    u.profile_photo

FROM service_requests sr

INNER JOIN missions m
ON m.service_request_id = sr.id

INNER JOIN candidates c
ON c.id = m.candidate_id

INNER JOIN users u
ON u.id = c.user_id

WHERE sr.client_id = ?

ORDER BY sr.service_date DESC, m.created_at DESC

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result(
    $stmt,
    $mission_id,
    $mission_status,
    $start_time,
    $end_time,
    $request_id,
    $title,
    $service_date,
    $location,
    $budget,
    $urgency_level,
    $candidate_id,
    $gender,
    $city,
    $experience_years,
    $availability_status,
    $first_name,
    $last_name,
    $profile_photo
);

while(mysqli_stmt_fetch($stmt)){

    $intervenants[] = array(
        "mission_id" => $mission_id,
        "mission_status" => $mission_status,
        "start_time" => $start_time,
        "end_time" => $end_time,
        "request_id" => $request_id,
        "title" => $title,
        "service_date" => $service_date,
        "location" => $location,
        "budget" => $budget,
        "urgency_level" => $urgency_level,
        "candidate_id" => $candidate_id,
        "gender" => $gender,
        "city" => $city,
        "experience_years" => $experience_years,
        "availability_status" => $availability_status,
        "first_name" => $first_name,
        "last_name" => $last_name,
        "profile_photo" => $profile_photo
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

        Mes intervenants | INFINITIA

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

        $current_page = "intervenants";

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
                            groups
                        </i>

                        Mes intervenants

                    </div>

                    <div class="welcome-text">

                        Consultez les intervenants affectes a vos demandes.

                    </div>

                </div>

            </div>

            <?php if(count($intervenants) > 0){ ?>

                <div class="row">

                    <?php foreach($intervenants as $intervenant){ ?>

                        <?php

                        $mission_id_value = (int)array_text($intervenant, "mission_id");
                        $candidate_id_value = (int)array_text($intervenant, "candidate_id");
                        $first_name_value = array_text($intervenant, "first_name");
                        $last_name_value = array_text($intervenant, "last_name");
                        $full_name_value = trim($first_name_value . " " . $last_name_value);
                        $gender_value = array_text($intervenant, "gender");
                        $city_value = array_text($intervenant, "city");
                        $experience_value = array_text($intervenant, "experience_years");
                        $availability_value = array_text($intervenant, "availability_status");
                        $mission_status_value = array_text($intervenant, "mission_status");
                        $title_value = array_text($intervenant, "title");
                        $service_date_value = array_text($intervenant, "service_date");
                        $location_value = array_text($intervenant, "location");
                        $budget_value = array_text($intervenant, "budget");
                        $urgency_value = array_text($intervenant, "urgency_level");
                        $start_time_value = array_text($intervenant, "start_time");
                        $end_time_value = array_text($intervenant, "end_time");
                        $profile_photo_value = array_text($intervenant, "profile_photo");
                        $photo_path = profile_photo_path($profile_photo_value);

                        if($full_name_value == ""){

                            $full_name_value = "Intervenant";

                        }

                        $budget_label = "Non renseigne";

                        if($budget_value !== ""){

                            $budget_label = number_format((float)$budget_value, 2) . " USD";

                        }

                        ?>

                        <div class="col s12 m6 l4">

                            <div class="card hoverable">

                                <div class="card-content">

                                    <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px;">

                                        <img src="<?php echo safe_text($photo_path); ?>"
                                             alt="Photo intervenant"
                                             width="68"
                                             height="68"
                                             style="border-radius:50%; object-fit:cover;">

                                        <div>

                                            <span class="card-title" style="font-weight:700; margin-bottom:4px;">

                                                <?php echo safe_text($full_name_value); ?>

                                            </span>

                                            <span class="new badge <?php echo availability_status_class($availability_value); ?>"
                                                  data-badge-caption="">

                                                <?php echo safe_text(status_label($availability_value)); ?>

                                            </span>

                                        </div>

                                    </div>

                                    <div class="divider"></div>

                                    <div style="margin-top:18px;">

                                        <p>
                                            <strong>Sexe :</strong>
                                            <?php echo safe_text($gender_value != "" ? $gender_value : "Non renseigne"); ?>
                                        </p>

                                        <p>
                                            <strong>Ville :</strong>
                                            <?php echo safe_text($city_value != "" ? $city_value : "Non renseigne"); ?>
                                        </p>

                                        <p>
                                            <strong>Experience :</strong>
                                            <?php echo safe_text($experience_value !== "" ? $experience_value . " an(s)" : "Non renseigne"); ?>
                                        </p>

                                        <p>
                                            <strong>Service demande :</strong>
                                            <?php echo safe_text($title_value != "" ? $title_value : "Non renseigne"); ?>
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
                                            <strong>Budget prevu :</strong>
                                            <?php echo safe_text($budget_label); ?>
                                        </p>

                                        <p>
                                            <strong>Urgence :</strong>
                                            <?php echo safe_text(urgency_label($urgency_value)); ?>
                                        </p>

                                        <p>
                                            <strong>Mission :</strong>

                                            <span class="new badge <?php echo mission_status_class($mission_status_value); ?>"
                                                  data-badge-caption="">

                                                <?php echo safe_text(status_label($mission_status_value)); ?>

                                            </span>
                                        </p>

                                    </div>

                                </div>

                                <div class="card-action">

                                    <a href="#profil<?php echo $candidate_id_value; ?>_<?php echo $mission_id_value; ?>"
                                       class="modal-trigger blue-text">

                                        Voir le profil

                                    </a>

                                    <a href="#mission<?php echo $mission_id_value; ?>"
                                       class="modal-trigger green-text">

                                        Voir la mission

                                    </a>

                                </div>

                            </div>

                        </div>

                        <div id="profil<?php echo $candidate_id_value; ?>_<?php echo $mission_id_value; ?>"
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
            Profil de l'intervenant
        </h4>

    </div>

                            <div class="modal-content" style="font-size:17px;line-height:1.9;color:#555;text-align:justify;">

                                <h4>
                                    <?php echo safe_text($full_name_value); ?>
                                </h4>

                                <p>
                                    <strong>Sexe :</strong>
                                    <?php echo safe_text($gender_value != "" ? $gender_value : "Non renseigne"); ?>
                                </p>

                                <p>
                                    <strong>Ville :</strong>
                                    <?php echo safe_text($city_value != "" ? $city_value : "Non renseigne"); ?>
                                </p>

                                <p>
                                    <strong>Experience :</strong>
                                    <?php echo safe_text($experience_value !== "" ? $experience_value . " an(s)" : "Non renseigne"); ?>
                                </p>

                                <p>
                                    <strong>Disponibilite :</strong>
                                    <?php echo safe_text(status_label($availability_value)); ?>
                                </p>

                            </div>

                            <div class="modal-footer">

                                <a href="#!"
                                   class="modal-close btn grey">

                                    Fermer

                                </a>

                            </div>

                        </div>

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
                                Mission affectee a <?php echo safe_text($full_name_value); ?>
                                </h4>

                            </div>

                            <div class="modal-content" style="font-size:17px;line-height:1.9;color:#555;text-align:justify;">

                                <h4>
                                    <?php echo safe_text($title_value != "" ? $title_value : "Mission"); ?>
                                </h4>

                                <p>
                                    <strong>Date prevue :</strong>
                                    <?php echo safe_text(format_date_fr($service_date_value) != "" ? format_date_fr($service_date_value) : "Non renseigne"); ?>
                                </p>

                                <p>
                                    <strong>Lieu :</strong>
                                    <?php echo safe_text($location_value != "" ? $location_value : "Non renseigne"); ?>
                                </p>

                                <p>
                                    <strong>Budget :</strong>
                                    <?php echo safe_text($budget_label); ?>
                                </p>

                                <p>
                                    <strong>Urgence :</strong>
                                    <?php echo safe_text(urgency_label($urgency_value)); ?>
                                </p>

                                <p>
                                    <strong>Statut :</strong>
                                    <?php echo safe_text(status_label($mission_status_value)); ?>
                                </p>

                                <p>
                                    <strong>Debut :</strong>
                                    <?php echo safe_text(format_date_fr($start_time_value) != "" ? format_date_fr($start_time_value) : "Non renseigne"); ?>
                                </p>

                                <p>
                                    <strong>Fin :</strong>
                                    <?php echo safe_text(format_date_fr($end_time_value) != "" ? format_date_fr($end_time_value) : "Non renseigne"); ?>
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

                </div>

            <?php }else{ ?>

                <div class="card">

                    <div class="card-content center">

                        <i class="material-icons large blue-text text-darken-4">
                            assignment_ind
                        </i>

                        <h5>
                            Toutes vos demandes sont encore en attente d'affectation.
                        </h5>

                        <p class="grey-text text-darken-1">
                            Des qu'un intervenant sera affecte a l'une de vos demandes, il apparaitra ici.
                        </p>

                    </div>

                    <div class="card-action center">

                        <a href="mes-demandes.php"
                           class="btn modal-trigger waves-effect waves-light new-request-btn">
            <i class="material-icons left">add</i>
                            Nouvelle demande

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
