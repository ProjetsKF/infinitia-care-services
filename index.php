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

    <!-- HERO -->

    <section class="hero">

        <div class="container">

            <div class="row">

                <div class="col s12 m8 hero-content">

                    <h1 class="hero-title">

                        Services professionnels
                        et recrutement intelligent

                    </h1>

                    <p class="hero-subtitle">

                        Plateforme moderne de gestion des candidats,
                        missions, formations et services à domicile.

                    </p>

                    <div class="hero-buttons">

                        <!-- CLIENT -->

                       <!-- OFFRES -->

                            <a href="offers.php"
                            class="btn-large btn-primary waves-effect waves-light">

                                Voir les offres

                                <i class="material-icons right">

                                    work

                                </i>

                            </a>

                            <!-- DEMANDE SERVICE -->

                            <a href="guest-request.php"
                            class="btn-large btn-secondary waves-effect waves-light">

                                Soumettre une demande

                                <i class="material-icons right">

                                    assignment

                                </i>

                            </a>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- SERVICES -->

    <section class="section-services" id="services">

        <div class="container">

            <div class="center">

                <h3>
                    Nos Services
                </h3>

                <p>
                    Une plateforme complète pour la gestion
                    des services professionnels.
                </p>

            </div>

            <br><br>

            <div class="row">

                <!-- CARD -->

                <div class="col s12 m4">

                    <div class="card service-card">

                        <div class="card-content center">

                            <i class="material-icons large blue-text">
                                groups
                            </i>

                            <span class="card-title">

                                Candidats

                            </span>

                            <p>

                                Gestion des profils candidats
                                et documents professionnels.

                            </p>

                        </div>

                    </div>

                </div>

                <!-- CARD -->

                <div class="col s12 m4">

                    <div class="card service-card">

                        <div class="card-content center">

                            <i class="material-icons large green-text">
                                assignment
                            </i>

                            <span class="card-title">

                                Missions

                            </span>

                            <p>

                                Affectation et suivi
                                des missions de travail.

                            </p>

                        </div>

                    </div>

                </div>

                <!-- CARD -->

                <div class="col s12 m4">

                    <div class="card service-card">

                        <div class="card-content center">

                            <i class="material-icons large orange-text">
                                school
                            </i>

                            <span class="card-title">

                                Formations

                            </span>

                            <p>

                                Gestion des formations
                                et certifications.

                            </p>

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