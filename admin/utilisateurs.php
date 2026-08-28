<?php

session_start();

require_once("../config/database.php");
require_once(dirname(__DIR__) . "/includes/admin-delete-security.php");

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1){

    header("Location: " . app_url("login"));
    exit();

}

$admin_delete_csrf = admin_delete_csrf_token();

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

function redirect_utilisateurs()
{
    header("Location: " . app_url("admin/utilisateurs"));
    exit();
}

function status_badge_class($status)
{
    if($status == "active"){

        return "green";

    }

    if($status == "inactive"){

        return "grey";

    }

    if($status == "suspended"){

        return "red";

    }

    return "grey";
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

function bind_params($stmt, $types, $params)
{
    if($types == ""){

        return true;

    }

    $bind_names = array();
    $bind_names[] = $types;
    $i = 0;

    for($i = 0; $i < count($params); $i++){

        $bind_names[] = &$params[$i];

    }

    return call_user_func_array(array($stmt, "bind_param"), $bind_names);
}

function pagination_url($page, $search)
{
    $params = $_GET;
    $params["page"] = (int)$page;

    return app_url_with_query("admin/utilisateurs", $params);
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

    $user_id = isset($_POST["user_id"])
        ? (int)$_POST["user_id"]
        : 0;

    $new_status = "";

    if($action == "set_active"){

        $new_status = "active";

    }elseif($action == "set_inactive"){

        $new_status = "inactive";

    }elseif($action == "set_suspended"){

        $new_status = "suspended";

    }

    if($user_id <= 0 || $new_status == ""){

        $_SESSION["error"] = "Action utilisateur invalide.";
        redirect_utilisateurs();

    }

    $existing_id = 0;

    $sql = "
    SELECT id
    FROM users
    WHERE id = ?
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        die("Erreur SQL : " . mysqli_error($conn));

    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $existing_id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if($existing_id <= 0){

        $_SESSION["error"] = "Utilisateur introuvable.";
        redirect_utilisateurs();

    }

    $sql = "
    UPDATE users
    SET status = ?
    WHERE id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        die("Erreur SQL : " . mysqli_error($conn));

    }

    mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);

    if(mysqli_stmt_execute($stmt)){

        $_SESSION["success"] = "Statut utilisateur mis a jour.";

    }else{

        $_SESSION["error"] = "Erreur lors de la mise a jour du statut.";

    }

    mysqli_stmt_close($stmt);
    redirect_utilisateurs();

}

$stats = array(
    "total" => count_query($conn, "SELECT COUNT(*) AS total FROM users"),
    "active" => count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'active'"),
    "inactive" => count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'inactive'"),
    "suspended" => count_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status = 'suspended'")
);

$users = array();
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$per_page = 50;

if($page <= 0){

    $page = 1;

}

$where_parts = array();
$params = array();
$types = "";

if($search != ""){

    $where_parts[] = "(
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR u.email LIKE ?
        OR u.phone LIKE ?
        OR COALESCE(
            NULLIF(TRIM(r.name), ''),
            CASE u.role_id
                WHEN 1 THEN 'Administrateur'
                WHEN 2 THEN 'Client'
                WHEN 3 THEN 'Intervenant'
                ELSE NULL
            END
        ) LIKE ?
        OR u.status LIKE ?
    )";

    $search_like = "%" . $search . "%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "ssssss";

}

$where_sql = "";

if(count($where_parts) > 0){

    $where_sql = "WHERE " . implode(" AND ", $where_parts);

}

$total_filtered = 0;

$sql = "
SELECT COUNT(*) AS total
FROM users u
LEFT JOIN roles r
ON r.id = u.role_id
" . $where_sql;

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

bind_params($stmt, $types, $params);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total_filtered);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$total_pages = (int)ceil($total_filtered / $per_page);

if($total_pages < 1){

    $total_pages = 1;

}

if($page > $total_pages){

    $page = $total_pages;

}

$offset = ($page - 1) * $per_page;
$list_params = $params;
$list_types = $types . "ii";
$list_params[] = $per_page;
$list_params[] = $offset;

$sql = "
SELECT
    u.id,
    u.role_id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.profile_photo,
    u.status,
    u.last_login,
    u.created_at,
    COALESCE(
        NULLIF(TRIM(r.name), ''),
        CASE u.role_id
            WHEN 1 THEN 'Administrateur'
            WHEN 2 THEN 'Client'
            WHEN 3 THEN 'Intervenant'
            ELSE NULL
        END
    ) AS role_name,
    r.description AS role_description
