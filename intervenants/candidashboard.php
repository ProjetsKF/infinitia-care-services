<?php

session_start();

require_once("../config/database.php");

/* =========================================
   VERIFICATION CONNEXION
========================================= */

if(!isset($_SESSION["user_id"]))
{
    header("Location: " . app_url("login"));
    exit();
}

$user_id = (int)$_SESSION["user_id"];

/* =========================================
   INFORMATIONS INTERVENANT
========================================= */

$sql = "

SELECT

    users.id,
    users.first_name,
    users.last_name,
    users.email,
    users.phone,
    users.profile_photo,
    users.status,

    candidates.id AS candidate_id,
    candidates.city,
    candidates.experience_years,
    candidates.availability_status,
    candidates.verification_status

FROM users

INNER JOIN candidates
ON users.id = candidates.user_id

WHERE users.id = ?

LIMIT 1

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt)
{
    die("Erreur SQL : " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$intervenant = mysqli_fetch_assoc($result);

$candidate_id = 0;

if($intervenant)
{
    $candidate_id =
    (int)$intervenant['candidate_id'];
}

/* =========================================
   MISSIONS RECUES
========================================= */

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
        $total_missions =
        (int)$row['total'];
    }
}

/* =========================================
   TOTAL DOCUMENTS TELEVERSES
========================================= */

$total_documents = 0;

$sql = "

SELECT COUNT(*) AS total

FROM candidate_documents

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
        $total_documents =
        (int)$row['total'];
    }

    mysqli_stmt_close($stmt);
}
/* =========================================
   MISSIONS EN COURS
========================================= */

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
        $missions_en_cours =
        (int)$row['total'];
    }
}


/* =========================================
   FORMATIONS DISPONIBLES
========================================= */

$total_formations = 0;

$sql = "

SELECT COUNT(*) AS total

FROM candidate_trainings

WHERE candidate_id = ?
AND status = 'active'

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
        $total_formations =
        (int)$row['total'];
    }
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

        Tableau de bord Intervenant | INFINITIA

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

    $current_page = "dashboard";

    include("menuin.php");

    ?>

 <div class="main-content">

    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <div class="page-title">

                Tableau de Bord

            </div>

            <div class="welcome-text">

                Bienvenue

                <?php echo htmlspecialchars($intervenant['first_name']); ?>

                dans votre espace intervenant.

            </div>

        </div>

    </div>

    <!-- STATISTIQUES -->

    <div class="row">

        <!-- MISSIONS RECUES -->

        <div class="col s12 m6 l3">

            <div class="dashboard-card">

                <div class="card-icon blue-gradient">

                    <i class="material-icons">
                        assignment
                    </i>

                </div>

                <h5>
                    Missions reçues
                </h5>

                <h3>
                    <?php echo $total_missions; ?>
                </h3>

            </div>

        </div>


        <!-- MISSIONS EN COURS -->

        <div class="col s12 m6 l3">

            <div class="dashboard-card">

                <div class="card-icon pink-gradient">

                    <i class="material-icons">
                        pending_actions
                    </i>

                </div>

                <h5>
                    Missions en cours
                </h5>

                <h3>
                    <?php echo $missions_en_cours; ?>
                </h3>

            </div>

        </div>

        <div class="col s12 m6 l3">

            <div class="dashboard-card">

                <div class="card-icon pink-gradient">

                    <i class="material-icons">
                        description
                    </i>

                </div>

                <h5>
                    Documents
                </h5>

                <h3>
                    <?php echo $total_documents; ?>
                </h3>

            </div>

        </div>
        <!-- FORMATIONS -->

        <div class="col s12 m6 l3">

            <div class="dashboard-card">

                <div class="card-icon gold-gradient">

                    <i class="material-icons">
                        school
                    </i>

                </div>

                <h5>
                    Formations disponibles
                </h5>

                <h3>
                    <?php echo $total_formations; ?>
                </h3>

            </div>

        </div>

    </div>

    <!-- INFORMATIONS RAPIDES -->

    <div class="table-card">

        <div class="table-title">

            Mon Profil

        </div>

        <table class="highlight responsive-table">

            <tbody>

                <tr>

                    <th>Nom complet</th>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $intervenant['first_name']
                        ) . ' ' .
                        htmlspecialchars(
                            $intervenant['last_name']
                        );
                        ?>

                    </td>

                </tr>

                <tr>

                    <th>Ville</th>

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $intervenant['city']
                        );
                        ?>

                    </td>

                </tr>

                <tr>

                    <th>Disponibilité</th>

                    <td>

                        <?php
                        echo ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $intervenant['availability_status']
                            )
                        );
                        ?>

                    </td>

                </tr>

                <tr>

                    <th>Expérience</th>

                    <td>

                        <?php
                        echo (int)$intervenant['experience_years'];
                        ?>

                        année(s)

                    </td>

                </tr>

                <tr>

                    <th>Compte</th>

                    <td>

                        <?php
                        echo ucfirst(
                            $intervenant['verification_status']
                        );
                        ?>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

</body>
</html>
