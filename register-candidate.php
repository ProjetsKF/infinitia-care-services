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

        Devenir Intervenant |
        Infinitia Care Services

    </title>

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

            <!-- TITRE -->
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


            <div class="center">

                <h2 class="page-title">

                    Devenir Intervenant

                </h2>

                <p class="subtitle">

                    Complétez votre profil professionnel.

                </p>

            </div>

            <br>

            <div class="row">

                <div class="col s12 m10 offset-m1 l8 offset-l2">

                    <div class="card register-card">

                        <!-- HEADER -->

                        <div class="card-header">

                            <h4>

                                Informations personnelles

                            </h4>

                        </div>

                        <!-- FORM -->

                        <div class="card-content">

                            <form
                            action="process-register-candidate.php"
                            method="POST">

                                <!-- DATE NAISSANCE -->

                                <div class="row">

                                    <div class="input-field col s12 m6">

                                        <input
                                        type="date"
                                        name="birth_date"
                                        required>

                                        <label class="active">

                                            Date de naissance

                                        </label>

                                    </div>

                                    <!-- GENRE -->

                                    <div class="input-field col s12 m6">

                                        <select
                                        name="gender"
                                        required>

                                            <option value=""
                                            disabled
                                            selected>

                                                Choisir

                                            </option>

                                            <option value="male">
                                                Homme
                                            </option>

                                            <option value="female">
                                                Femme
                                            </option>

                                        </select>

                                        <label>

                                            Sexe

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

                                <!-- VILLE + NATIONALITE -->

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

                                    <div class="input-field col s12 m6">

                                        <input
                                        type="text"
                                        name="nationality"
                                        required>

                                        <label>

                                            Nationalité

                                        </label>

                                    </div>

                                </div>

                                <!-- SITUATION MATRIMONIALE -->

                                <div class="row">

                                    <div class="input-field col s12 m6">

                                        <select
                                        name="marital_status"
                                        required>

                                            <option value=""
                                            disabled
                                            selected>

                                                Choisir

                                            </option>

                                            <option value="single">
                                                Célibataire
                                            </option>

                                            <option value="married">
                                                Marié(e)
                                            </option>

                                            <option value="divorced">
                                                Divorcé(e)
                                            </option>

                                            <option value="widowed">
                                                Veuf / Veuve
                                            </option>

                                        </select>

                                        <label>

                                            Situation matrimoniale

                                        </label>

                                    </div>

                                    <!-- NIVEAU ETUDE -->

                                    <div class="input-field col s12 m6">

                                        <select
                                        name="education_level"
                                        required>

                                            <option value=""
                                            disabled
                                            selected>

                                                Choisir

                                            </option>

                                            <option value="primary">
                                                Primaire
                                            </option>

                                            <option value="secondary">
                                                Secondaire
                                            </option>

                                            <option value="university">
                                                Universitaire
                                            </option>

                                            <option value="technical">
                                                Technique
                                            </option>

                                        </select>

                                        <label>

                                            Niveau d'étude

                                        </label>

                                    </div>

                                </div>

                                <!-- EXPERIENCE -->

                                <div class="row">

                                    <div class="input-field col s12 m6">

                                        <input
                                        type="number"
                                        name="experience_years"
                                        min="0"
                                        required>

                                        <label>

                                            Années d'expérience

                                        </label>

                                    </div>

                                    <!-- CONTACT URGENCE -->

                                    <div class="input-field col s12 m6">

                                        <input
                                        type="text"
                                        name="emergency_contact"
                                        required>

                                        <label>

                                            Contact d'urgence

                                        </label>

                                    </div>

                                </div>

                                <!-- BIO -->

                                <div class="row">

                                    <div class="input-field col s12">

                                        <textarea
                                        name="bio"
                                        class="materialize-textarea"
                                        required>
                                        </textarea>

                                        <label>

                                            Présentation / Biographie

                                        </label>

                                    </div>

                                </div>

                                <!-- BOUTON -->

                                <br>

                                <button
                                type="submit"
                                class="btn-large btn-register waves-effect waves-light">

                                    Soumettre mon profil

                                    <i class="material-icons right">

                                        send

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

    <!-- MATERIALIZE JS -->

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