

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        Mes formations | INFINITIA

    </title>

    <link rel="icon"
          type="image/x-icon"
          href="../assets/images/ico.ico">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="../assets/css/style.css">

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "formations";

    include("menuin.php");

    ?>


<div class="main-content">

    <div class="topbar">

        <div class="page-title">
            Mes Formations
        </div>

    </div>

    <div class="row">

        <div class="col s12 m6">
            <div class="card blue darken-3 white-text">
                <div class="card-content">
                    <span class="card-title">
                        Formations disponibles
                    </span>
                    <h3>
                        0
                    </h3>
                </div>
            </div>
        </div>

        <div class="col s12 m6">
            <div class="card green darken-2 white-text">
                <div class="card-content">
                    <span class="card-title">
                        Durée totale
                    </span>
                    <h3>
                        0 min
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="table-card">

        <div class="table-title">
            Mes formations
        </div>

        <table class="highlight responsive-table">

            <thead>

                <tr>
                    <th>Formation</th>
                    <th>Description</th>
                    <th>Durée</th>
                    <th>Action</th>
                </tr>

            </thead>

            <tbody>

                <tr>

                    <td>
                        Techniques de nettoyage professionnel
                    </td>

                    <td>
                        Formation sur les bonnes pratiques de nettoyage et d'entretien.
                    </td>

                    <td>
                        45 min
                    </td>

                    <td>

                        <a href="#videoModal"
                           class="btn blue modal-trigger">

                            <i class="material-icons left">
                                play_circle
                            </i>

                            Suivre

                        </a>

                    </td>

                </tr>

                <tr>

                    <td>
                        Assistance aux personnes âgées
                    </td>

                    <td>
                        Formation de base pour l'accompagnement des personnes âgées.
                    </td>

                    <td>
                        1 h 20 min
                    </td>

                    <td>

                        <a href="#videoModal"
                           class="btn blue modal-trigger">

                            <i class="material-icons left">
                                play_circle
                            </i>

                            Suivre

                        </a>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>



<div id="videoModal" class="modal">

    <div class="modal-content">

        <h5>Formation vidéo</h5>

        <iframe
            src="https://www.youtube.com/embed/JZWmrq_K3b4"
            style="
                width:100%;
                height:75vh;
                border:none;
                border-radius:8px;
            "
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
        </iframe>

    </div>

    <div class="modal-footer">

        <a href="#!"
           class="modal-close btn grey">
            Fermer
        </a>

    </div>

</div>


</div>





<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function() {

    var elems = document.querySelectorAll('.modal');

    M.Modal.init(elems, {
        opacity: 0.7,
        inDuration: 250,
        outDuration: 250
    });

});

</script>

</body>
</html>