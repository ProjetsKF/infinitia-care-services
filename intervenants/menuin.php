<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================================
   PAGE ACTIVE
========================================= */

if(!isset($current_page)){

    $current_page = "";

}

/* =========================================
   SESSION
========================================= */

if(session_status() === PHP_SESSION_NONE){

    session_start();

}

/* =========================================
   CONNEXION BDD
========================================= */

require_once(dirname(__DIR__) . "/config/app.php");
require_once(dirname(__DIR__) . "/config/database.php");

/* =========================================
   VERIFICATION CONNEXION
========================================= */

if(!isset($_SESSION["user_id"])){

    header("Location: " . app_url("login"));
    exit();

}

$user_id = (int)$_SESSION["user_id"];

/* =========================================
   RECUPERATION INTERVENANT
========================================= */

$sqlUser = "

SELECT

    first_name,
    last_name

FROM users

WHERE id = ?

LIMIT 1

";

$stmtUser = $conn->prepare($sqlUser);

if(!$stmtUser){

    die("Erreur SQL : " . $conn->error);

}

$stmtUser->bind_param("i", $user_id);

if(!$stmtUser->execute()){

    die("Erreur exécution SQL : " . $stmtUser->error);

}

$resultUser = $stmtUser->get_result();

$userMenu = $resultUser->fetch_assoc();

if(!$userMenu){

    die("Utilisateur introuvable.");

}

?>

<!-- =========================================
     SIDEBAR INTERVENANT
========================================= -->

<div class="dashboard-mobile-header">

    <button type="button"
            class="dashboard-menu-toggle"
            aria-label="Ouvrir le menu intervenant"
            aria-controls="intervenant-sidebar"
            aria-expanded="false">
        <i class="material-icons" aria-hidden="true">menu</i>
    </button>

    <span>Menu intervenant</span>

</div>

<div class="dashboard-sidebar-overlay" aria-hidden="true"></div>

<div class="sidebar" id="intervenant-sidebar">

    <!-- LOGO -->

    <div class="sidebar-logo">

        <img
        src="<?php echo app_url_html("assets/images/brand1.png"); ?>"
        alt="INFINITIA">

        <div class="sidebar-title"
             style="
             display:flex;
             align-items:center;
             justify-content:center;
             gap:10px;
             color:white;
             font-weight:500;
             ">

            <i class="material-icons"
               style="font-size:28px;">

                account_circle

            </i>

            <span>

                <?= htmlspecialchars(
                    $userMenu['first_name']
                    .' '.
                    $userMenu['last_name']
                ); ?>

            </span>

        </div>

    </div>

    <!-- MENU -->

    <div class="sidebar-menu">

        <!-- TABLEAU DE BORD -->

        <a href="<?php echo app_url_html("intervenant/tableau-de-bord"); ?>"
           class="<?= ($current_page == 'dashboard') ? 'active' : ''; ?>">

            <i class="material-icons">
                dashboard
            </i>

            Tableau de bord

        </a>

        <div class="menu-section-title">
            Profil professionnel
        </div>

        <!-- PROFIL -->

        <a href="<?php echo app_url_html("intervenant/profil"); ?>"
           class="<?= ($current_page == 'profil') ? 'active' : ''; ?>">

            <i class="material-icons">
                person
            </i>

            Mon Profil

        </a>

        <!-- DOCUMENTS -->

        <a href="<?php echo app_url_html("intervenant/documents"); ?>"
           class="<?= ($current_page == 'documents') ? 'active' : ''; ?>">

            <i class="material-icons">
                folder
            </i>

            Mes Documents

        </a>

        <!-- COMPETENCES -->

        <a href="<?php echo app_url_html("intervenant/competences"); ?>"
           class="<?= ($current_page == 'competences') ? 'active' : ''; ?>">

            <i class="material-icons">
                workspace_premium
            </i>

            Mes Compétences

        </a>

        <div class="menu-section-title">
            Suivi des activités
        </div>

        <!-- MISSIONS -->

        <a href="<?php echo app_url_html("intervenant/missions"); ?>"
           class="<?= ($current_page == 'missions') ? 'active' : ''; ?>">

            <i class="material-icons">
                assignment
            </i>

            Mes Missions

        </a>

        <!-- FORMATIONS -->

        <a href="<?php echo app_url_html("intervenant/formations"); ?>"
           class="<?= ($current_page == 'formations') ? 'active' : ''; ?>">

            <i class="material-icons">
                school
            </i>

            Mes Formations

        </a>

        <div class="menu-section-title">
            Mon compte
        </div>

        <!-- PARAMETRES -->

        <a href="<?php echo app_url_html("intervenant/parametres"); ?>"
           class="<?= ($current_page == 'parametres') ? 'active' : ''; ?>">

            <i class="material-icons">
                settings
            </i>

            Paramètres

        </a>

        <!-- DECONNEXION -->

        <a href="<?php echo app_url_html("deconnexion"); ?>">

            <i class="material-icons">
                logout
            </i>

            Déconnexion

        </a>

    </div>

</div>

<script src="<?php echo app_url_html("assets/js/dashboard-menu.js"); ?>"></script>