FROM users u
LEFT JOIN roles r
ON r.id = u.role_id
" . $where_sql . "
ORDER BY u.created_at DESC
LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

bind_params($stmt, $list_types, $list_params);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result(
    $stmt,
    $result_id,
    $result_role_id,
    $result_first_name,
    $result_last_name,
    $result_email,
    $result_phone,
    $result_profile_photo,
    $result_status,
    $result_last_login,
    $result_created_at,
    $result_role_name,
    $result_role_description
);

while(mysqli_stmt_fetch($stmt)){

    $users[] = array(
        "id" => $result_id,
        "role_id" => $result_role_id,
        "first_name" => $result_first_name,
        "last_name" => $result_last_name,
        "email" => $result_email,
        "phone" => $result_phone,
        "profile_photo" => $result_profile_photo,
        "status" => $result_status,
        "last_login" => $result_last_login,
        "created_at" => $result_created_at,
        "role_name" => $result_role_name,
        "role_description" => $result_role_description
    );

}

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(dirname(__DIR__) . "/includes/pwa-head.php"); ?>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Utilisateurs | INFINITIA</title>

    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

    <style>
        .admin-summary-card{
            background:#ffffff;
            border-radius:14px;
            padding:18px;
            box-shadow:0 8px 22px rgba(0,0,0,.08);
            min-height:118px;
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

        .actions-wrap{
            display:flex;
            flex-wrap:wrap;
            gap:7px;
        }

        .actions-wrap form{
            margin:0;
        }

        .search-card{
            background:#ffffff;
            border-radius:14px;
            box-shadow:0 8px 22px rgba(0,0,0,.08);
            margin-bottom:22px;
            padding:18px;
        }

        .search-card .btn,
        .search-card .btn-flat{
            border-radius:22px;
        }

        .pagination-wrap{
            align-items:center;
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            justify-content:center;
            margin-top:22px;
        }

        .pagination-wrap a,
        .pagination-wrap span{
            border-radius:20px;
            min-width:36px;
            text-align:center;
        }

        .user-detail-card{
            background:#ffffff;
            border:1px solid #eeeeee;
            border-radius:14px;
            box-shadow:0 6px 18px rgba(0,0,0,.05);
            padding:18px;
        }

        .detail-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:12px;
        }

        .detail-item{
            background:#fafafa;
            border-radius:10px;
            padding:12px;
        }

        .detail-label{
            color:#757575;
            display:block;
            font-size:12px;
            font-weight:600;
            text-transform:uppercase;
        }
    </style>

</head>

<body class="admin-module">

