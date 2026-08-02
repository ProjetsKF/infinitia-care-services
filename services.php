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

   <title>Nos services | INFINITIA CARE SERVICES</title>

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
 
<!-- SERVICES SECTION -->

<!-- SERVICES SECTION -->

<section class="section services-section">

    <div class="container">

        <!-- TITRE -->

        <div class="center services-header">

            <h3 class="blue-text text-darken-4">

                Nos services

            </h3>

            <p class="grey-text text-darken-1 flow-text">

                INFINITIA Care Services met à votre disposition
                des intervenantes sélectionnées et formées pour répondre
                à vos besoins de ménage, de nettoyage et d’entretien.

            </p>

        </div>

        <br><br>

        <div class="row services-row">

            <!-- SERVICE 1 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable service-card">

                    <div class="card-content center">

                        <i class="material-icons large icon-blue">

                            home

                        </i>

                        <h5>

                            Ménage résidentiel

                        </h5>

                        <p>

                            Nettoyage et entretien régulier des maisons,
                            appartements, chambres et espaces de vie.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 2 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable service-card">

                    <div class="card-content center">

                        <i class="material-icons large icon-pink">

                            business

                        </i>

                        <h5>

                            Nettoyage de bureaux

                        </h5>

                        <p>

                            Entretien des bureaux, espaces professionnels,
                            commerces et locaux administratifs.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 3 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable service-card">

                    <div class="card-content center">

                        <i class="material-icons large icon-gold">

                            local_laundry_service

                        </i>

                        <h5>

                            Lessive et repassage

                        </h5>

                        <p>

                            Lavage, séchage, pliage et repassage
                            des vêtements et du linge de maison.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 4 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable service-card">

                    <div class="card-content center">

                        <i class="material-icons large green-text">

                            cleaning_services

                        </i>

                        <h5>

                            Grand ménage

                        </h5>

                        <p>

                            Nettoyage complet et approfondi avant ou après
                            un déménagement, une réception ou une longue absence.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 5 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable service-card">

                    <div class="card-content center">

                        <i class="material-icons large purple-text">

                            kitchen

                        </i>

                        <h5>

                            Entretien de la cuisine

                        </h5>

                        <p>

                            Nettoyage de la cuisine, vaisselle,
                            rangement et entretien des équipements ménagers.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 6 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable service-card">

                    <div class="card-content center">

                        <i class="material-icons large teal-text">

                            yard

                        </i>

                        <h5>

                            Entretien des espaces extérieurs

                        </h5>

                        <p>

                            Nettoyage des cours, terrasses, entrées
                            et autres espaces extérieurs du domicile.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 7 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable service-card">

                    <div class="card-content center">

                        <i class="material-icons large orange-text">

                            inventory_2

                        </i>

                        <h5>

                            Rangement et organisation

                        </h5>

                        <p>

                            Organisation des pièces, armoires, cuisines,
                            réserves et espaces de rangement.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 8 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable service-card">

                    <div class="card-content center">

                        <i class="material-icons large blue-grey-text">

                            event_available

                        </i>

                        <h5>

                            Nettoyage après événement

                        </h5>

                        <p>

                            Remise en ordre et nettoyage des espaces
                            après une fête, une réunion ou une cérémonie.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 9 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable service-card">

                    <div class="card-content center">

                        <i class="material-icons large pink-text">

                            groups

                        </i>

                        <h5>

                            Mise à disposition d’intervenantes

                        </h5>

                        <p>

                            Recherche et affectation d’assistantes ménagères
                            qualifiées selon les besoins du client.

                        </p>

                    </div>

                </div>

            </div>

        </div>

        <!-- ACTION -->

        <div class="center services-action">

            <a href="<?php echo app_url_html("demande-service"); ?>"
            class="btn-large btn-primary waves-effect waves-light">

                Soumettre une demande

                <i class="material-icons right">

                    send

                </i>

            </a>

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
