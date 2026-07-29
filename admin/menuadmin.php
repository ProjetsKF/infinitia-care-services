<?php

if(!isset($current_page)){

    $current_page = "";

}

?>

<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once("../config/database.php");

$user_id = $_SESSION["user_id"];

$sqlUser = "SELECT first_name, last_name
            FROM users
            WHERE id = ?";

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

<!-- =========================================
     ADMIN SIDEBAR
========================================= -->

<div class="sidebar">

    <div class="sidebar-logo">

        <img src="../assets/images/brand1.png"
        alt="INFINITIA">

        <div class="sidebar-title"
        style="display:flex;
               align-items:center;
               justify-content:center;
               gap:10px;
               color:white;
               font-weight:500;">

            <i class="material-icons" style="font-size:28px;">
                admin_panel_settings
            </i>

            <span>
                <?php echo htmlspecialchars($admin['first_name'].' '.$admin['last_name'], ENT_QUOTES, 'UTF-8'); ?>
            </span>

        </div>

    </div>

    <div class="sidebar-menu">

        <a href="dashboard.php"
        class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">

            <i class="material-icons">dashboard</i>

            Tableau de bord

        </a>

        <div class="menu-section-title">
            Gestion des demandes
        </div>

        <a href="demandes.php"
        class="<?php echo ($current_page == 'demandes') ? 'active' : ''; ?>">

            <i class="material-icons">assignment</i>

            Demandes

        </a>

        <a href="affectations.php"
        class="<?php echo ($current_page == 'affectations') ? 'active' : ''; ?>">

            <i class="material-icons">person_add</i>

            Affectations

        </a>

        <div class="menu-section-title">
            Gestion des intervenants
        </div>

        <a href="intervenants.php"
        class="<?php echo ($current_page == 'intervenants') ? 'active' : ''; ?>">

            <i class="material-icons">groups</i>

            Intervenants

        </a>

        <a href="formations.php"
        class="<?php echo ($current_page == 'formations') ? 'active' : ''; ?>">

            <i class="material-icons">school</i>

            Formations

        </a>

        <div class="menu-section-title">
            Gestion des services
        </div>

        <a href="categories-services.php"
        class="<?php echo ($current_page == 'categories-services') ? 'active' : ''; ?>">

            <i class="material-icons">category</i>

            Catégories de services

        </a>

        <div class="menu-section-title">
            Suivi des activités
        </div>

        <a href="missions.php"
        class="<?php echo ($current_page == 'missions') ? 'active' : ''; ?>">

            <i class="material-icons">work</i>

            Missions

        </a>

        <a href="paiements.php"
        class="<?php echo ($current_page == 'paiements') ? 'active' : ''; ?>">

            <i class="material-icons">payments</i>

            Paiements

        </a>

        <a href="evaluations.php"
        class="<?php echo ($current_page == 'evaluations') ? 'active' : ''; ?>">

            <i class="material-icons">star</i>

            Évaluations

        </a>

        <a href="carte-clients.php"
        class="<?php echo ($current_page == 'carte-clients') ? 'active' : ''; ?>">

            <i class="material-icons">location_on</i>

            Localisation des clients

        </a>

        <div class="menu-section-title">
            Administration
        </div>

        <a href="utilisateurs.php"
        class="<?php echo ($current_page == 'utilisateurs') ? 'active' : ''; ?>">

            <i class="material-icons">manage_accounts</i>

            Utilisateurs

        </a>

        <a href="../logout.php">

            <i class="material-icons">logout</i>

            Déconnexion

        </a>

    </div>

</div>

<style>

    .menu-section-title{
        color:rgba(255,255,255,0.65);
        font-size:11px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:1px;
        margin:22px 0 8px 22px;
    }
    .sidebar{
    height:100vh;
    overflow-y:auto;
    overflow-x:hidden;
    position:fixed;
    left:0;
    top:0;
}

/* Style discret de la barre de défilement */
.sidebar::-webkit-scrollbar{
    width:6px;
}

.sidebar::-webkit-scrollbar-track{
    background:rgba(255,255,255,0.08);
}

.sidebar::-webkit-scrollbar-thumb{
    background:rgba(255,255,255,0.35);
    border-radius:10px;
}

.sidebar::-webkit-scrollbar-thumb:hover{
    background:rgba(255,255,255,0.55);
}

</style>
