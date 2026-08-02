<?php require_once("config/app.php"); ?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <base href="<?php echo app_url_html(""); ?>">

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Demande de Service | INFINITIA CARE SERVICES

    </title>

    <!-- MATERIALIZE CSS -->

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

    <!-- FAVICON -->

    <link rel="icon"
    type="image/x-icon"
    href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <style>

        *{
            font-family:'Poppins', sans-serif;
        }

        body{

            background:#f5f7fb;
        }

        .register-section{

            min-height:100vh;

            padding:30px 0 60px 0;
        }

        /* TITLE */

        .main-title{

            font-size:58px;

            font-weight:800;

            color:#081f78;

            line-height:1.1;

            margin-bottom:15px;
        }

        .subtitle{

            font-size:20px;

            color:#6b7280;

            max-width:750px;

            margin:auto;

            line-height:1.8;
        }

        /* CARD */

        .register-card{

            border-radius:25px !important;

            overflow:hidden;

            box-shadow:0 10px 25px rgba(0,0,0,0.12);
        }

        .register-card .card-content{

            padding:40px;
        }

        /* HEADER */

        .form-header{

            background:
            linear-gradient(
            90deg,
            #081f78,
            #e83e8c
            );

            padding:28px 40px;
        }

        .form-header h4{

            color:white;

            font-size:30px;

            font-weight:700;

            margin:0;
        }

        /* INPUTS */

        .input-field{

            margin-bottom:30px;
        }

        .input-field input,
        .input-field textarea{

            border-radius:10px !important;

            padding-left:15px !important;

            box-sizing:border-box !important;
        }

        .input-field .prefix{

            color:#081f78;
        }

        .input-field input:focus,
        .input-field textarea:focus{

            border-bottom:2px solid #081f78 !important;

            box-shadow:none !important;
        }

        .input-field label.active{

            color:#081f78 !important;
        }

        /* SELECT */

        .dropdown-content li>a,
        .dropdown-content li>span{

            color:#081f78 !important;
        }

        /* BUTTON */

        .btn-register{

            width:100%;

            height:58px;

            line-height:58px;

            border-radius:12px;

            font-size:16px;

            font-weight:600;

            text-transform:none;

            background:
            linear-gradient(
            45deg,
            #081f78,
            #e83e8c
            );
        }

        .btn-register:hover{

            background:
            linear-gradient(
            45deg,
            #e83e8c,
            #081f78
            );
        }

        /* BACK BUTTON */

        .back-home{

            margin-bottom:25px;
        }

        .back-btn{

            display:inline-flex;

            align-items:center;

            gap:8px;

            padding:12px 22px;

            border-radius:12px;

            background:white;

            color:#081f78;

            font-weight:600;

            box-shadow:0 4px 12px rgba(0,0,0,0.08);

            transition:0.3s;
        }

        .back-btn:hover{

            background:
            linear-gradient(
            45deg,
            #081f78,
            #e83e8c
            );

            color:white;
        }

        /* RESPONSIVE */

        @media(max-width:600px){

            .main-title{

                font-size:42px;
            }

            .subtitle{

                font-size:18px;
            }

            .register-card .card-content{

                padding:25px;
            }

            .form-header{

                padding:22px 25px;
            }

            .form-header h4{

                font-size:24px;
            }

        }

        .form-actions{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    margin-top:40px;

    flex-wrap:wrap;
}

.cancel-btn{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    min-width:170px;

    height:58px;

    padding:0 28px;

    border-radius:14px;

    background:white;

    color:#081f78;

    font-weight:600;

    font-size:16px;

    box-shadow:0 4px 12px rgba(0,0,0,0.08);

    transition:0.3s;
}

.cancel-btn:hover{

    background:#f1f5ff;

    transform:translateY(-2px);
}

.btn-register{

    flex:1;

    border-radius:14px !important;
}

@media(max-width:600px){

    .form-actions{

        flex-direction:column-reverse;
    }

    .cancel-btn,
    .btn-register{

        width:100%;
    }

}

    </style>

</head>

