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
 
<!-- SERVICES SECTION -->

<section class="section services-section">

    <div class="container">

        <!-- TITRE -->

        <div class="center">

            <h3 class="blue-text text-darken-4">
                Nos Services
            </h3>

            <p class="grey-text text-darken-1 flow-text">

                INFINITIA GROUP SARLU vous accompagne avec des solutions
                numériques modernes, des services de recrutement,
                de gestion et d’accompagnement des entreprises.

            </p>

        </div>

        <br><br>

        <!-- SERVICES -->

        <div class="row">

            <!-- SERVICE 1 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable">

                    <div class="card-content center">

                        <i class="material-icons large blue-text text-darken-4">
                            computer
                        </i>

                        <h5>
                            Plateformes Numériques
                        </h5>

                        <p>

                            Développement et gestion de plateformes web,
                            applications mobiles et solutions digitales
                            modernes adaptées aux entreprises.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 2 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable">

                    <div class="card-content center">

                        <i class="material-icons large pink-text accent-2">
                            groups
                        </i>

                        <h5>
                            Recrutement & Mise en Relation
                        </h5>

                        <p>

                            Services digitaux de recrutement,
                            sélection de personnel et mise en relation
                            dans plusieurs secteurs d’activités.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 3 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable">

                    <div class="card-content center">

                        <i class="material-icons large green-text">
                            business_center
                        </i>

                        <h5>
                            Gestion & Accompagnement
                        </h5>

                        <p>

                            Accompagnement des entreprises,
                            organisation administrative,
                            gestion du personnel et ressources humaines.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 4 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable">

                    <div class="card-content center">

                        <i class="material-icons large orange-text">
                            engineering
                        </i>

                        <h5>
                            Consultation Professionnelle
                        </h5>

                        <p>

                            Prestations de consultation,
                            marketing et représentation
                            dans divers domaines professionnels.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 5 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable">

                    <div class="card-content center">

                        <i class="material-icons large teal-text">
                            inventory_2
                        </i>

                        <h5>
                            Agroalimentaire
                        </h5>

                        <p>

                            Transformation, production et commercialisation
                            de produits agroalimentaires,
                            notamment les produits laitiers et le yaourt.

                        </p>

                    </div>

                </div>

            </div>

            <!-- SERVICE 6 -->

            <div class="col s12 m6 l4">

                <div class="card hoverable">

                    <div class="card-content center">

                        <i class="material-icons large purple-text">
                            public
                        </i>

                        <h5>
                            Services Multisectoriels
                        </h5>

                        <p>

                            Interventions dans les domaines
                            de la logistique, agriculture,
                            environnement, énergie,
                            tourisme et transport.

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