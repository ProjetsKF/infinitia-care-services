<!-- =========================================
     CLIENT DASHBOARD
     FICHIER : clidashboard.php
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

    clients.client_type,
    clients.company_name,
    clients.address,
    clients.city,
    clients.gps_location

FROM users

INNER JOIN clients
ON users.id = clients.user_id

WHERE users.id = ?

LIMIT 1

";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$client = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Tableau de bord Client | INFINITIA

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

    <!-- ICON -->

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

</head>

<body>

    <div class="dashboard">

        

       <?php

        $current_page = "dashboard";

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

                        Tableau de Bord

                    </div>

                    <div class="welcome-text">

                        Bienvenue cher(e) <?php echo htmlspecialchars($client['first_name']); ?> sur votre espace client.

                    </div>

                </div>

            </div>

            <!-- STATS -->

            <div class="row">

                <!-- CARD 1 -->

                <div class="col s12 m6 l4">

                    <div class="dashboard-card">

                        <div class="card-icon blue-gradient">

                            <i class="material-icons">

                                assignment

                            </i>

                        </div>

                        <h5>

                            Demandes envoyées

                        </h5>

                        <h3>

                            12

                        </h3>

                    </div>

                </div>

                <!-- CARD 2 -->

                <div class="col s12 m6 l4">

                    <div class="dashboard-card">

                        <div class="card-icon pink-gradient">

                            <i class="material-icons">

                                engineering

                            </i>

                        </div>

                        <h5>

                            Intervenants actifs

                        </h5>

                        <h3>

                            5

                        </h3>

                    </div>

                </div>

                <!-- CARD 3 -->

                <div class="col s12 m6 l4">

                    <div class="dashboard-card">

                        <div class="card-icon gold-gradient">

                            <i class="material-icons">

                                payments

                            </i>

                        </div>

                        <h5>

                            Paiements effectués

                        </h5>

                        <h3>

                            8

                        </h3>

                    </div>

                </div>

            </div>

            <!-- TABLE -->

            <div class="table-card">

                <div class="table-title">

                    Dernières Demandes

                </div>

                <table class="highlight responsive-table">

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Intervenant</th>
                            <th>Statut</th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td>#001</td>

                            <td>Ménage à domicile</td>

                            <td>24 Mai 2026</td>

                            <td>Grâce K.</td>

                            <td>

                                <span class="status progress">

                                    En cours

                                </span>

                            </td>

                        </tr>

                        <tr>

                            <td>#002</td>

                            <td>Chauffeur privé</td>

                            <td>20 Mai 2026</td>

                            <td>Patrick M.</td>

                            <td>

                                <span class="status completed">

                                    Terminé

                                </span>

                            </td>

                        </tr>

                        <tr>

                            <td>#003</td>

                            <td>Agent d'entretien</td>

                            <td>18 Mai 2026</td>

                            <td>Sarah T.</td>

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

    <!-- MATERIALIZE JS -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

</body>

</html>