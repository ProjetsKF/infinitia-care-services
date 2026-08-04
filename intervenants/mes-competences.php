<?php

session_start();

require_once("../config/database.php");

$user_id = $_SESSION['user_id'];

/* RECUPERATION DU CANDIDAT */

$sql = "
SELECT id
FROM candidates
WHERE user_id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$candidate = mysqli_fetch_assoc($result);

$candidate_id = $candidate['id'];

/* STATISTIQUES */

$sql = "
SELECT
COUNT(*) AS total_competences,
SUM(years_experience) AS total_experience
FROM candidate_skills
WHERE candidate_id = ?
AND is_active = 1
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $candidate_id);

mysqli_stmt_execute($stmt);

$stats = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

/* LISTE DES COMPETENCES */

$sql = "
SELECT *
FROM candidate_skills
WHERE candidate_id = ?
AND is_active = 1
ORDER BY skill_name ASC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $candidate_id);

mysqli_stmt_execute($stmt);

$competences = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(dirname(__DIR__) . "/includes/pwa-head.php"); ?>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        Mes compétences | INFINITIA

    </title>

    <link rel="icon"
          type="image/x-icon"
          href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="<?php echo app_url_html("assets/css/style.css"); ?>">

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "competences";

    include("menuin.php");

    ?>

 <div class="main-content">

    <div class="topbar">

        <div class="page-title">

            Mes compétences

        </div>

    </div>

    <?php if(isset($_SESSION['success'])): ?>

    <script>

    document.addEventListener('DOMContentLoaded', function(){

        M.toast({

            html:'<?php echo $_SESSION['success']; ?>',
            classes:'green'

        });

    });

    </script>

    <?php unset($_SESSION['success']); ?>

    <?php endif; ?>


    <?php if(isset($_SESSION['error'])): ?>

    <script>

    document.addEventListener('DOMContentLoaded', function(){

        M.toast({

            html:'<?php echo $_SESSION['error']; ?>',
            classes:'red'

        });

    });

    </script>

    <?php unset($_SESSION['error']); ?>

    <?php endif; ?>


    <div class="row">

        <div class="col s12 m6">

            <div class="card blue darken-3 white-text">

                <div class="card-content">

                    <span class="card-title">

                        Compétences enregistrées

                    </span>

                    <h3>

                        <?php echo (int)$stats['total_competences']; ?>

                    </h3>

                </div>

            </div>

        </div>

        <div class="col s12 m6">

            <div class="card green darken-2 white-text">

                <div class="card-content">

                    <span class="card-title">

                        Expérience cumulée

                    </span>

                    <h3>

                        <?php echo (int)$stats['total_experience']; ?> ans

                    </h3>

                </div>

            </div>

        </div>

    </div>

    <div class="table-card">

        <div style="margin-bottom:20px;">

            <a href="#modalAddSkill" class="btn-large teal modal-trigger">

                <i class="material-icons left">

                    add

                </i>

                Ajouter une compétence

            </a>

        </div>

        <div class="table-title">

            Liste des compétences

        </div>

        <table class="highlight responsive-table">

            <thead>

                <tr>

                    <th>Compétence</th>

                    <th>Niveau</th>

                    <th>Expérience</th>

                    <th>Description</th>

                    <th>Actions</th>

                </tr>

            </thead>

            <tbody>

            <?php while($skill = mysqli_fetch_assoc($competences)): ?>

                <tr>

                    <td>

                        <?php echo htmlspecialchars($skill['skill_name']); ?>

                    </td>

                    <td>

                        <?php echo htmlspecialchars($skill['level']); ?>

                    </td>

                    <td>

                        <?php echo (int)$skill['years_experience']; ?> ans

                    </td>

                    <td>

                        <?php echo htmlspecialchars($skill['description']); ?>

                    </td>

                 <td>

                    <a
                        href="#modalEditSkill"
                        class="btn-small orange modal-trigger edit-skill-btn"
                        data-id="<?php echo $skill['id']; ?>"
                        data-level="<?php echo htmlspecialchars($skill['level']); ?>"
                        data-years="<?php echo (int)$skill['years_experience']; ?>"
                        data-description="<?php echo htmlspecialchars($skill['description']); ?>"
                        title="Modifier">

                        <i class="material-icons">edit</i>

                    </a>

                    <a
                        href="<?php echo app_url_with_query_html("intervenant/competence/supprimer", array("id" => (int)$skill["id"])); ?>"
                        class="btn-small red"
                        title="Supprimer"
                        onclick="return confirm('Voulez-vous vraiment supprimer cette compétence ?');">

                        <i class="material-icons">delete</i>

                    </a>

                </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</div>

</div>



