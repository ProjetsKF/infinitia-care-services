<?php

if(!isset($current_page)){
    $current_page = "";
}

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once(dirname(__DIR__) . "/config/app.php");
require_once(dirname(__DIR__) . "/config/database.php");

$user_id = $_SESSION["user_id"];
$sqlUser = "SELECT first_name, last_name FROM users WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$stmtUser->bind_result($admin_first_name, $admin_last_name);

$admin = array(
    "first_name" => "",
    "last_name" => ""
);

if($stmtUser->fetch()){
    $admin["first_name"] = $admin_first_name;
    $admin["last_name"] = $admin_last_name;
}

$stmtUser->close();

?>

<div class="dashboard-mobile-header">
    <button type="button"
            class="dashboard-menu-toggle"
            aria-label="Ouvrir le menu administrateur"
            aria-controls="admin-sidebar"
            aria-expanded="false">
        <i class="material-icons" aria-hidden="true">menu</i>
    </button>
    <span>Menu administrateur</span>
</div>

<div class="dashboard-sidebar-overlay" aria-hidden="true"></div>

<div class="sidebar" id="admin-sidebar">
    <div class="sidebar-logo">
        <img src="<?php echo app_url_html("assets/images/brand1.png"); ?>" alt="INFINITIA">

        <div class="sidebar-title"
             style="display:flex;align-items:center;justify-content:center;gap:10px;color:white;font-weight:500;">
            <i class="material-icons" style="font-size:28px;">admin_panel_settings</i>
            <span>
                <?php echo htmlspecialchars($admin["first_name"] . " " . $admin["last_name"], ENT_QUOTES, "UTF-8"); ?>
            </span>
        </div>
    </div>

    <div class="sidebar-menu">
        <a href="<?php echo app_url_html("admin/tableau-de-bord"); ?>"
           class="<?php echo ($current_page == "dashboard") ? "active" : ""; ?>">
            <i class="material-icons">dashboard</i>
            Tableau de bord
        </a>

        <div class="menu-section-title">Gestion des demandes</div>

        <a href="<?php echo app_url_html("admin/demandes"); ?>"
           class="<?php echo ($current_page == "demandes") ? "active" : ""; ?>">
            <i class="material-icons">assignment</i>
            Demandes
        </a>

        <a href="<?php echo app_url_html("admin/affectations"); ?>"
           class="<?php echo ($current_page == "affectations") ? "active" : ""; ?>">
            <i class="material-icons">person_add</i>
            Affectations
        </a>

        <div class="menu-section-title">Gestion des intervenants</div>

        <a href="<?php echo app_url_html("admin/intervenants"); ?>"
           class="<?php echo ($current_page == "intervenants") ? "active" : ""; ?>">
            <i class="material-icons">groups</i>
            Intervenants
        </a>

        <a href="<?php echo app_url_html("admin/formations"); ?>"
           class="<?php echo ($current_page == "formations") ? "active" : ""; ?>">
            <i class="material-icons">school</i>
            Formations
        </a>

        <div class="menu-section-title">Gestion des services</div>

        <a href="<?php echo app_url_html("admin/categories"); ?>"
           class="<?php echo ($current_page == "categories-services") ? "active" : ""; ?>">
            <i class="material-icons">category</i>
            Catégories de services
        </a>

        <div class="menu-section-title">Suivi des activités</div>

        <a href="<?php echo app_url_html("admin/missions"); ?>"
           class="<?php echo ($current_page == "missions") ? "active" : ""; ?>">
            <i class="material-icons">work</i>
            Missions
        </a>

        <a href="<?php echo app_url_html("admin/paiements"); ?>"
           class="<?php echo ($current_page == "paiements") ? "active" : ""; ?>">
            <i class="material-icons">payments</i>
            Paiements
        </a>

        <a href="<?php echo app_url_html("admin/evaluations"); ?>"
           class="<?php echo ($current_page == "evaluations") ? "active" : ""; ?>">
            <i class="material-icons">star</i>
            Évaluations
        </a>

        <a href="<?php echo app_url_html("admin/localisation-clients"); ?>"
           class="<?php echo ($current_page == "carte-clients") ? "active" : ""; ?>">
            <i class="material-icons">location_on</i>
            Localisation des clients
        </a>

        <div class="menu-section-title">Administration</div>

        <a href="<?php echo app_url_html("admin/utilisateurs"); ?>"
           class="<?php echo ($current_page == "utilisateurs") ? "active" : ""; ?>">
            <i class="material-icons">manage_accounts</i>
            Utilisateurs
        </a>

        <a href="<?php echo app_url_html("deconnexion"); ?>">
            <i class="material-icons">logout</i>
            Déconnexion
        </a>
        <?php include(dirname(__DIR__) . "/includes/pwa-install-button.php"); ?>
    </div>
</div>

<script src="<?php echo app_url_html("assets/js/dashboard-menu.js"); ?>"></script>
