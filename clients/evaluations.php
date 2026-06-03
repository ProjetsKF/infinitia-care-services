
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

    <!-- ICON -->

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

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

            Évaluation des services

        </div>

        <div class="welcome-text">

            Consultez vos évaluations et notez les prestations réalisées par nos intervenants.

        </div>

    </div>

</div>
   

          <!-- LISTE DES INTERVENANTS -->
<div class="table-card">

    <div class="table-header">

        <div class="table-title">
                Historique des évaluations
            </div>

    </div>

    <table class="highlight responsive-table">

        <thead>

            <tr>

                <th>Mission</th>
                <th>Service</th>
                <th>Intervenant</th>
                <th>Note générale</th>
                <th>Ponctualité</th>
                <th>Professionnalisme</th>
                <th>Qualité</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>

            </tr>

        </thead>

        <tbody>

            <tr>

                <td>MIS-001</td>

                <td>Nettoyage résidentiel</td>

                <td>Marie Kasongo</td>

                <td>★★★★★</td>

                <td>★★★★★</td>

                <td>★★★★☆</td>

                <td>★★★★★</td>

                <td>02/06/2026</td>

                <td>

                    <span class="status completed">

                        Évaluée

                    </span>

                </td>

                <td>

                    <a href="#"
                       class="blue-text"
                       title="Voir">

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

                <td>★★★★☆</td>

                <td>★★★★☆</td>

                <td>★★★★★</td>

                <td>★★★★☆</td>

                <td>28/05/2026</td>

                <td>

                    <span class="status completed">

                        Évaluée

                    </span>

                </td>

                <td>

                    <a href="#"
                       class="blue-text"
                       title="Voir">

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

                <td>-</td>

                <td>-</td>

                <td>-</td>

                <td>-</td>

                <td>05/06/2026</td>

                <td>

                    <span class="status pending">

                        À évaluer

                    </span>

                </td>

                <td>

                    <a href="#"
                       class="green-text"
                       title="Évaluer">

                        <i class="material-icons">
                            star

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