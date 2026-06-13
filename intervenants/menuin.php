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

require_once("../config/database.php");

/* =========================================
   VERIFICATION CONNEXION
========================================= */

if(!isset($_SESSION["user_id"])){

    header("Location: ../login.php");
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

<div class="sidebar">

    <!-- LOGO -->

    <div class="sidebar-logo">

        <img
        src="../assets/images/brand1.png"
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

        <a href="candidashboard.php"
           class="<?= ($current_page == 'dashboard') ? 'active' : ''; ?>">

            <i class="material-icons">
                dashboard
            </i>

            Tableau de bord

        </a>

        <!-- PROFIL -->

        <a href="profil.php"
           class="<?= ($current_page == 'profil') ? 'active' : ''; ?>">

            <i class="material-icons">
                person
            </i>

            Mon Profil

        </a>

        <!-- DOCUMENTS -->

        <a href="mes-documents.php"
           class="<?= ($current_page == 'documents') ? 'active' : ''; ?>">

            <i class="material-icons">
                folder
            </i>

            Mes Documents

        </a>

        <!-- COMPETENCES -->

        <a href="mes-competences.php"
           class="<?= ($current_page == 'competences') ? 'active' : ''; ?>">

            <i class="material-icons">
                workspace_premium
            </i>

            Mes Compétences

        </a>

        <!-- MISSIONS -->

        <a href="missions.php"
           class="<?= ($current_page == 'missions') ? 'active' : ''; ?>">

            <i class="material-icons">
                assignment
            </i>

            Mes Missions

        </a>

        <!-- EVALUATIONS -->

        <a href="evaluations.php"
           class="<?= ($current_page == 'evaluations') ? 'active' : ''; ?>">

            <i class="material-icons">
                star
            </i>

            Mes Évaluations

        </a>

        <!-- FORMATIONS -->

        <a href="formations.php"
           class="<?= ($current_page == 'formations') ? 'active' : ''; ?>">

            <i class="material-icons">
                school
            </i>

            Mes Formations

        </a>

        <!-- PARAMETRES -->

        <a href="parametres.php"
           class="<?= ($current_page == 'parametres') ? 'active' : ''; ?>">

            <i class="material-icons">
                settings
            </i>

            Paramètres

        </a>

        <!-- DECONNEXION -->

        <a href="../logout.php">

            <i class="material-icons">
                logout
            </i>

            Déconnexion

        </a>

    </div>

</div>