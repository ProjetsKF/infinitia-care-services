<?php

session_start();

$success_message = "";
$error_message = "";

if(isset($_SESSION['success'])){

    $success_message = $_SESSION['success'];

    unset($_SESSION['success']);

}

if(isset($_SESSION['error'])){

    $error_message = $_SESSION['error'];

    unset($_SESSION['error']);

}

require_once("../config/database.php");

if(!isset($_SESSION["user_id"])){

    header("Location: " . app_url("login"));
    exit();

}

/* ==========================
   RECUPERATION DES DEMANDES
   AVEC PAGINATION
========================== */

$user_id = (int)$_SESSION["user_id"];
$client_id = 0;
/**
 * @var array<int,array{id:int,name:string}> $service_categories
 */
$service_categories = array();

if(!isset($_SESSION["service_request_csrf"]) || $_SESSION["service_request_csrf"] == ""){

    if(function_exists("openssl_random_pseudo_bytes")){

        $_SESSION["service_request_csrf"] = bin2hex(openssl_random_pseudo_bytes(32));

    }else{

        $_SESSION["service_request_csrf"] = sha1(uniqid(mt_rand(), true));

    }

}

$service_request_csrf = $_SESSION["service_request_csrf"];

$sqlClient = "
    SELECT id
    FROM clients
    WHERE user_id = ?
    LIMIT 1
";

$stmtClient = mysqli_prepare($conn, $sqlClient);

if(!$stmtClient){

    die("Erreur SQL client : " . mysqli_error($conn));

}

mysqli_stmt_bind_param(
    $stmtClient,
    "i",
    $user_id
);

mysqli_stmt_execute($stmtClient);

mysqli_stmt_bind_result(
    $stmtClient,
    $client_id
);

mysqli_stmt_fetch($stmtClient);

mysqli_stmt_close($stmtClient);

if($client_id <= 0){

    header("Location: " . app_url("login"));
    exit();

}

$sqlCategories = "
    SELECT id, name
    FROM service_categories
    ORDER BY name ASC
";

$stmtCategories = mysqli_prepare($conn, $sqlCategories);

if($stmtCategories){

    mysqli_stmt_execute($stmtCategories);
    mysqli_stmt_bind_result($stmtCategories, $category_id_result, $category_name_result);

    while(mysqli_stmt_fetch($stmtCategories)){

        $service_categories[] = array(
            'id' => $category_id_result,
            'name' => $category_name_result
        );

    }

    mysqli_stmt_close($stmtCategories);

}

/* Nombre de demandes par page */

$limite = 10;

/* Page courante */

$page = isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;

if($page < 1){

    $page = 1;

}

/* Calcul offset */

$offset = ($page - 1) * $limite;

/* ==========================
   TOTAL DES DEMANDES
========================== */

$sqlCount = "SELECT COUNT(*) AS total
             FROM service_requests
             WHERE client_id = ?";

$stmtCount = mysqli_prepare($conn, $sqlCount);

if(!$stmtCount){

    die("Erreur SQL comptage : " . mysqli_error($conn));

}

mysqli_stmt_bind_param(
    $stmtCount,
    "i",
    $client_id
);

mysqli_stmt_execute($stmtCount);

$resultCount = mysqli_stmt_get_result($stmtCount);

$totalRows = mysqli_fetch_assoc($resultCount)['total'];

$totalPages = ceil($totalRows / $limite);

mysqli_stmt_close($stmtCount);

/* ==========================
   RECUPERATION DES DEMANDES
========================== */

$sql = "SELECT *
        FROM service_requests
        WHERE client_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL demandes : " . mysqli_error($conn));

}

