<!-- =========================================
     INTERVENANT DASHBOARD
     FICHIER : dashboard.php
========================================= -->
<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"])){

    header("Location: ../login.php");
    exit();

}

$user_id = $_SESSION["user_id"];

$sql = "

SELECT

    users.first_name,
    users.last_name,
    users.email,
    users.phone,
    users.profile_photo,
    users.status,

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

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$intervenant = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        Tableau de bord Intervenant | INFINITIA

    </title>

    <link rel="icon"
          type="image/x-icon"
          href="../assets/images/ico.ico">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="../assets/css/style.css">

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

                    Bienvenue <?=
                    htmlspecialchars($intervenant['first_name']);
                    ?> dans votre espace intervenant.

                </div>

            </div>

        </div>

        <!-- STATISTIQUES -->

        <div class="row">

            <!-- MISSIONS -->

            <div class="col s12 m6 l4">

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

                        12

                    </h3>

                </div>

            </div>

            <!-- MISSIONS TERMINEES -->

            <div class="col s12 m6 l4">

                <div class="dashboard-card">

                    <div class="card-icon pink-gradient">

                        <i class="material-icons">

                            task_alt

                        </i>

                    </div>

                    <h5>

                        Missions terminées

                    </h5>

                    <h3>

                        8

                    </h3>

                </div>

            </div>

            <!-- NOTE MOYENNE -->

            <div class="col s12 m6 l4">

                <div class="dashboard-card">

                    <div class="card-icon gold-gradient">

                        <i class="material-icons">

                            star

                        </i>

                    </div>

                    <h5>

                        Note moyenne

                    </h5>

                    <h3>

                        4.8

                    </h3>

                </div>

            </div>

        </div>

       

        <!-- DERNIERES MISSIONS -->

        <div class="table-card">

            <div class="table-title">

                Dernières Missions

            </div>

            <table class="highlight responsive-table">

                <thead>

                <tr>

                    <th>ID</th>
                    <th>Service</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Statut</th>

                </tr>

                </thead>

                <tbody>

                <tr>

                    <td>#001</td>

                    <td>Nettoyage résidentiel</td>

                    <td>Jean Test</td>

                    <td>05 Juin 2026</td>

                    <td>

                        <span class="status progress">

                            En cours

                        </span>

                    </td>

                </tr>

                <tr>

                    <td>#002</td>

                    <td>Jardinage</td>

                    <td>Marie K.</td>

                    <td>02 Juin 2026</td>

                    <td>

                        <span class="status completed">

                            Terminée

                        </span>

                    </td>

                </tr>

                <tr>

                    <td>#003</td>

                    <td>Entretien bureau</td>

                    <td>Patrick M.</td>

                    <td>28 Mai 2026</td>

                    <td>

                        <span class="status pending">

                            En attente

                        </span>

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