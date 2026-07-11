<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Accueil | INFINITIA CARE SERVICES</title>

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
    
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

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

                            <a href="tout_intervenants.php"
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

                    Pourquoi choisir INFINITIA GROUP SARLU ?

                </h3>

               <p>

                    INFINITIA GROUP SARLU s'engage à fournir des solutions fiables,
                    innovantes et adaptées aux besoins de ses clients grâce à une
                    approche centrée sur la qualité, la réactivité et l'excellence
                    opérationnelle.

                </p>

            </div>

            <br><br>


   <!-- SERRRRRVICESSSSSSSSSSS-->

       <div class="row">

    <div class="col s12 m6 l3">
        <div class="card service-card">
            <div class="card-content center">
                <i class="material-icons large blue-text">verified_user</i>
                <span class="card-title">Fiabilité</span>
                <p>
                    Des profils vérifiés, un suivi structuré et des services organisés
                    pour garantir une expérience professionnelle.
                </p>
            </div>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card service-card">
            <div class="card-content center">
                <i class="material-icons large green-text">speed</i>
                <span class="card-title">Réactivité</span>
                <p>
                    Une prise en charge rapide des besoins grâce à une organisation
                    claire et des outils numériques adaptés.
                </p>
            </div>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card service-card">
            <div class="card-content center">
                <i class="material-icons large orange-text">groups</i>
                <span class="card-title">Réseau qualifié</span>
                <p>
                    Un réseau d’intervenants, partenaires et compétences mobilisables
                    selon les besoins des clients.
                </p>
            </div>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card service-card">
            <div class="card-content center">
                <i class="material-icons large purple-text">insights</i>
                <span class="card-title">Innovation</span>
                <p>
                    Des solutions modernes pour améliorer la gestion des services,
                    des missions et des relations clients.
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

    <script>
document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('.modal');
    M.Modal.init(elems);
});
</script>

</body>

</html>