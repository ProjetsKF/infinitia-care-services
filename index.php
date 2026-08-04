<?php
session_start();
require_once("config/app.php");
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(__DIR__ . "/includes/pwa-head.php"); ?>

    <base href="<?php echo app_url_html(""); ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Accueil | INFINITIA CARE SERVICES</title>

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

                            <a href="<?php echo app_url_html("offres"); ?>"
                            class="btn-large btn-primary waves-effect waves-light">

                                Voir les offres

                                <i class="material-icons right">

                                    work

                                </i>

                            </a>

                            <!-- DEMANDE SERVICE -->

                            <a href="<?php echo app_url_html("intervenants"); ?>"
                           class="btn-large btn-secondary waves-effect waves-light">

                                Voir les intervenants

                                <i class="material-icons right">
                                    people
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

                Pourquoi choisir INFINITIA CARE SERVICES ?

            </h3>

            <p>

                INFINITIA Care Services facilite l’accès à des services de ménage
                et d’entretien grâce à une plateforme numérique qui met en relation
                les clients avec des intervenantes sélectionnées, formées et suivies.

            </p>

        </div>

        <br><br>

        <div class="row services-row">

            <!-- FIABILITE -->

            <div class="col s12 m6 l3">

                <div class="card service-card">

                    <div class="card-content center">

                        <i class="material-icons large blue-text">

                            verified_user

                        </i>

                        <span class="card-title">

                            Profils vérifiés

                        </span>

                        <p>

                            Les intervenantes passent par un processus de sélection,
                            de vérification et d’évaluation avant d’être affectées
                            aux demandes des clients.

                        </p>

                    </div>

                </div>

            </div>

            <!-- REACTIVITE -->

            <div class="col s12 m6 l3">

                <div class="card service-card">

                    <div class="card-content center">

                        <i class="material-icons large green-text">

                            speed

                        </i>

                        <span class="card-title">

                            Prise en charge rapide

                        </span>

                        <p>

                            Les demandes sont enregistrées, suivies et traitées
                            rapidement afin de trouver une intervenante disponible
                            selon le besoin du client.

                        </p>

                    </div>

                </div>

            </div>

            <!-- INTERVENANTES QUALIFIEES -->

            <div class="col s12 m6 l3">

                <div class="card service-card">

                    <div class="card-content center">

                        <i class="material-icons large orange-text">

                            groups

                        </i>

                        <span class="card-title">

                            Intervenantes qualifiées

                        </span>

                        <p>

                            La plateforme met à disposition des assistantes ménagères
                            formées pour les services de ménage, de nettoyage,
                            de lessive, de repassage et d’entretien.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SUIVI NUMERIQUE -->

            <div class="col s12 m6 l3">

                <div class="card service-card">

                    <div class="card-content center">

                        <i class="material-icons large purple-text">

                            dashboard

                        </i>

                        <span class="card-title">

                            Suivi numérique

                        </span>

                        <p>

                            Les clients peuvent soumettre leurs demandes,
                            suivre les missions, consulter les intervenantes
                            affectées et évaluer les services reçus.

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

        document.addEventListener("DOMContentLoaded", function () {
            var sidenavElements = document.querySelectorAll(".sidenav");

            if (
                typeof M !== "undefined" &&
                M.Sidenav &&
                sidenavElements.length > 0
            ) {
                M.Sidenav.init(sidenavElements, {
                    edge: "left",
                    draggable: true,
                    inDuration: 250,
                    outDuration: 200
                });
            }
        });

    </script>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('.modal');
    M.Modal.init(elems);
});
</script>

</body>

</html>
