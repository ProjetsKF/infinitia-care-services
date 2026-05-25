<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Inscription | Infinitia Care Services</title>
    <link rel="icon" type="image/x-icon" href="assets/images/ico.ico">

    <!-- MATERIALIZE -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- ICONS -->

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- FONT -->

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

    <style>

        *{
            font-family:'Poppins', sans-serif;
        }

        body{

            background:#f5f7fb;
        }

        .register-section{

            min-height:100vh;

            display:flex;
            align-items:center;
            justify-content:center;

            padding:40px 0;
        }

        .main-title{

            font-size:42px;
            font-weight:700;

            color:#081f78;
        }

        .subtitle{

            color:#666;
            margin-top:10px;
        }

        .register-card{

            border-radius:20px;

            overflow:hidden;

            transition:0.4s;

            cursor:pointer;
        }

        .register-card:hover{

            transform:translateY(-8px);

            box-shadow:0 12px 30px rgba(0,0,0,0.15);
        }

        .register-card .card-content{

            padding:40px;
        }

        .icon-circle{

            width:90px;
            height:90px;

            border-radius:50%;

            display:flex;
            align-items:center;
            justify-content:center;

            margin:auto auto 20px auto;
        }

        .client-bg{

            background:#081f78;
        }

        .candidate-bg{

            background:#e83e8c;
        }

        .icon-circle i{

            font-size:45px;
            color:white;
        }

        .card-title{

            font-size:28px;
            font-weight:700;

            margin-bottom:15px;
        }

        .card-description{

            color:#666;
            min-height:70px;
        }

        .btn-register{

            margin-top:25px;

            border-radius:10px;

            width:100%;
        }

    </style>

</head>

<body>

    <section class="register-section">

        <div class="container">

            <!-- TITRE -->

            <div class="center">

                <h1 class="main-title">

                    Choisissez votre profil

                </h1>

                <p class="subtitle">

                    Sélectionnez le type de compte
                    que vous souhaitez créer.

                </p>

            </div>

            <br><br>

            <div class="row">

                <!-- CLIENT -->

                <div class="col s12 m6">

                    <div class="card register-card">

                        <div class="card-content center">

                            <div class="icon-circle client-bg">

                                <i class="material-icons">
                                    business_center
                                </i>

                            </div>

                            <span class="card-title">

                                Client

                            </span>

                            <p class="card-description">

                                Recherchez des services professionnels,
                                intervenants et assistance à domicile.

                            </p>

                            <a href="register-client.php"
                            class="btn-large blue darken-4 btn-register waves-effect waves-light">

                                Créer un compte client

                            </a>

                        </div>

                    </div>

                </div>

                <!-- CANDIDAT -->

                <div class="col s12 m6">

                    <div class="card register-card">

                        <div class="card-content center">

                            <div class="icon-circle candidate-bg">

                                <i class="material-icons">
                                    groups
                                </i>

                            </div>

                            <span class="card-title">

                                Intervenant

                            </span>

                            <p class="card-description">

                                Postulez pour des missions,
                                travaillez avec nos clients
                                et développez votre carrière.

                            </p>

                            <a href="register-candidate.php"
                            class="btn-large pink accent-2 btn-register waves-effect waves-light">

                                Devenir intervenant

                            </a>

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

</body>

</html>