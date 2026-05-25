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

    <!-- Google Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

   <style>

    *{
        font-family:'Poppins', sans-serif;
    }

    :root{

        /* Couleurs principales INFINITIA */

        --primary-blue:#081f78;
        --secondary-pink:#e83e8c;
        --gold:#d89b2b;

        --dark-blue:#05134d;
        --light-bg:#f7f7f7;
        --text-dark:#1b1b1b;
        --text-light:#ffffff;

    }

    body{
        background:var(--light-bg);
        color:var(--text-dark);
    }

    /* NAVBAR */

    nav{

        background:
        linear-gradient(
        90deg,
        var(--dark-blue),
        var(--primary-blue)
        );

        box-shadow:0 4px 15px rgba(0,0,0,0.2);
    }

    nav .brand-logo{

        font-weight:700;
        color:white;
    }

    nav ul li a{

        color:white;
        font-weight:500;
        transition:0.3s;
    }

    nav ul li a:hover{

        color:var(--secondary-pink);
    }

    /* HERO SECTION */

    .hero{

    position:relative;
    height:100vh;

    background:
    linear-gradient(
    rgba(5,19,77,0.75),
    rgba(8,31,120,0.75)
    ),

    url('assets/images/hero1.jpg');

    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;

    display:flex;
    align-items:center;
}

    .hero-content{

        color:white;
    }

    .hero-title{

        font-size:65px;
        font-weight:700;
        line-height:1.2;

        color:white;
    }

    .hero-title span{

        color:var(--secondary-pink);
    }

    .hero-subtitle{

        font-size:20px;
        margin-top:20px;

        color:#f1f1f1;

        max-width:700px;
    }

    .hero-buttons{

        margin-top:35px;
    }

    /* BOUTONS */

    .btn-large{

        border-radius:12px;
        margin-right:15px;

        text-transform:none;
        font-weight:600;

        transition:0.3s;
    }

    .btn-primary{

        background:
        linear-gradient(
        45deg,
        var(--primary-blue),
        var(--secondary-pink)
        );
    }

    .btn-primary:hover{

        background:
        linear-gradient(
        45deg,
        var(--secondary-pink),
        var(--primary-blue)
        );

        transform:translateY(-2px);
    }

    .btn-secondary{

        background:white;
        color:var(--primary-blue);
    }

    .btn-secondary:hover{

        background:var(--gold);
        color:white;
    }

    /* SERVICES */

    .section-services{

        padding:90px 0;
    }

    .section-title{

        color:var(--primary-blue);
        font-weight:700;
    }

    .section-subtitle{

        color:#666;
    }

    /* SERVICE CARDS */

    .service-card{

        border-radius:20px;

        transition:0.4s;

        overflow:hidden;

        background:white;
    }

    .service-card:hover{

        transform:translateY(-8px);

        box-shadow:0 10px 30px rgba(0,0,0,0.15);
    }

    .service-card .card-content{

        padding:35px;
    }

    .service-card i{

        margin-bottom:15px;
    }

    .service-card .card-title{

        color:var(--primary-blue);

        font-weight:700;
    }

    /* ICON COLORS */

    .icon-blue{
        color:var(--primary-blue);
    }

    .icon-pink{
        color:var(--secondary-pink);
    }

    .icon-gold{
        color:var(--gold);
    }

    /* FOOTER */

    footer{

        background:
        linear-gradient(
        90deg,
        var(--dark-blue),
        var(--primary-blue)
        );

        color:white;

        padding:30px;

        text-align:center;

        font-size:15px;
    }

    /* RESPONSIVE */

    @media(max-width:992px){

        .hero-title{

            font-size:45px;
        }

        .hero-subtitle{

            font-size:18px;
        }
    }

    @media(max-width:600px){

        .hero{

            text-align:center;
            padding:20px;
        }

        .hero-title{

            font-size:35px;
        }

        .btn-large{

            width:100%;
            margin-bottom:15px;
        }
    }

</style>

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

                        <a href="services.php"
                        class="btn-large btn-primary waves-effect waves-light">

                            Trouver un service

                            <i class="material-icons right">
                                search
                            </i>

                        </a>

                        <!-- CANDIDAT -->

                        <a href="register.php?type=candidate"
                        class="btn-large btn-secondary waves-effect">

                            Devenir intervenant

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