mysqli_stmt_bind_param(
    $stmt,
    "iii",
    $client_id,
    $limite,
    $offset
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>
    Mes demandes | INFINITIA
</title>


    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <!-- MATERIALIZE -->

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

    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

    <!-- ICON -->

    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">


</head>

<body>

    <div class="dashboard">
    <?php

    $current_page = "demandes";

    include("menucli.php");

    ?>

        <!-- =========================================
             MAIN CONTENT
        ========================================= -->

        <div class="main-content">

            <!-- TOPBAR -->

            <div class="topbar">

                <div class="page-title">

                            <i class="material-icons left" style="vertical-align: middle;">
                                assignment
                            </i>
                                Demandes

                        </div>

                        <div class="welcome-text">

                            Consultez et gérez toutes vos demandes de services.

                        </div>

            </div>

           <!-- STATS -->

            <!-- =========================================
             MAIN CONTENT
      

<div class="row">

    <div class="col s12 m6 l3">

        <div class="dashboard-card">

            <div class="card-icon blue-gradient">

                <i class="material-icons">
                    assignment
                </i>

            </div>

            <h5>Total demandes</h5>

            <h3>0</h3>

        </div>

    </div>

    <div class="col s12 m6 l3">

        <div class="dashboard-card">

            <div class="card-icon gold-gradient">

                <i class="material-icons">
                    schedule
                </i>

            </div>

            <h5>En attente</h5>

            <h3>0</h3>

        </div>

    </div>

    <div class="col s12 m6 l3">

        <div class="dashboard-card">

            <div class="card-icon pink-gradient">

                <i class="material-icons">
                    engineering
                </i>

            </div>

            <h5>En cours</h5>

            <h3>0</h3>

        </div>

    </div>

    <div class="col s12 m6 l3">

        <div class="dashboard-card">

            <div class="card-icon blue-gradient">

                <i class="material-icons">
                    check_circle
                </i>

            </div>

            <h5>Terminées</h5>

            <h3>0</h3>

        </div>

    </div>

</div>
 ========================================= -->

            <!-- TABLE -->

           <div class="table-card">

    <div class="table-header">

        <div class="table-title">
            Mes demandes de services
        </div>

        <a href="#modalDemande"
           class="btn modal-trigger waves-effect waves-light new-request-btn">

            <i class="material-icons left">add</i>

            Nouvelle demande

        </a>

    </div>

            <!-- MESSAGES -->

                    <?php if(isset($_SESSION['success'])): ?>

                        <div class="card-panel green white-text">

                            <?= $_SESSION['success']; ?>

                        </div>

                        <?php unset($_SESSION['success']); ?>

                        <?php endif; ?>


                        <?php if(isset($_SESSION['error'])): ?>

                        <div class="card-panel red white-text">

                            <?= $_SESSION['error']; ?>

                        </div>

                        <?php unset($_SESSION['error']); ?>

                    <?php endif; ?>




                    <div class="table-tools" style="
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    gap:20px;
    flex-wrap:wrap;
">

    <div style="flex:1; min-width:300px;">

        <div class="input-field" style="margin:0;">

            <i class="material-icons prefix">search</i>

            <input
                type="text"
                id="searchDemandes"
                placeholder="Rechercher un service, un lieu, un statut...">

        </div>

    </div>

    <div style="min-width:220px;">

       <select id="filterUrgence">

            <option value="">Toutes les urgences</option>

            <option value="Low">Faible</option>

            <option value="Medium">Moyenne</option>

            <option value="High">Élevée</option>

        </select>

    </div>

</div>

    <table class="highlight responsive-table">

        <thead>
            <tr>
                <th>ID</th>
                <th>Service</th>
                <th>Description</th>
                <th>Lieu</th>
                <th>Durée</th>
                <th>Date prévue</th>
                <th>Budget</th>
                <th>Urgence</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php if(mysqli_num_rows($result) > 0): ?>

            <?php while($row = mysqli_fetch_assoc($result)): ?>

            <tr>

                <td><?= str_pad($row['id'], 2, '0', STR_PAD_LEFT); ?></td>

                <td><?= htmlspecialchars($row['title']); ?></td>

                <td><?= htmlspecialchars($row['description']); ?></td>

                <td><?= htmlspecialchars($row['location']); ?></td>

                <td><?= $row['duration']; ?> h</td>

                <td><?= date('d/m/Y', strtotime($row['service_date'])); ?></td>

                <td><?= number_format($row['budget'], 2); ?> USD</td>

                <td><?= ucfirst($row['urgency_level']); ?></td>

                <td>

                    <?php

                    $statusClass = "pending";

                    if($row['status'] == "en_cours"){
                        $statusClass = "progress";
                    }

                    if($row['status'] == "terminee"){
                        $statusClass = "completed";
                    }

                    ?>

                    <span class="status <?= $statusClass; ?>">
                        <?= ucfirst(str_replace('_', ' ', $row['status'])); ?>
                    </span>

                </td>

                <td>

                    <!-- Voir -->

                    <a href="#details<?= $row['id']; ?>"
                       class="modal-trigger green-text"
                       title="Voir">

                        <i class="material-icons">visibility</i>

                    </a>

                    &nbsp;&nbsp;

                    <!-- Modifier -->

                    <a href="#editModal<?= $row['id']; ?>"
                       class="modal-trigger blue-text"
                       title="Modifier">

                        <i class="material-icons">edit</i>

                    </a>

                    &nbsp;&nbsp;

                    <!-- Supprimer -->

                    <a href="<?php echo app_url_with_query_html("client/demande/supprimer", array("id" => (int)$row["id"])); ?>"
                       class="red-text"
                       title="Supprimer"
                       onclick="return confirm('Voulez-vous vraiment supprimer cette demande ?');">

                        <i class="material-icons">delete</i>

                    </a>

                </td>

            </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>

                <td colspan="10" style="text-align:center;">

                    Aucune demande trouvée.

                </td>

            </tr>

        <?php endif; ?>

        </tbody>

    </table>

    <div class="center" style="margin-top:25px;">

    <ul class="pagination">

        <li class="<?= ($page <= 1) ? 'disabled' : 'waves-effect'; ?>">

            <a href="?page=<?= $page - 1; ?>">

                <i class="material-icons">
                    chevron_left
                </i>

            </a>

        </li>

        <?php for($i = 1; $i <= $totalPages; $i++): ?>

            <li class="<?= ($i == $page) ? 'active' : 'waves-effect'; ?>">

                <a href="?page=<?= $i; ?>">

                    <?= $i; ?>

                </a>

            </li>

        <?php endfor; ?>

        <li class="<?= ($page >= $totalPages) ? 'disabled' : 'waves-effect'; ?>">

            <a href="?page=<?= $page + 1; ?>">

                <i class="material-icons">
                    chevron_right
                </i>

            </a>

        </li>

    </ul>

</div>

</div>

<!-- MODAL TEST -->

<?php
mysqli_data_seek($result, 0);

while($modal = mysqli_fetch_assoc($result)):
?>

<!-- MODAL D'AFFICHAGE -->

<div id="details<?= $modal['id']; ?>" class="modal modal-fixed-footer">

    <div class="modal-content">

        <div class="card-panel purple white-text">

            <h4 style="margin:0;">
                Demande N°<?= str_pad($modal['id'], 2, '0', STR_PAD_LEFT); ?>
            </h4>

            <p style="margin-top:10px;">
                <?= htmlspecialchars($modal['title']); ?>
            </p>

        </div>

        <div class="row">

            <div class="col s12 m6">

                <div class="card-panel">

                    <strong>Description</strong>

                    <p><?= htmlspecialchars($modal['description']); ?></p>

                </div>

            </div>

            <div class="col s12 m6">

                <div class="card-panel">

                    <strong>Lieu d'intervention</strong>

                    <p><?= htmlspecialchars($modal['location']); ?></p>

                </div>

            </div>

            <div class="col s12 m4">

                <div class="card-panel center">

                    <i class="material-icons medium blue-text">
                        event
                    </i>

                    <p>Date prévue</p>

                    <strong>
                        <?= date('d/m/Y', strtotime($modal['service_date'])); ?>
                    </strong>

                </div>

            </div>

            <div class="col s12 m4">

                <div class="card-panel center">

                    <i class="material-icons medium orange-text">
                        schedule
                    </i>

                    <p>Durée</p>

                    <strong>
                        <?= $modal['duration']; ?> h
                    </strong>

                </div>

            </div>

            <div class="col s12 m4">

                <div class="card-panel center">

                    <i class="material-icons medium green-text">
                        payments
                    </i>

                    <p>Budget</p>

                    <strong>
                        <?= number_format($modal['budget'], 2); ?> USD
                    </strong>

                </div>

            </div>

        </div>

        <div class="row">

            <div class="col s12 m6">

                <h6>Urgence</h6>

                <span class="new badge orange"
                      data-badge-caption="">
                    <?= ucfirst($modal['urgency_level']); ?>
                </span>

            </div>

            <div class="col s12 m6">

                <h6>Statut</h6>

                <span class="new badge green"
                      data-badge-caption="">
                    <?= ucfirst(str_replace('_', ' ', $modal['status'])); ?>
                </span>

            </div>

        </div>

    </div>

    <div class="modal-footer">

        <a href="<?php echo app_url_with_query_html("client/demande/modifier", array("id" => (int)$modal["id"])); ?>"
           class="btn blue">

            Modifier

        </a>

        <a href="#!"
           class="modal-close btn grey">

            Fermer

        </a>

    </div>

</div>


<!-- MODAL DE MODIFICATION -->

<div id="editModal<?= $modal['id']; ?>" class="modal modal-fixed-footer">

    <form action="<?php echo app_url_html("client/demande/modifier"); ?>" method="POST">

        <input type="hidden"
               name="id"
               value="<?= $modal['id']; ?>">

        <div class="edit-modal-header">

            <h5>
                Modifier la demande #<?= str_pad($modal['id'], 2, '0', STR_PAD_LEFT); ?>
            </h5>

            <p>
                Mettez à jour les informations de votre demande de service.
            </p>

        </div>

        <div class="modal-content">

            <div class="edit-section">

                <h6>
                    <i class="material-icons left">home_repair_service</i>
                    Informations générales
                </h6>

                <div class="input-field">

                    <input
                        type="text"
                        name="title"
                        id="title<?= $modal['id']; ?>"
                        value="<?= htmlspecialchars($modal['title']); ?>"
                        required>

                    <label class="active"
                           for="title<?= $modal['id']; ?>">
                        Service
                    </label>

                </div>

                <div class="input-field">

                    <textarea
                        name="description"
                        id="description<?= $modal['id']; ?>"
                        class="materialize-textarea"><?= htmlspecialchars($modal['description']); ?></textarea>

                    <label class="active"
                           for="description<?= $modal['id']; ?>">
                        Description
                    </label>

                </div>

                <div class="input-field">

                    <input
                        type="text"
                        name="location"
                        id="location<?= $modal['id']; ?>"
                        value="<?= htmlspecialchars($modal['location']); ?>"
                        required>

                    <label class="active"
                           for="location<?= $modal['id']; ?>">
                        Lieu d'intervention
                    </label>

                </div>

            </div>

            <div class="edit-section">

                <h6>
                    <i class="material-icons left">event</i>
                    Planification
                </h6>

                <div class="row">

                    <div class="input-field col s12 m6">

                        <input
                            type="date"
                            name="service_date"
                            value="<?= $modal['service_date']; ?>"
                            required>

                        <label class="active">
                            Date prévue
                        </label>

                    </div>

                    <div class="input-field col s12 m6">

                        <input
                            type="number"
                            name="duration"
                            value="<?= $modal['duration']; ?>"
                            min="1"
                            required>

                        <label class="active">
                            Durée (heures)
                        </label>

                    </div>

                </div>

            </div>

            <div class="edit-section">

                <h6>
                    <i class="material-icons left">payments</i>
                    Budget et urgence
                </h6>

                <div class="row">

                    <div class="input-field col s12 m6">

                        <input
                            type="number"
                            step="0.01"
                            name="budget"
                            value="<?= $modal['budget']; ?>"
                            required>

                        <label class="active">
                            Budget (USD)
                        </label>

                    </div>

                    <div class="input-field col s12 m6">

                        <select name="urgency_level">

                            <option value="low"
                                <?= $modal['urgency_level']=='low'?'selected':''; ?>>
                                Faible
                            </option>

                            <option value="medium"
                                <?= $modal['urgency_level']=='medium'?'selected':''; ?>>
                                Moyenne
                            </option>

                            <option value="high"
                                <?= $modal['urgency_level']=='high'?'selected':''; ?>>
                                Élevée
                            </option>

                        </select>

                        <label>Niveau d'urgence</label>

                    </div>

                </div>

            </div>

        </div>

        <div class="modal-footer">

            <a href="#!"
               class="modal-close btn-flat">

                Annuler

            </a>

            <button
                type="submit"
                class="btn purple">

                <i class="material-icons left">save</i>

                Enregistrer

            </button>

        </div>

    </form>

</div>

<?php endwhile; ?>

<?php
mysqli_free_result($result);
mysqli_stmt_close($stmt);
?>

        </div>

    </div>



<!-- MODAL D'AJOUT DE LA REQUETTE -->

    <div id="modalDemande" class="modal">

    <div class="modal-content">

        <h4 class="page-title">Nouvelle demande de service</h4>

        <form action="<?php echo app_url_html("client/demande/enregistrer"); ?>" method="POST">

            <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($service_request_csrf, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- CATEGORIE -->

            <div class="input-field col s12" >

                <select name="category_id" required>

                    <option value="" disabled selected>

                        Choisir une catégorie

                    </option>

                    <?php
                    /** @var array{id:int,name:string} $categorie */
                    foreach($service_categories as $categorie){
                    ?>

                        <option value="<?php echo (int)$categorie['id']; ?>">
                            <?php echo htmlspecialchars($categorie['name'], ENT_QUOTES, "UTF-8"); ?>
                        </option>

                    <?php } ?>

                </select>

                <label>Catégorie</label>

            </div>

            <!-- TITRE -->

            <div class="input-field col s12">

                <input
                type="text"
                name="title"
                required>

                <label>

                    Titre de la demande

                </label>

            </div>

            <!-- DESCRIPTION -->

            <div class="input-field col s12">

                <textarea
                name="description"
                class="materialize-textarea"
                required></textarea>

                <label>

                    Description

                </label>

            </div>

            <!-- LOCALISATION -->

            <div class="input-field col s12">

                <textarea
                name="location"
                class="materialize-textarea"
                required></textarea>

                <label>

                    Lieu d'intervention

                </label>

            </div>

            <!-- DATE -->

            <div class="input-field col s12">

                <input
                type="date"
                name="service_date"
                required>

            </div>

            <!-- DUREE -->

            <div class="input-field col s12">

                <input
                type="number"
                name="duration"
                min="1"
                required>

                <label>

                    Durée estimée (heures)

                </label>

            </div>

            <!-- BUDGET -->

            <div class="input-field col s12">

                <input
                type="number"
                step="0.01"
                name="budget"
                required>

                <label>

                    Budget proposé

                </label>

            </div>

            <!-- URGENCE -->

            <div class="input-field col s12">

                <select
                name="urgency_level"
                required>

                    <option value="low">

                        Faible

                    </option>

                    <option value="medium"
                    selected>

                        Moyenne

                    </option>

                    <option value="high">

                        Élevée

                    </option>

                </select>

                <label>

                    Niveau d'urgence

                </label>

            </div>

            <div class="modal-footer">

                <a href="#!"
                class="modal-close btn-flat">

                    Annuler

                </a>

               <button type="submit" class="btn waves-effect waves-light new-request-btn">

                <i class="material-icons left">

                    save

                </i>

                Enregistrer

            </button>

            </div>

        </form>

    </div>

</div>

<?php if(!empty($success_message)): ?>

<div id="successModal" class="modal">

    <div class="modal-content center">

        <i class="material-icons green-text"
        style="font-size:70px;">

            check_circle

        </i>

        <h5>Succès</h5>

        <p>

            <?php echo $success_message; ?>

        </p>

    </div>

    <div class="modal-footer">

        <a href="#!"
        class="modal-close btn waves-effect waves-light new-request-btn">

            Fermer

        </a>

    </div>

</div>

<?php endif; ?>

<?php if(!empty($error_message)): ?>

<div id="errorModal" class="modal">

    <div class="modal-content center">

        <i class="material-icons red-text"
        style="font-size:70px;">

            error_outline

        </i>

        <h5>Erreur</h5>

        <p>

            <?php echo htmlspecialchars($error_message, ENT_QUOTES, "UTF-8"); ?>

        </p>

    </div>

    <div class="modal-footer">

        <a href="#!"
        class="modal-close btn red">

            Fermer

        </a>

    </div>

</div>

<?php endif; ?>
    <!-- MATERIALIZE JS -->

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

   <script>

document.addEventListener('DOMContentLoaded', function() {

    var modals =
    document.querySelectorAll('.modal');

    M.Modal.init(modals);

    <?php if(!empty($success_message)): ?>

    var instance = M.Modal.getInstance(
        document.getElementById('successModal')
    );

    instance.open();

    <?php endif; ?>

    <?php if(!empty($error_message)): ?>

    var errorInstance = M.Modal.getInstance(
        document.getElementById('errorModal')
    );

    errorInstance.open();

    <?php endif; ?>

});

</script>

<script>

document.getElementById('searchDemandes')
.addEventListener('keyup', function() {

    let value = this.value.toLowerCase();

    let rows =
    document.querySelectorAll('tbody tr');

    rows.forEach(function(row) {

        let text =
        row.innerText.toLowerCase();

        row.style.display =
        text.includes(value)
        ? ''
        : 'none';

    });

});

document.getElementById('filterUrgence')
.addEventListener('change', function() {

    let urgence = this.value.toLowerCase();

    let rows = document.querySelectorAll('tbody tr');

    rows.forEach(function(row) {

        let celluleUrgence = row.cells[7];

        if(!celluleUrgence) return;

        let texteUrgence =
        celluleUrgence.innerText.trim().toLowerCase();

        let correspondance = false;

        if(urgence === '') {

            correspondance = true;

        } else if(
            urgence === 'low' &&
            (texteUrgence.includes('faible') || texteUrgence.includes('low'))
        ) {

            correspondance = true;

        } else if(
            urgence === 'medium' &&
            (texteUrgence.includes('moyenne') || texteUrgence.includes('medium'))
        ) {

            correspondance = true;

        } else if(
            urgence === 'high' &&
            (texteUrgence.includes('élevée') || texteUrgence.includes('elevee') || texteUrgence.includes('high'))
        ) {

            correspondance = true;

        }

        row.style.display = correspondance ? '' : 'none';

    });

});

</script>

</body>

</html>
