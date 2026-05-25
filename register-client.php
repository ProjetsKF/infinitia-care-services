<?php

session_start();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Créer un compte client |
        Infinitia Care Services

    </title>

    <!-- FAVICON -->

    <link rel="icon"
    type="image/x-icon"
    href="assets/images/ico.ico">

    <!-- MATERIALIZE CSS -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- GOOGLE ICONS -->

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- GOOGLE FONT -->

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

    <style>

        *{
            font-family:'Poppins', sans-serif;
        }

        body{

            background:#f4f6fb;
        }

        .register-section{

            padding:60px 0;
        }

        .register-card{

            border-radius:20px;
            overflow:hidden;
        }

        .card-header{

            background:
            linear-gradient(
            45deg,
            #081f78,
            #e83e8c
            );

            padding:35px;

            color:white;
        }

        .card-header h4{

            margin:0;
            font-weight:700;
        }

        .card-content{

            padding:40px !important;
        }

        .input-field input:focus{

            border-bottom:1px solid #081f78 !important;
            box-shadow:0 1px 0 0 #081f78 !important;
        }

        .input-field textarea:focus{

            border-bottom:1px solid #081f78 !important;
            box-shadow:0 1px 0 0 #081f78 !important;
        }

        .input-field label.active{

            color:#081f78 !important;
        }

        .btn-register{

            width:100%;
            border-radius:10px;
            height:55px;
            line-height:55px;

            background:
            linear-gradient(
            45deg,
            #081f78,
            #e83e8c
            );
        }

        .page-title{

            font-size:42px;
            font-weight:700;

            color:#081f78;
        }

        .subtitle{

            color:#666;
        }

    </style>

</head>

<body>

    <section class="register-section">

        <div class="container">

            <!-- BOUTON RETOUR -->

            <a href="register.php"
            class="btn waves-effect waves-light white blue-text text-darken-4 z-depth-1"
            style="
            margin-bottom:25px;
            border-radius:10px;
            ">

                <i class="material-icons left">
                    arrow_back
                </i>

                Retour

            </a>

            <!-- TITRE -->

            <div class="center">

                <h2 class="page-title">

                    Créer un compte Client

                </h2>

                <p class="subtitle">

                    Complétez les informations
                    de votre entreprise ou profil.

                </p>

            </div>

            <br>

            <div class="row">

                <div class="col s12 m10 offset-m1 l8 offset-l2">

                    <div class="card register-card">

                        <!-- HEADER -->

                        <div class="card-header">

                            <h4>

                                Informations client

                            </h4>

                        </div>

                        <!-- FORM -->

                        <div class="card-content">

                            <form
                            action="process-register-client.php"
                            method="POST">

                                <!-- TYPE CLIENT -->

                                <div class="row">

                                    <div class="input-field col s12">

                                        <select
                                        name="client_type"
                                        required>

                                            <option value=""
                                            disabled
                                            selected>

                                                Choisir

                                            </option>

                                            <option value="individual">

                                                Particulier

                                            </option>

                                            <option value="company">

                                                Entreprise

                                            </option>

                                            <option value="expatriate">

                                                Expatrié

                                            </option>

                                        </select>

                                        <label>

                                            Type de client

                                        </label>

                                    </div>

                                </div>

                                <!-- NOM ENTREPRISE -->

                                <div class="row">

                                    <div class="input-field col s12">

                                        <input
                                        type="text"
                                        name="company_name">

                                        <label>

                                            Nom de l'entreprise
                                            (optionnel)

                                        </label>

                                    </div>

                                </div>

                                <!-- ADRESSE -->

                                <div class="row">

                                    <div class="input-field col s12">

                                        <textarea
                                        name="address"
                                        class="materialize-textarea"
                                        required>
                                        </textarea>

                                        <label>

                                            Adresse complète

                                        </label>

                                    </div>

                                </div>

                                <!-- VILLE -->

                                <div class="row">

                                    <div class="input-field col s12 m6">

                                        <input
                                        type="text"
                                        name="city"
                                        required>

                                        <label>

                                            Ville

                                        </label>

                                    </div>

                                    <!-- GPS -->

                                    <div class="input-field col s12 m6">

                                        <input
                                        type="text"
                                        name="gps_location">

                                        <label>

                                            Localisation GPS
                                            (optionnel)

                                        </label>

                                    </div>

                                </div>

                                <!-- BOUTON -->

                                <br>

                                <button
                                type="submit"
                                class="btn-large btn-register waves-effect waves-light">

                                    Continuer

                                    <i class="material-icons right">

                                        arrow_forward

                                    </i>

                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- JQUERY -->

    <script
    src="https://code.jquery.com/jquery-3.7.1.min.js">
    </script>

    <!-- MATERIALIZE -->

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