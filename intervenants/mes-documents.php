<?php

session_start();

require_once("../config/database.php");

$user_id = $_SESSION['user_id'];

/* RECUPERATION DU CANDIDAT */

$sql = "SELECT id FROM candidates WHERE user_id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$candidate = mysqli_fetch_assoc($result);

if(!$candidate){

    die("Profil candidat introuvable.");

}

$candidate_id = (int)$candidate['id'];

/* STATISTIQUES */

$sql = "
SELECT
COUNT(*) AS total_documents,
SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) AS documents_valides,
SUM(CASE WHEN verified = 0 THEN 1 ELSE 0 END) AS documents_attente
FROM candidate_documents
WHERE candidate_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $candidate_id);

mysqli_stmt_execute($stmt);

$stats = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

/* LISTE DOCUMENTS */

$sql = "
SELECT *
FROM candidate_documents
WHERE candidate_id = ?
ORDER BY uploaded_at DESC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $candidate_id);

mysqli_stmt_execute($stmt);

$documents = mysqli_stmt_get_result($stmt);

?>

<?php if(isset($_SESSION['success'])): ?>

<script>

document.addEventListener('DOMContentLoaded', function(){

    M.toast({

        html: '<?php echo addslashes($_SESSION['success']); ?>',

        classes: 'green'

    });

});

</script>

<?php unset($_SESSION['success']); ?>

<?php endif; ?>


<?php if(isset($_SESSION['error'])): ?>

<script>

document.addEventListener('DOMContentLoaded', function(){

    M.toast({

        html: '<?php echo addslashes($_SESSION['error']); ?>',

        classes: 'red'

    });

});

</script>

<?php unset($_SESSION['error']); ?>

<?php endif; ?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        Mes documents | INFINITIA

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

    $current_page = "documents";

    include("menuin.php");

    ?>

    <div class="main-content">

        <!-- TOPBAR -->

        <div class="topbar">

            <div>

                <div class="page-title">

                   Mes documents

                </div>

                <div class="welcome-text">

                   
                </div>

            </div>

        </div>



        <div class="row">

    <div class="col s12 m4">

        <div class="card-panel blue white-text">

            <h5>
                <?php echo (int)$stats['total_documents']; ?>
            </h5>

            <p>Documents téléversés</p>

        </div>

    </div>

    <div class="col s12 m4">

        <div class="card-panel green white-text">

            <h5>
                <?php echo (int)$stats['documents_valides']; ?>
            </h5>

            <p>Documents validés</p>

        </div>

    </div>

    <div class="col s12 m4">

        <div class="card-panel orange white-text">

            <h5>
                <?php echo (int)$stats['documents_attente']; ?>
            </h5>

            <p>En attente</p>

        </div>

    </div>

</div>

<div class="table-card">

    <div class="table-title">

        Liste des documents

        <a href="#modalDocument"
           class="btn right modal-trigger">

            <i class="material-icons left">
                add
            </i>

            Ajouter

        </a>

    </div>

    <table class="highlight responsive-table">

        <thead>

            <tr>

                <th>Type</th>

                <th>Date d'ajout</th>

                <th>Statut</th>

                <th>Action</th>

            </tr>

        </thead>

        <tbody>

        <?php while($doc = mysqli_fetch_assoc($documents)): ?>

            <tr>

                <td>

                    <?php echo htmlspecialchars($doc['document_type']); ?>

                </td>

                <td>

                    <?php echo date(
                        "d/m/Y",
                        strtotime($doc['uploaded_at'])
                    ); ?>

                </td>

                <td>

                    <?php if($doc['verified'] == 1): ?>

                        <span class="new badge green"
                              data-badge-caption="Validé"></span>

                    <?php else: ?>

                        <span class="new badge orange"
                              data-badge-caption="En attente"></span>

                    <?php endif; ?>

                </td>

            <td>

    <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>"
       target="_blank"
       class="btn-small blue">

        <i class="material-icons">
            visibility
        </i>

    </a>

    <a href="delete-document.php?id=<?php echo $doc['id']; ?>"
       class="btn-small red"
       onclick="return confirm('Voulez-vous vraiment supprimer ce document ?');">

        <i class="material-icons">
            delete
        </i>

    </a>

</td>

            </tr>

        <?php endwhile; ?>

        </tbody>

    </table>

</div>

        
       

        

    </div>

</div>


<div id="modalDocument" class="modal">

    <div class="modal-content" >

        <h4>Ajouter un document</h4>

        <form
        action="upload-document.php"
        method="POST"
        enctype="multipart/form-data">

            <div class="input-field">

                <select
                name="document_type"
                required>

                    <option value="" disabled selected>

                        Choisir

                    </option>

                    <option value="Carte d’identité">
                        Carte d’identité
                    </option>

                    <option value="CV">
                        CV
                    </option>

                    <option value="Certificat médical">
                        Certificat médical
                    </option>

                    <option value="Photo d’identité">
                        Photo d’identité
                    </option>

                    <option value="Casier judiciaire">
                        Casier judiciaire
                    </option>

                    <option value="Diplôme">
                        Diplôme
                    </option>

                    <option value="Lettre de recommandation">
                        Lettre de recommandation
                    </option>

                    <option value="Attestation de formation">
                        Attestation de formation
                    </option>

                    <option value="Permis de conduire">
                        Permis de conduire
                    </option>

                    <option value="Autre">
                        Autre
                    </option>

                </select>

                <label>Type de document</label>

            </div>

            <div class="file-field input-field">

                <div class="btn">

                    <span>Fichier</span>

                    <input
                    type="file"
                    name="document"
                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                    required>

                </div>

                <div class="file-path-wrapper">

                    <input
                    class="file-path validate"
                    type="text">

                </div>

            </div>

            <p class="grey-text text-darken-1" style="margin-top:-5px;margin-bottom:20px;">
                Formats acceptés : PDF, JPG, PNG, DOC et DOCX — taille maximale : 10 Mo.
            </p>

            <button
            type="submit"
            class="btn green">

                Enregistrer

            </button>

        </form>

    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function() {

    M.Modal.init(
        document.querySelectorAll('.modal')
    );

    M.FormSelect.init(
        document.querySelectorAll('select')
    );

});

</script>

</body>
</html>