<body>

    <section class="register-section">

        <div class="container">

            

            <!-- TITLE -->

            <div class="center">

                <h1 class="main-title">

                    Demande de Service

                </h1>

                <p class="subtitle">

                    Soumettez votre besoin et notre équipe
                    vous mettra en relation avec un intervenant qualifié.

                </p>

            </div>

            <br><br>

            <div class="row">

                <div class="col s12 m12 l8 offset-l2">

                    <!-- CARD -->

                    <div class="card register-card">

                        <!-- HEADER -->

                        <div class="form-header">

                            <h4>

                                Informations de la demande

                            </h4>

                        </div>

                        <!-- CONTENT -->

                        <div class="card-content">

                            <form action=""
                            method="POST">

                                <!-- FULL NAME -->

                                <div class="input-field">

                                    <i class="material-icons prefix">

                                        person

                                    </i>

                                    <input
                                    type="text"
                                    id="fullname"
                                    required>

                                    <label for="fullname">

                                        Nom complet

                                    </label>

                                </div>

                                <!-- PHONE -->

                                <div class="input-field">

                                    <i class="material-icons prefix">

                                        phone

                                    </i>

                                    <input
                                    type="text"
                                    id="phone"
                                    required>

                                    <label for="phone">

                                        Téléphone

                                    </label>

                                </div>

                                <!-- EMAIL -->

                                <div class="input-field">

                                    <i class="material-icons prefix">

                                        email

                                    </i>

                                    <input
                                    type="email"
                                    id="email">

                                    <label for="email">

                                        Adresse Email

                                    </label>

                                </div>

                                <!-- SERVICE -->

                                <div class="input-field">

                                    <i class="material-icons prefix">

                                        work

                                    </i>

                                   <select required name="service_category">

    <option value=""
    disabled
    selected>

        Choisir un service

    </option>

    <optgroup label="Ménage et Entretien">

        <option value="menage_residentiel">

            Ménage résidentiel

        </option>

        <option value="menage_general">

            Grand ménage

        </option>

        <option value="nettoyage_appartement">

            Nettoyage d'appartement

        </option>

        <option value="nettoyage_maison">

            Nettoyage de maison

        </option>

        <option value="nettoyage_bureaux">

            Nettoyage de bureaux

        </option>

        <option value="nettoyage_commerces">

            Nettoyage de commerces

        </option>

    </optgroup>

    <optgroup label="Entretien du linge">

        <option value="lessive">

            Lessive

        </option>

        <option value="repassage">

            Repassage

        </option>

        <option value="lessive_repassage">

            Lessive et repassage

        </option>

    </optgroup>

    <optgroup label="Entretien extérieur">

        <option value="jardinage">

            Jardinage

        </option>

        <option value="entretien_cour">

            Entretien de la cour

        </option>

        <option value="nettoyage_terrasse">

            Nettoyage de terrasse

        </option>

    </optgroup>

    <optgroup label="Services complémentaires">

        <option value="cuisine_domicile">

            Cuisine à domicile

        </option>

        <option value="vaisselle">

            Vaisselle

        </option>

        <option value="rangement">

            Rangement et organisation

        </option>

    </optgroup>

</select>

                                    <label>

                                        Type de service

                                    </label>

                                </div>

                                <!-- LOCATION -->

                                <div class="input-field">

                                    <i class="material-icons prefix">

                                        location_on

                                    </i>

                                    <input
                                    type="text"
                                    id="location">

                                    <label for="location">

                                        Adresse / Localisation

                                    </label>

                                </div>

                                <!-- DATE -->

                                <div class="input-field">

                                    <i class="material-icons prefix">

                                        calendar_month

                                    </i>

                                    <input
                                    type="date"
                                    id="service_date">

                                </div>

                                <!-- BUDGET -->

                                <div class="input-field">

                                    <i class="material-icons prefix">

                                        payments

                                    </i>

                                    <input
                                    type="number"
                                    id="budget">

                                    <label for="budget">

                                        Budget estimatif

                                    </label>

                                </div>

                                <!-- DESCRIPTION -->

                                <div class="input-field">

                                    <i class="material-icons prefix">

                                        description

                                    </i>

                                    <textarea
                                    id="description"
                                    class="materialize-textarea"></textarea>

                                    <label for="description">

                                        Décrivez votre besoin

                                    </label>

                                </div>

                                <!-- BUTTON -->

                               <!-- ACTIONS -->

<div class="form-actions">

    <!-- CANCEL -->

    <a href="<?php echo app_url_html(""); ?>"
    class="cancel-btn waves-effect">

        <i class="material-icons left">

            close

        </i>

        Annuler

    </a>

    <!-- SUBMIT -->

    <button
    type="submit"
    class="btn-large btn-register waves-effect waves-light">

        Soumettre la demande

        <i class="material-icons right">

            send

        </i>

    </button>

</div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- MATERIALIZE JS -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <!-- INIT SELECT -->

    <script>

        document.addEventListener('DOMContentLoaded', function(){

            var elems = document.querySelectorAll('select');

            M.FormSelect.init(elems);

        });

    </script>

</body>

</html>