<div class="dashboard">

    <?php

    $current_page = "utilisateurs";

    include("menuadmin.php");

    ?>

    <div class="main-content">

        <div class="topbar">
            <div>
                <div class="page-title">Utilisateurs</div>
                <div class="welcome-text">
                    Consultation des comptes et gestion limitee des statuts.
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION["success"])){ ?>
            <div class="card-panel green white-text admin-flash-message">
                <span><?php echo safe_text($_SESSION["success"]); ?></span>
                <button type="button" class="admin-flash-close" aria-label="Fermer le message">
                    <i class="material-icons" aria-hidden="true">close</i>
                </button>
            </div>
            <?php unset($_SESSION["success"]); ?>
        <?php } ?>

        <?php if(isset($_SESSION["error"])){ ?>
            <div class="card-panel red white-text admin-flash-message">
                <span><?php echo safe_text($_SESSION["error"]); ?></span>
                <button type="button" class="admin-flash-close" aria-label="Fermer le message">
                    <i class="material-icons" aria-hidden="true">close</i>
                </button>
            </div>
            <?php unset($_SESSION["error"]); ?>
        <?php } ?>

        <div class="row intervenant-stat-grid admin-stat-grid">
            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">groups</i></div>
                    <h5>Total utilisateurs</h5>
                    <h3><?php echo (int)$stats["total"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">check_circle</i></div>
                    <h5>Actifs</h5>
                    <h3><?php echo (int)$stats["active"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon gold-gradient"><i class="material-icons">pause_circle</i></div>
                    <h5>Inactifs</h5>
                    <h3><?php echo (int)$stats["inactive"]; ?></h3>
                </div>
            </div>

            <div class="col s12 m6 l3">
                <div class="admin-summary-card">
                    <div class="card-icon pink-gradient"><i class="material-icons">block</i></div>
                    <h5>Suspendus</h5>
                    <h3><?php echo (int)$stats["suspended"]; ?></h3>
                </div>
            </div>
        </div>

        <div class="search-card">
            <form action="<?php echo app_url_html("admin/utilisateurs"); ?>" method="GET">
                <div class="row" style="margin-bottom:0;">
                    <div class="input-field col s12 m8">
                        <i class="material-icons prefix">search</i>
                        <input type="text"
                               name="search"
                               id="search"
                               value="<?php echo safe_text($search); ?>">
                        <label for="search" class="<?php if($search != ""){ echo "active"; } ?>">
                            Rechercher par prenom, nom, email, telephone, role ou statut
                        </label>
                    </div>

                    <div class="col s12 m4" style="padding-top:22px;">
                        <button type="submit" class="btn waves-effect waves-light">
                            <i class="material-icons left">search</i>
                            Rechercher
                        </button>
                        <a href="<?php echo app_url_html("admin/utilisateurs"); ?>" class="btn-flat">Reinitialiser</a>
                    </div>
                </div>
            </form>
        </div>

        <?php if(count($users) > 0){ ?>

            <div class="table-card">
                <div class="table-title">
                    Liste des utilisateurs
                    <span class="grey-text" style="font-size:14px; font-weight:400;">
                        (<?php echo (int)$total_filtered; ?> resultat(s))
                    </span>
                </div>

                <table class="highlight responsive-table intervenant-table mobile-card-table admin-responsive-table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Telephone</th>
                            <th>Statut</th>
                            <th>Derniere connexion</th>
                            <th>Date creation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user){ ?>
                            <?php
                            $user_id = isset($user["id"]) ? (int)$user["id"] : 0;
                            $status = isset($user["status"]) ? $user["status"] : "";
                            $full_name = trim($user["first_name"] . " " . $user["last_name"]);
                            $is_connected_user = $user_id === (int)$_SESSION["user_id"];
                            ?>
                            <tr class="mobile-card-row">
                                <td data-label="Rôle"><?php echo safe_text(display_value($user["role_name"])); ?></td>
                                <td data-label="Nom"><?php echo safe_text(display_value($full_name)); ?></td>
                                <td data-label="Email"><?php echo safe_text(display_value($user["email"])); ?></td>
                                <td data-label="Téléphone"><?php echo safe_text(display_value($user["phone"])); ?></td>
                                <td data-label="Statut">
                                    <span class="new badge <?php echo safe_text(status_badge_class($status)); ?>" data-badge-caption="">
                                        <?php echo safe_text(display_value($status)); ?>
                                    </span>
                                </td>
                                <td data-label="Dernière connexion"><?php echo safe_text(format_date_fr($user["last_login"])); ?></td>
                                <td data-label="Date de création"><?php echo safe_text(format_date_fr($user["created_at"])); ?></td>
                                <td data-label="Actions">
                                    <div class="actions-wrap admin-actions">
                                        <a href="#viewUser<?php echo $user_id; ?>"
                                           class="btn-small green modal-trigger">
                                            Voir
                                        </a>

                                        <?php if($status != "active"){ ?>
                                            <form action="<?php echo app_url_html("admin/utilisateurs"); ?>" method="POST">
                                                <input type="hidden" name="action" value="set_active">
                                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                                <button type="submit" class="btn-small blue">Activer</button>
                                            </form>
                                        <?php } ?>

                                        <?php if($status != "inactive"){ ?>
                                            <form action="<?php echo app_url_html("admin/utilisateurs"); ?>" method="POST">
                                                <input type="hidden" name="action" value="set_inactive">
                                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                                <button type="submit" class="btn-small grey darken-1">Desactiver</button>
                                            </form>
                                        <?php } ?>

                                        <?php if($status != "suspended"){ ?>
                                            <form action="<?php echo app_url_html("admin/utilisateurs"); ?>" method="POST">
                                                <input type="hidden" name="action" value="set_suspended">
                                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                                <button type="submit" class="btn-small red">Suspendre</button>
                                            </form>
                                        <?php } ?>

                                        <?php if(!$is_connected_user){ ?>
                                            <a href="#deleteUser<?php echo $user_id; ?>"
                                               class="btn-small red darken-2 modal-trigger admin-delete-trigger">
                                                <i class="material-icons" aria-hidden="true">delete</i>
                                                Supprimer
                                            </a>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <div class="pagination-wrap">
                    <ul class="pagination center-align">
                    <?php if($page > 1){ ?>
                        <li class="waves-effect">
                            <a href="<?php echo safe_text(pagination_url($page - 1, $search)); ?>">Precedent</a>
                        </li>
                    <?php }else{ ?>
                        <li class="disabled"><a href="#!">Precedent</a></li>
                    <?php } ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if($start_page > 1){
                    ?>
                        <li class="waves-effect"><a href="<?php echo safe_text(pagination_url(1, $search)); ?>">1</a></li>
                        <?php if($start_page > 2){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                    <?php } ?>

                    <?php
                    for($page_number = $start_page; $page_number <= $end_page; $page_number++){
                    ?>
                        <?php if($page_number == $page){ ?>
                            <li class="active"><a href="#!"><?php echo (int)$page_number; ?></a></li>
                        <?php }else{ ?>
                            <li class="waves-effect">
                                <a href="<?php echo safe_text(pagination_url($page_number, $search)); ?>"><?php echo (int)$page_number; ?></a>
                            </li>
                        <?php } ?>
                    <?php } ?>

                    <?php if($end_page < $total_pages){ ?>
                        <?php if($end_page < $total_pages - 1){ ?><li class="disabled"><a href="#!">...</a></li><?php } ?>
                        <li class="waves-effect"><a href="<?php echo safe_text(pagination_url($total_pages, $search)); ?>"><?php echo (int)$total_pages; ?></a></li>
                    <?php } ?>

                    <?php if($page < $total_pages){ ?>
                        <li class="waves-effect">
                            <a href="<?php echo safe_text(pagination_url($page + 1, $search)); ?>">Suivant</a>
                        </li>
                    <?php }else{ ?>
                        <li class="disabled"><a href="#!">Suivant</a></li>
                    <?php } ?>
                    </ul>
                </div>
            </div>

        <?php }else{ ?>

            <div class="card">
                <div class="card-content center">
                    <i class="material-icons large blue-text text-darken-4">groups</i>
                    <h5>Aucun utilisateur ne correspond a votre recherche.</h5>
                </div>
            </div>

        <?php } ?>

    </div>
</div>

<?php foreach($users as $user){ ?>
    <?php
    $user_id = isset($user["id"]) ? (int)$user["id"] : 0;
    $status = isset($user["status"]) ? $user["status"] : "";
    $full_name = trim($user["first_name"] . " " . $user["last_name"]);
    ?>

    <div id="viewUser<?php echo $user_id; ?>" class="modal modal-fixed-footer">
        <div class="modal-content">
            <div class="user-detail-card">
                <h4><?php echo safe_text(display_value($full_name)); ?></h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Role</span>
                        <?php echo safe_text(display_value($user["role_name"])); ?>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <?php echo safe_text(display_value($user["email"])); ?>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Telephone</span>
                        <?php echo safe_text(display_value($user["phone"])); ?>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Statut</span>
                        <?php echo safe_text(display_value($status)); ?>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Derniere connexion</span>
                        <?php echo safe_text(format_date_fr($user["last_login"])); ?>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date creation</span>
                        <?php echo safe_text(format_date_fr($user["created_at"])); ?>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Description role</span>
                        <?php echo safe_text(display_value($user["role_description"])); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Fermer</a>
        </div>
    </div>

    <?php if($user_id !== (int)$_SESSION["user_id"]){ ?>
        <div id="deleteUser<?php echo $user_id; ?>" class="modal admin-delete-modal">
            <div class="modal-content">
                <h4>Confirmer la suppression</h4>
                <p>
                    Vous demandez la suppression définitive du compte de
                    <strong><?php echo safe_text(display_value($full_name)); ?></strong>.
                </p>
                <p>
                    Cette action est irréversible. Les profils et données liées seront vérifiés avant toute suppression.
                </p>
                <p class="red-text text-darken-2">
                    Si un historique de demandes, missions, paiements, formations ou évaluations existe, la suppression sera automatiquement refusée.
                </p>
            </div>

            <div class="modal-footer">
                <a href="#!" class="modal-close btn-flat">Annuler</a>
                <form action="<?php echo app_url_html("admin/supprimer-utilisateur.php"); ?>"
                      method="POST"
                      class="admin-delete-form">
                    <input type="hidden" name="csrf_token" value="<?php echo safe_text($admin_delete_csrf); ?>">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <button type="submit" class="btn red darken-2 waves-effect waves-light">
                        <i class="material-icons left" aria-hidden="true">delete</i>
                        Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    <?php } ?>
<?php } ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script src="<?php echo app_url_html("assets/js/admin-delete-ui.js"); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    M.Modal.init(document.querySelectorAll('.modal'));
});
</script>

</body>
</html>
