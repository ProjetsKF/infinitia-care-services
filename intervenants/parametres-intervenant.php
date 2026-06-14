<?php

session_start();

require_once("../config/database.php");

/*
|--------------------------------------------------------------------------
| Vérification de la session
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Mise à jour de la disponibilité
|--------------------------------------------------------------------------
*/

if (isset($_POST['update_availability']))
{
    $availability_status = $_POST['availability_status'];

    $stmt = $conn->prepare("
        UPDATE candidates
        SET availability_status = ?
        WHERE user_id = ?
    ");

    $stmt->bind_param(
        "si",
        $availability_status,
        $user_id
    );

    $stmt->execute();

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| Récupération du statut actuel
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT availability_status
    FROM candidates
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$candidate = $result->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| Valeur par défaut
|--------------------------------------------------------------------------
*/

if (
    $candidate &&
    isset($candidate['availability_status']) &&
    !empty($candidate['availability_status'])
)
{
    $current_status = $candidate['availability_status'];
}
else
{
    $current_status = 'hors_ligne';
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        Mes paramètres | INFINITIA

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

    $current_page = "parametres";

    include("menuin.php");

    ?>


<div class="main-content">

    <div class="topbar">

        <div class="page-title">
            Paramètres
        </div>

    </div>

    <div class="row">

        <div class="col s12 m6">

            <div class="card blue darken-3 white-text">

                <div class="card-content">

                    <span class="card-title">
                        Statut actuel
                    </span>

                    <h4>

                        <?php

                        echo ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $current_status
                            )
                        );

                        ?>

                    </h4>

                </div>

            </div>

        </div>

        <div class="col s12 m6">

            <div class="card green darken-2 white-text">

                <div class="card-content">

                    <span class="card-title">
                        Dernière mise à jour
                    </span>

                    <h4>
                        Aujourd'hui
                    </h4>

                </div>

            </div>

        </div>

    </div>

    <div class="table-card">

        <div class="table-title">
            Gestion de la disponibilité
        </div>

        <div class="row">

            <form method="POST">

                <div class="input-field col s12 m6">

                    <select name="availability_status">

                        <option
                            value="disponible"
                            <?php

                            if($current_status == 'disponible')
                            {
                                echo 'selected="selected"';
                            }

                            ?>
                        >
                            Disponible
                        </option>

                        <option
                            value="occupé"
                            <?php

                            if($current_status == 'occupé')
                            {
                                echo 'selected="selected"';
                            }

                            ?>
                        >
                            Occupé
                        </option>

                        <option
                            value="hors_ligne"
                            <?php

                            if($current_status == 'hors_ligne')
                            {
                                echo 'selected="selected"';
                            }

                            ?>
                        >
                            Hors ligne
                        </option>

                    </select>

                    <label>
                        Statut de disponibilité
                    </label>

                </div>

                <div class="col s12">

                    <button
                        type="submit"
                        name="update_availability"
                        class="btn-large blue">

                        <i class="material-icons left">
                            save
                        </i>

                        Mettre à jour

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


</div>





<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>


<script>

document.addEventListener('DOMContentLoaded', function() {

    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

});

</script>

</body>
</html>