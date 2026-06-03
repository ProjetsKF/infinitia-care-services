<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Infinitia Care Services</title>

    <link rel="icon" type="image/x-icon" href="assets/images/ico.ico">

    <!-- Materialize CSS -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Google Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">


</head>

<body>

 <?php include "includes/navbar.php"; ?>
 

<!-- ABOUT SECTION -->
<!-- ABOUT SECTION -->

<section class="section about-section white">

    <div class="container">

    <div class="row about-wrapper">

        <!-- IMAGE -->

        <div class="col s12 m12 l6 about-image-container">

            <img
            src="assets/images/aboutimg.png"
            alt="Infinitia Care Services"
            class="about-image z-depth-3">

        </div>

        <!-- CONTENT -->

        <div class="col s12 m12 l6">

            <div class="about-content">

                <h3 class="section-title">

                    À Propos de Nous

                </h3>

                <p class="flow-text grey-text text-darken-1">

                    INFINITIA GROUP SARLU est une entreprise
                    innovante spécialisée dans les solutions numériques,
                    le recrutement, la gestion des talents
                    et l’accompagnement des entreprises.

                </p>

                <p>

                    Notre mission est de connecter les entreprises,
                    les professionnels et les particuliers
                    à des solutions modernes, fiables et accessibles
                    grâce aux technologies digitales.

                </p>

                <p>

                    Nous développons des plateformes numériques,
                    des applications web et mobiles,
                    ainsi que des services de mise en relation,
                    de gestion du personnel et de formation.

                </p>

                <p>

                    INFINITIA GROUP intervient également
                    dans plusieurs secteurs tels que
                    l’agroalimentaire, la logistique,
                    l’environnement, l’énergie,
                    le tourisme et le transport.

                </p>

                <br>

                <!-- VALUES -->

                <div class="row">

                    <!-- VALUE 1 -->

                    <div class="col s12 m6">

                        <div class="card-panel blue darken-4 white-text center value-card">

                            <i class="material-icons medium">

                                verified

                            </i>

                            <h6>

                                Professionnalisme

                            </h6>

                        </div>

                    </div>

                    <!-- VALUE 2 -->

                    <div class="col s12 m6">

                        <div class="card-panel pink accent-2 white-text center value-card">

                            <i class="material-icons medium">

                                lightbulb

                            </i>

                            <h6>

                                Innovation

                            </h6>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</section>


 <?php include "includes/footer.php"; ?>

    <!-- JQuery -->

    <script
    src="https://code.jquery.com/jquery-3.7.1.min.js">
    </script>

    <!-- Materialize JS -->

    <script
    src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js">
    </script>

    <script>

        document.addEventListener('DOMContentLoaded', function(){

            M.AutoInit();

        });

    </script>

</body>

</html>