<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1){

    header("Location: ../login.php");
    exit();

}

function safe_text($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function display_value($value)
{
    if($value === NULL || $value === ""){

        return "Non renseigne";

    }

    return (string)$value;
}

function format_date_fr($value)
{
    if($value === NULL || $value === ""){

        return "Non renseigne";

    }

    $timestamp = strtotime($value);

    if($timestamp === false){

        return "Non renseigne";

    }

    return date("d/m/Y H:i", $timestamp);
}

function redirect_categories()
{
    header("Location: categories-services.php");
    exit();
}

function count_query($conn, $sql)
{
    $total = 0;
    $result = mysqli_query($conn, $sql);

    if($result){

        $row = mysqli_fetch_assoc($result);

        if($row && isset($row["total"])){

            $total = (int)$row["total"];

        }

        mysqli_free_result($result);

    }

    return $total;
}

$icon_options = array(
    array("icon" => "cleaning_services", "label" => "Menage"),
    array("icon" => "restaurant", "label" => "Cuisine"),
    array("icon" => "child_care", "label" => "Garde d'enfants"),
    array("icon" => "yard", "label" => "Jardinage"),
    array("icon" => "checkroom", "label" => "Repassage"),
    array("icon" => "business_center", "label" => "Bureau"),
    array("icon" => "local_shipping", "label" => "Courses / livraison"),
    array("icon" => "pets", "label" => "Animaux"),
    array("icon" => "handyman", "label" => "Bricolage"),
    array("icon" => "elderly", "label" => "Assistance personne agee"),
    array("icon" => "health_and_safety", "label" => "Assistance / securite"),
    array("icon" => "home", "label" => "Service a domicile")
);

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

    if($action == "add_category" || $action == "update_category"){

        $name = isset($_POST["name"])
            ? trim($_POST["name"])
            : "";

        $description = isset($_POST["description"])
            ? trim($_POST["description"])
            : "";

        $icon = isset($_POST["icon"])
            ? trim($_POST["icon"])
            : "";

        if($name == "" || $icon == ""){

            $_SESSION["error"] = "Le nom et l'icone sont obligatoires.";
            redirect_categories();

        }

        if($description == ""){

            $description = NULL;

        }

        if($action == "add_category"){

            $sql = "
            INSERT INTO service_categories(
                name,
                description,
                icon,
                created_at
            )
            VALUES(?, ?, ?, NOW())
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $name,
                $description,
                $icon
            );

            if(mysqli_stmt_execute($stmt)){

                $_SESSION["success"] = "Categorie ajoutee avec succes.";

            }else{

                $_SESSION["error"] = "Erreur lors de l'ajout de la categorie.";

            }

            mysqli_stmt_close($stmt);
            redirect_categories();

        }

        $category_id = isset($_POST["category_id"])
            ? (int)$_POST["category_id"]
            : 0;

        if($category_id <= 0){

            $_SESSION["error"] = "Categorie introuvable.";
            redirect_categories();

        }

        $sql = "
        UPDATE service_categories
        SET
            name = ?,
            description = ?,
            icon = ?
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssi",
            $name,
            $description,
            $icon,
            $category_id
        );

        if(mysqli_stmt_execute($stmt)){

            $_SESSION["success"] = "Categorie modifiee avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors de la modification de la categorie.";

        }

        mysqli_stmt_close($stmt);
        redirect_categories();

    }

    if($action == "delete_category"){

        $category_id = isset($_POST["category_id"])
            ? (int)$_POST["category_id"]
            : 0;

        if($category_id <= 0){

            $_SESSION["error"] = "Categorie introuvable.";
            redirect_categories();

        }

        $request_total = 0;

        $sql = "
        SELECT COUNT(*) AS total
        FROM service_requests
        WHERE category_id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $request_total);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if($request_total > 0){

            $_SESSION["error"] = "Impossible de supprimer cette categorie car elle est deja utilisee par des demandes de service.";
            redirect_categories();

        }

        $sql = "
        DELETE FROM service_categories
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $category_id);

        if(mysqli_stmt_execute($stmt)){

            $_SESSION["success"] = "Categorie supprimee avec succes.";

        }else{

            $_SESSION["error"] = "Erreur lors de la suppression de la categorie.";

        }

        mysqli_stmt_close($stmt);
        redirect_categories();

    }

}

$stats = array(
    "total_categories" => count_query($conn, "SELECT COUNT(*) AS total FROM service_categories"),
    "used_categories" => count_query($conn, "SELECT COUNT(DISTINCT category_id) AS total FROM service_requests"),
    "total_requests" => count_query($conn, "SELECT COUNT(*) AS total FROM service_requests")
);

$stats["unused_categories"] = $stats["total_categories"] - $stats["used_categories"];

if($stats["unused_categories"] < 0){

    $stats["unused_categories"] = 0;

}

$categories = array();

$sql = "
SELECT
    sc.id,
    sc.name,
    sc.description,
    sc.icon,
    sc.created_at,
    COUNT(sr.id) AS request_total
