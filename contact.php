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

<!-- CONTACT SECTION -->

<!-- CONTACT SECTION -->

<section class="section contact-section">

  <div class="container contact-container">

        <!-- TITLE -->

        <div class="center contact-header">

            <h2 class="section-title">

                Contactez-Nous

            </h2>

            <p class="section-subtitle flow-text">

                Notre équipe reste disponible pour répondre
                à vos besoins en recrutement,
                solutions numériques et services professionnels.

            </p>

        </div>

        <!-- CONTACT CONTENT -->

        <div class="row contact-wrapper">

            <!-- LEFT SIDE -->

            <div class="col s12 l5">

                <div class="contact-info-card">

                    <h4>

                        Informations de Contact

                    </h4>

                    <!-- ITEM -->

                    <div class="contact-item">

                        <div class="contact-icon blue darken-4">

                            <i class="material-icons">

                                location_on

                            </i>

                        </div>

                        <div>

                            <h6>

                                Adresse

                            </h6>

                            <p>

                                02 Avenue Fridolin Mutunda,
                                Quartier Joli Site,
                                Commune de Manika,
                                Kolwezi, Lualaba, RDC

                            </p>

                        </div>

                    </div>

                    <!-- ITEM -->

                    <div class="contact-item">

                        <div class="contact-icon pink accent-2">

                            <i class="material-icons">

                                phone

                            </i>

                        </div>

                        <div>

                            <h6>

                                Téléphone

                            </h6>

                            <p>

                                +243 843 794 809

                            </p>

                        </div>

                    </div>

                    <!-- ITEM -->

                    <div class="contact-item">

                        <div class="contact-icon amber darken-2">

                            <i class="material-icons">

                                email

                            </i>

                        </div>

                        <div>

                            <h6>

                                Email

                            </h6>

                            <p>

                                infinitiagroupsarlu@gmail.com

                            </p>

                        </div>

                    </div>

                    <!-- ITEM -->

                    <div class="contact-item">

                        <div class="contact-icon green">

                            <i class="material-icons">

                                business_center

                            </i>

                        </div>

                        <div>

                            <h6>

                                Nos Services

                            </h6>

                            <p>

                                Recrutement • Plateformes numériques •
                                Gestion RH • Services à domicile •
                                Accompagnement des entreprises

                            </p>

                        </div>

                    </div>

                </div>

            </div>

            <!-- RIGHT SIDE -->

            <div class="col s12 l7">

                <div class="contact-form-card">

                    <h4>

                        Envoyez-nous un message

                    </h4>

                    <form>

                        <div class="row">

                            <div class="input-field col s12 m6">

                                <input type="text" id="name">

                                <label for="name">

                                    Nom complet

                                </label>

                            </div>

                            <div class="input-field col s12 m6">

                                <input type="text" id="phone">

                                <label for="phone">

                                    Téléphone

                                </label>

                            </div>

                        </div>

                        <div class="input-field">

                            <input type="email" id="email">

                            <label for="email">

                                Adresse Email

                            </label>

                        </div>

                        <div class="input-field">

                            <input type="text" id="subject">

                            <label for="subject">

                                Sujet

                            </label>

                        </div>

                        <div class="input-field">

                            <textarea
                            id="message"
                            class="materialize-textarea"></textarea>

                            <label for="message">

                                Votre message

                            </label>

                        </div>

                        <br>

                        <button
                        class="btn-large btn-primary waves-effect waves-light">

                            Envoyer le message

                        </button>

                    </form>

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