
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Missions

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

        $current_page = "missions";

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
                assignment
            </i>

            Missions

        </div>

        <div class="welcome-text">

            Suivez l'état d'avancement de toutes vos missions et prestations.

        </div>

    </div>

</div>

           <!-- STATISTIQUES -->

<div class="row">

    <div class="col s12 m6 l4">

        <div class="dashboard-card">

            <div class="card-icon blue-gradient">

                <i class="material-icons">
                    pending_actions
                </i>

            </div>

            <h5>
                Missions en attente
            </h5>

            <h3>
                5
            </h3>

        </div>

    </div>

    <div class="col s12 m6 l4">

        <div class="dashboard-card">

            <div class="card-icon pink-gradient">

                <i class="material-icons">
                    engineering
                </i>

            </div>

            <h5>
                Missions en cours
            </h5>

            <h3>
                3
            </h3>

        </div>

    </div>

    <div class="col s12 m6 l4">

        <div class="dashboard-card">

            <div class="card-icon gold-gradient">

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

</div>

          <!-- LISTE DES INTERVENANTS -->

<div class="table-card">

    <div class="table-header">

        <div class="table-title">

            Liste des missions

        </div>

    </div>

    <table class="highlight responsive-table">

        <thead>

            <tr>

                <th>Référence</th>
                <th>Service</th>
                <th>Intervenant</th>
                <th>Lieu</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Statut</th>
                <th>Actions</th>

            </tr>

        </thead>

        <tbody>

            <tr>

                <td>MIS-001</td>

                <td>Nettoyage résidentiel</td>

                <td>Marie Kasongo</td>

                <td>Kolwezi</td>

                <td>02/06/2026</td>

                <td>-</td>

                <td>

                    <span class="status progress">

                        En cours

                    </span>

                </td>

                <td>

                    <a href="#"
                       class="blue-text">

                        <i class="material-icons">
                            visibility
                        </i>

                    </a>

                </td>

            </tr>

            <tr>

                <td>MIS-002</td>

                <td>Jardinage</td>

                <td>Patrick Mutombo</td>

                <td>Kolwezi</td>

                <td>28/05/2026</td>

                <td>28/05/2026</td>

                <td>

                    <span class="status completed">

                        Terminée

                    </span>

                </td>

                <td>

                    <a href="#"
                       class="blue-text">

                        <i class="material-icons">
                            visibility
                        </i>

                    </a>

                </td>

            </tr>

            <tr>

                <td>MIS-003</td>

                <td>Repassage</td>

                <td>Sarah Tshibangu</td>

                <td>Lubumbashi</td>

                <td>-</td>

                <td>-</td>

                <td>

                    <span class="status pending">

                        En attente

                    </span>

                </td>

                <td>

                    <a href="#"
                       class="blue-text">

                        <i class="material-icons">
                            visibility
                        </i>

                    </a>

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