FROM service_categories sc
LEFT JOIN service_requests sr
ON sr.category_id = sc.id
GROUP BY
    sc.id,
    sc.name,
    sc.description,
    sc.icon,
    sc.created_at
ORDER BY sc.created_at DESC
";

$result = mysqli_query($conn, $sql);

if($result){

    while($row = mysqli_fetch_assoc($result)){

        $categories[] = $row;

    }

    mysqli_free_result($result);

}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Categories de Services | INFINITIA</title>

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .admin-summary-card{
            background:#ffffff;
            border-radius:14px;
            padding:18px;
            box-shadow:0 8px 22px rgba(0,0,0,.08);
            min-height:120px;
        }

        .admin-summary-card h5{
            color:#2f3b55;
            font-size:15px;
            font-weight:600;
            margin:12px 0 6px;
        }

        .admin-summary-card h3{
            color:#081f78;
            font-size:30px;
            font-weight:800;
            margin:0;
        }

        .icon-grid{
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(130px,1fr));
            gap:12px;
            margin-top:12px;
        }

        .icon-option{
            border:1px solid #e0e0e0;
            border-radius:12px;
            padding:12px;
            text-align:center;
            cursor:pointer;
            background:#ffffff;
            transition:.2s;
        }

        .icon-option i{
            color:#081f78;
            display:block;
            font-size:30px;
            margin-bottom:6px;
        }

        .icon-option.active{
            border-color:#e83e8c;
            background:#fff4fa;
            box-shadow:0 6px 16px rgba(232,62,140,.16);
        }
    </style>

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "categories-services";

    include("menuadmin.php");

    ?>

    <div class="main-content">

        <div class="topbar">

            <div>
                <div class="page-title">Categories de services</div>
                <div class="welcome-text">
                    Gere les categories utilisees pour les demandes de services.
                </div>
            </div>

            <a href="#modalAddCategory"
               class="btn waves-effect waves-light modal-trigger">
                <i class="material-icons left">add</i>
                Nouvelle categorie
            </a>

        </div>

        <?php if(isset($_SESSION["success"])){ ?>
            <div class="card-panel green white-text">
                <?php echo safe_text($_SESSION["success"]); ?>
            </div>
            <?php unset($_SESSION["success"]); ?>
        <?php } ?>

        <?php if(isset($_SESSION["error"])){ ?>
            <div class="card-panel red white-text">
                <?php echo safe_text($_SESSION["error"]); ?>
            </div>
            <?php unset($_SESSION["error"]); ?>
        <?php } ?>

        <div class="row">
            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">category</i></div>
                    <h5>Total categories</h5>
                    <h3><?php echo (int)$stats["total_categories"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon pink-gradient"><i class="material-icons">assignment</i></div>
                    <h5>Categories utilisees</h5>
                    <h3><?php echo (int)$stats["used_categories"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">inventory_2</i></div>
                    <h5>Non utilisees</h5>
                    <h3><?php echo (int)$stats["unused_categories"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">playlist_add_check</i></div>
                    <h5>Demandes de services</h5>
                    <h3><?php echo (int)$stats["total_requests"]; ?></h3>
                </div>
            </div>
        </div>

        <?php if(count($categories) > 0){ ?>

            <div class="table-card">
                <div class="table-title">Liste des categories</div>

                <table class="highlight responsive-table">
                    <thead>
                        <tr>
                            <th>Icone</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Demandes liees</th>
                            <th>Date de creation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $category){ ?>
                            <?php
                            $category_id = isset($category["id"]) ? (int)$category["id"] : 0;
                            $name = isset($category["name"]) ? $category["name"] : "";
                            $description = isset($category["description"]) ? $category["description"] : "";
                            $icon = isset($category["icon"]) ? $category["icon"] : "";
                            $created_at = isset($category["created_at"]) ? $category["created_at"] : "";
                            $request_total = isset($category["request_total"]) ? (int)$category["request_total"] : 0;
                            ?>
                            <tr>
                                <td>
                                    <?php if($icon != ""){ ?>
                                        <i class="material-icons"><?php echo safe_text($icon); ?></i>
                                    <?php }else{ ?>
                                        -
                                    <?php } ?>
                                </td>
                                <td><?php echo safe_text(display_value($name)); ?></td>
                                <td><?php echo safe_text(display_value($description)); ?></td>
                                <td><?php echo (int)$request_total; ?></td>
                                <td><?php echo safe_text(format_date_fr($created_at)); ?></td>
                                <td>
                                    <a href="#viewCategory<?php echo $category_id; ?>"
                                       class="modal-trigger green-text"
                                       title="Voir">
                                        <i class="material-icons">visibility</i>
                                    </a>

                                    <a href="#editCategory<?php echo $category_id; ?>"
                                       class="modal-trigger blue-text"
                                       title="Modifier">
                                        <i class="material-icons">edit</i>
                                    </a>

                                    <form action="categories-services.php"
                                          method="POST"
                                          style="display:inline;"
                                          onsubmit="return confirm('Voulez-vous supprimer cette categorie ?');">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                                        <button type="submit"
                                                class="btn-flat red-text"
                                                title="Supprimer"
                                                style="padding:0 6px;">
                                            <i class="material-icons">delete</i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        <?php }else{ ?>

            <div class="card">
                <div class="card-content center">
                    <i class="material-icons large blue-text text-darken-4">category</i>
                    <h5>Aucune categorie n'est encore enregistree.</h5>
                </div>
            </div>

        <?php } ?>

    </div>
</div>

<div id="modalAddCategory" class="modal modal-fixed-footer">
    <form action="categories-services.php" method="POST">
        <input type="hidden" name="action" value="add_category">
        <input type="hidden" name="icon" class="icon-hidden-field" value="">

        <div class="modal-content">
            <h4>Nouvelle categorie</h4>

            <div class="input-field">
                <input type="text" name="name" id="add_name" required>
                <label for="add_name">Nom</label>
            </div>

            <div class="input-field">
                <textarea name="description" id="add_description" class="materialize-textarea"></textarea>
                <label for="add_description">Description</label>
            </div>

            <h6>Selectionner une icone</h6>
            <div class="icon-grid">
                <?php foreach($icon_options as $option){ ?>
                    <div class="icon-option" data-icon="<?php echo safe_text($option["icon"]); ?>">
                        <i class="material-icons"><?php echo safe_text($option["icon"]); ?></i>
                        <span><?php echo safe_text($option["label"]); ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Annuler</a>
            <button type="submit" class="btn waves-effect waves-light">Enregistrer</button>
        </div>
    </form>
</div>

<?php foreach($categories as $category){ ?>
    <?php
    $category_id = isset($category["id"]) ? (int)$category["id"] : 0;
    $name = isset($category["name"]) ? $category["name"] : "";
    $description = isset($category["description"]) ? $category["description"] : "";
    $icon = isset($category["icon"]) ? $category["icon"] : "";
    $created_at = isset($category["created_at"]) ? $category["created_at"] : "";
    $request_total = isset($category["request_total"]) ? (int)$category["request_total"] : 0;
    ?>

    <div id="viewCategory<?php echo $category_id; ?>" class="modal modal-fixed-footer">
        <div class="modal-content center">
            <?php if($icon != ""){ ?>
                <i class="material-icons large blue-text text-darken-4"><?php echo safe_text($icon); ?></i>
            <?php } ?>
            <h4><?php echo safe_text(display_value($name)); ?></h4>
            <p><?php echo nl2br(safe_text(display_value($description))); ?></p>
            <p><strong>Demandes liees :</strong> <?php echo (int)$request_total; ?></p>
            <p><strong>Date de creation :</strong> <?php echo safe_text(format_date_fr($created_at)); ?></p>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Fermer</a>
        </div>
    </div>

    <div id="editCategory<?php echo $category_id; ?>" class="modal modal-fixed-footer">
        <form action="categories-services.php" method="POST">
            <input type="hidden" name="action" value="update_category">
            <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
            <input type="hidden" name="icon" class="icon-hidden-field" value="<?php echo safe_text($icon); ?>">

            <div class="modal-content">
                <h4>Modifier la categorie</h4>

                <div class="input-field">
                    <input type="text"
                           name="name"
                           id="edit_name<?php echo $category_id; ?>"
                           value="<?php echo safe_text($name); ?>"
                           required>
                    <label class="active" for="edit_name<?php echo $category_id; ?>">Nom</label>
                </div>

                <div class="input-field">
                    <textarea name="description"
                              id="edit_description<?php echo $category_id; ?>"
                              class="materialize-textarea"><?php echo safe_text($description); ?></textarea>
                    <label class="active" for="edit_description<?php echo $category_id; ?>">Description</label>
                </div>

                <h6>Selectionner une icone</h6>
                <div class="icon-grid">
                    <?php foreach($icon_options as $option){ ?>
                        <?php
                        $active_class = "";
                        if($icon == $option["icon"]){
                            $active_class = " active";
                        }
                        ?>
                        <div class="icon-option<?php echo $active_class; ?>" data-icon="<?php echo safe_text($option["icon"]); ?>">
                            <i class="material-icons"><?php echo safe_text($option["icon"]); ?></i>
                            <span><?php echo safe_text($option["label"]); ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="modal-footer">
                <a href="#!" class="modal-close btn-flat">Annuler</a>
                <button type="submit" class="btn waves-effect waves-light">Modifier</button>
            </div>
        </form>
    </div>
<?php } ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    M.Modal.init(document.querySelectorAll('.modal'));
    M.updateTextFields();

    var iconOptions = document.querySelectorAll('.icon-option');
    var i;

    for(i = 0; i < iconOptions.length; i++){
        iconOptions[i].addEventListener('click', function() {
            var modal = this.closest('.modal');
            var options = modal.querySelectorAll('.icon-option');
            var hiddenField = modal.querySelector('.icon-hidden-field');
            var j;

            for(j = 0; j < options.length; j++){
                options[j].classList.remove('active');
            }

            this.classList.add('active');
            hiddenField.value = this.getAttribute('data-icon');
        });
    }
});
</script>

</body>
</html>