<div id="modalAddSkill" class="modal">
                        <div style="
                                background:linear-gradient(90deg,#1b2d8f,#e63b88);
                                 padding:28px 40px;
                                border-radius:18px 18px 0 0;">

                                <h4 style="
                                    margin:0;
                                    color:#fff;
                                    font-size:38px;
                                    font-weight:700;
                                ">
                                 Ajouter des compétences
                                </h4>

                            </div>

    <div class="modal-content" style="font-size:17px;line-height:1.9;color:#555;text-align:justify;">

      

        <form
            action="<?php echo app_url_html("intervenant/competence/ajouter"); ?>"
            method="POST">

            <h6 style="margin-bottom:20px;">
                Sélectionnez vos compétences
            </h6>

            <div class="row">

                <div class="col s12 m6">

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Entretien ménager" />
                            <span>Entretien ménager</span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Cuisine" />
                            <span>Cuisine</span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Repassage" />
                            <span>Repassage</span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Lessive" />
                            <span>Lessive</span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Garde d'enfants" />
                            <span>Garde d'enfants</span>
                        </label>
                    </p>

                </div>

                <div class="col s12 m6">

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Assistance aux personnes âgées" />
                            <span>Assistance aux personnes âgées</span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Nettoyage de bureaux" />
                            <span>Nettoyage de bureaux</span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Jardinage" />
                            <span>Jardinage</span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Chauffeur" />
                            <span>Chauffeur</span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="skills[]" value="Sécurité domestique" />
                            <span>Sécurité domestique</span>
                        </label>
                    </p>

                </div>

            </div>

            <div class="input-field">

                <select name="level" required>

                    <option value="" disabled selected>
                        Choisir le niveau
                    </option>

                    <option value="Débutant">
                        Débutant
                    </option>

                    <option value="Intermédiaire">
                        Intermédiaire
                    </option>

                    <option value="Avancé">
                        Avancé
                    </option>

                    <option value="Expert">
                        Expert
                    </option>

                </select>

                <label>
                    Niveau de maîtrise
                </label>

            </div>

            <div class="input-field">

                <input
                    type="number"
                    name="years_experience"
                    min="0"
                    value="0"
                    required>

                <label class="active">
                    Années d'expérience
                </label>

            </div>

            <div class="input-field">

                <textarea
                    name="description"
                    id="description"
                    class="materialize-textarea"></textarea>

                <label for="description">
                    Description complémentaire
                </label>

            </div>

            <div style="margin-top:25px;">

                <button
                    type="submit"
                    class="btn-large teal">

                    <i class="material-icons left">
                        save
                    </i>

                    Enregistrer

                </button>

            </div>

        </form>

    </div>

</div>


<div id="modalEditSkill" class="modal">

                                <div style="
                                background:linear-gradient(90deg,#1b2d8f,#e63b88);
                                padding:28px 40px;
                                border-radius:18px 18px 0 0;">

                                <h4 style="
                                    margin:0;
                                    color:#fff;
                                    font-size:38px;
                                    font-weight:700;
                                ">
                                 Modifier la compétence 
                                </h4>

                            </div>

    <div class="modal-content" style="font-size:17px;line-height:1.9;color:#555;text-align:justify;">

    
        <form
            action="<?php echo app_url_html("intervenant/competence/modifier"); ?>"
            method="POST">

            <input
                type="hidden"
                name="skill_id"
                id="edit_skill_id">

            <div class="input-field">

                <select
                    name="level"
                    id="edit_level"
                    required>

                    <option value="Débutant">
                        Débutant
                    </option>

                    <option value="Intermédiaire">
                        Intermédiaire
                    </option>

                    <option value="Avancé">
                        Avancé
                    </option>

                    <option value="Expert">
                        Expert
                    </option>

                </select>

                <label>
                    Niveau de maîtrise
                </label>

            </div>

            <div class="input-field">

                <input
                    type="number"
                    name="years_experience"
                    id="edit_years_experience"
                    min="0"
                    required>

                <label class="active">
                    Années d'expérience
                </label>

            </div>

            <div class="input-field">

                <textarea
                    name="description"
                    id="edit_description"
                    class="materialize-textarea"></textarea>

                <label class="active">
                    Description complémentaire
                </label>

            </div>

            <div style="margin-top:25px;">

                <button
                    type="submit"
                    class="btn-large orange">

                    <i class="material-icons left">
                        save
                    </i>

                    Mettre à jour

                </button>

            </div>

        </form>

    </div>

</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function(){

    var modals =
    document.querySelectorAll('.modal');

    M.Modal.init(modals);

    var selects =
    document.querySelectorAll('select');

    M.FormSelect.init(selects);

});

</script>

<script>

document.addEventListener('DOMContentLoaded', function() {

    var elems = document.querySelectorAll('.edit-skill-btn');

    for(var i = 0; i < elems.length; i++)
    {
        elems[i].addEventListener('click', function() {

            document.getElementById('edit_skill_id').value =
                this.getAttribute('data-id');

            document.getElementById('edit_years_experience').value =
                this.getAttribute('data-years');

            document.getElementById('edit_description').value =
                this.getAttribute('data-description');

            document.getElementById('edit_level').value =
                this.getAttribute('data-level');

            M.FormSelect.init(
                document.querySelectorAll('select')
            );

            M.updateTextFields();

        });
    }

});

</script>
</body>
</html>
