<?php
session_start();
require_once("config/app.php");
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <base href="<?php echo app_url_html(""); ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>À propos | INFINITIA CARE SERVICES</title>

    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <!-- Materialize CSS -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

    <!-- Google Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


</head>

<body>

 <?php include "includes/navbar.php"; ?>
 

<!-- ABOUT SECTION -->
<!-- ABOUT SECTION -->

<!-- ABOUT SECTION -->

<section class="section about-section white">

    <div class="container">

        <div class="row about-wrapper">

            <!-- IMAGE -->

            <div class="col s12 m12 l6 about-image-container">

                <img
                src="<?php echo app_url_html("assets/images/aboutimg.png"); ?>"
                alt="Assistante ménagère INFINITIA Care Services"
                class="about-image z-depth-3">

            </div>

            <!-- CONTENT -->

            <div class="col s12 m12 l6">

                <div class="about-content">

                    <h3 class="section-title">

                        À propos d’INFINITIA Care Services

                    </h3>

                    <p class="flow-text grey-text text-darken-1">

                        INFINITIA Care Services est une plateforme numérique
                        spécialisée dans le recrutement, la formation et la mise
                        en relation d’assistantes ménagères qualifiées avec les
                        familles, les particuliers et les entreprises.

                    </p>

                    <p>

                        Notre plateforme facilite la recherche de personnel
                        fiable pour les services de ménage, de nettoyage,
                        d’entretien du domicile, de lessive, de repassage
                        et d’assistance ménagère.

                    </p>

                    <p>

                        Nous accompagnons les intervenantes à travers un processus
                        de sélection, de vérification, de formation et de suivi
                        afin d’améliorer la qualité des prestations proposées
                        aux clients.

                    </p>

                    <p>

                        Notre objectif est de sécuriser les services à domicile,
                        de simplifier la mise en relation et de créer des
                        opportunités de travail décent pour les femmes et les
                        jeunes dans la ville de Kolwezi et progressivement
                        dans d’autres zones.

                    </p>

                    <p>

                        Grâce à la plateforme, les clients peuvent soumettre
                        leurs demandes, suivre leurs missions, consulter les
                        intervenantes affectées et évaluer les services reçus.

                    </p>

                    <div class="row about-values">

                        <div class="col s12 m6">

                            <div class="card-panel blue darken-4 white-text center value-card">

                                <i class="material-icons medium">

                                    verified_user

                                </i>

                                <h6>

                                    Confiance et sécurité

                                </h6>

                            </div>

                        </div>

                        <div class="col s12 m6">

                            <div class="card-panel pink accent-2 white-text center value-card">

                                <i class="material-icons medium">

                                    cleaning_services

                                </i>

                                <h6>

                                    Qualité de service

                                </h6>

                            </div>

                        </div>

                        <div class="col s12 m6">

                            <div class="card-panel teal white-text center value-card">

                                <i class="material-icons medium">

                                    school

                                </i>

                                <h6>

                                    Formation

                                </h6>

                            </div>

                        </div>

                        <div class="col s12 m6">

                            <div class="card-panel amber darken-2 white-text center value-card">

                                <i class="material-icons medium">

                                    diversity_3

                                </i>

                                <h6>

                                    Inclusion professionnelle

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
