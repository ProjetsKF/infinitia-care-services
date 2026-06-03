
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Intervenants

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

                    Intervenants

                </div>

                <div class="welcome-text">

                    Consultez les profils des intervenants disponibles et ceux affectés à vos demandes.

                </div>

            </div>

        </div>

           <!-- STATISTIQUES -->

<div class="row">

    <div class="col s12 m6 l4">

        <div class="dashboard-card">

            <div class="card-icon blue-gradient">

                <i class="material-icons">

                    groups

                </i>

            </div>

            <h5>

                Intervenants disponibles

            </h5>

            <h3>

                12

            </h3>

        </div>

    </div>

    <div class="col s12 m6 l4">

        <div class="dashboard-card">

            <div class="card-icon pink-gradient">

                <i class="material-icons">

                    assignment_ind

                </i>

            </div>

            <h5>

                Affectés à vos missions

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

          <!-- LISTE DES INTERVENANTS -->

<div class="table-card">

    <div class="table-header">

        <div class="table-title">

            Liste des intervenants

        </div>

    </div>

    <table class="highlight responsive-table">

        <thead>

            <tr>

                <th>Photo</th>
                <th>Nom</th>
                <th>Spécialité</th>
                <th>Ville</th>
                <th>Expérience</th>
                <th>Disponibilité</th>
                <th>Note</th>
                <th>Actions</th>

            </tr>

        </thead>

        <tbody>

            <tr>

                <td>

                    <img src="../assets/images/default-user.png"
                         width="50"
                         style="border-radius:50%;">

                </td>

                <td>Marie Kasongo</td>

                <td>Nettoyage résidentiel</td>

                <td>Kolwezi</td>

                <td>5 ans</td>

                <td>

                    <span class="status completed">
                        Disponible
                    </span>

                </td>

                <td>⭐ 4.9</td>

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

                <td>

                    <img src="../assets/images/default-user.png"
                         width="50"
                         style="border-radius:50%;">

                </td>

                <td>Patrick Mutombo</td>

                <td>Jardinage</td>

                <td>Kolwezi</td>

                <td>3 ans</td>

                <td>

                    <span class="status progress">
                        Affecté
                    </span>

                </td>

                <td>⭐ 4.7</td>

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