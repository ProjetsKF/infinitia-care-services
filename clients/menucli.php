<?php

if(!isset($current_page)){

    $current_page = "";

}

?>

<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once(dirname(__DIR__) . "/config/app.php");
require_once(dirname(__DIR__) . "/config/database.php");

$user_id = $_SESSION["user_id"];

$user_id = $_SESSION["user_id"];

$sqlUser = "SELECT first_name, last_name
            FROM users
            WHERE id = ?";

$stmtUser = $conn->prepare($sqlUser);

$stmtUser->bind_param("i", $user_id);

$stmtUser->execute();

$resultUser = $stmtUser->get_result();

$client = $resultUser->fetch_assoc();

?>

<!-- =========================================
     SIDEBAR
========================================= -->

<div class="dashboard-mobile-header">

    <button type="button"
            class="dashboard-menu-toggle"
            aria-label="Ouvrir le menu client"
            aria-controls="client-sidebar"
            aria-expanded="false">
        <i class="material-icons" aria-hidden="true">menu</i>
    </button>

    <span>Menu client</span>

</div>

<div class="dashboard-sidebar-overlay" aria-hidden="true"></div>

<div class="sidebar" id="client-sidebar">

    <!-- LOGO -->

    <div class="sidebar-logo">

        <img src="<?php echo app_url_html("assets/images/brand1.png"); ?>"
        alt="INFINITIA">

        <div class="sidebar-title"
     style="display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            color:white;
            font-weight:500;">

    <i class="material-icons"
       style="font-size:28px;">
        account_circle
    </i>

    <span>
        <?= htmlspecialchars($client['first_name'].' '.$client['last_name']); ?>
    </span>

</div>

    </div>

    <!-- MENU -->

    <div class="sidebar-menu">

        <!-- TABLEAU DE BORD -->

        <a href="<?php echo app_url_html("client/tableau-de-bord"); ?>"
        class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">

            <i class="material-icons">

                dashboard

            </i>

            Tableau de bord

        </a>

        <div class="menu-section-title">
            Gestion des services
        </div>

        <!-- MES DEMANDES -->

        <a href="<?php echo app_url_html("client/demandes"); ?>"
        class="<?php echo ($current_page == 'demandes') ? 'active' : ''; ?>">

            <i class="material-icons">

                work

            </i>

            Mes demandes

        </a>

        <!-- INTERVENANTS -->

        <a href="<?php echo app_url_html("client/intervenants"); ?>"
        class="<?php echo ($current_page == 'intervenants') ? 'active' : ''; ?>">

            <i class="material-icons">

                people

            </i>

            Intervenants

        </a>

        <!-- MISSIONS -->

        <a href="<?php echo app_url_html("client/missions"); ?>"
        class="<?php echo ($current_page == 'missions') ? 'active' : ''; ?>">

            <i class="material-icons">

                assignment

            </i>

            Missions

        </a>

        <!-- EVALUATIONS -->

        <a href="<?php echo app_url_html("client/evaluations"); ?>"
        class="<?php echo ($current_page == 'evaluations') ? 'active' : ''; ?>">

            <i class="material-icons">

                star

            </i>

            Évaluation

        </a>

        <!-- PAIEMENTS -->

        <a href="<?php echo app_url_html("client/paiements"); ?>"
        class="<?php echo ($current_page == 'paiements') ? 'active' : ''; ?>">

            <i class="material-icons">

                payments

            </i>

            Paiements

        </a>

        <div class="menu-section-title">
            Mon compte
        </div>

        <!-- PARAMETRES -->

        <a href="<?php echo app_url_html("client/parametres"); ?>"
        class="<?php echo ($current_page == 'parametres') ? 'active' : ''; ?>">

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
