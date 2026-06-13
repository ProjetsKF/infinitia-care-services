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

<div class="sidebar">

    <!-- LOGO -->

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

        <a href="clidashboard.php"
        class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">

            <i class="material-icons">

                dashboard

            </i>

            Tableau de bord

        </a>

        <!-- MES DEMANDES -->

        <a href="mes-demandes.php"
        class="<?php echo ($current_page == 'demandes') ? 'active' : ''; ?>">

            <i class="material-icons">

                work

            </i>

            Mes demandes

        </a>

        <!-- INTERVENANTS -->

        <a href="cli_intervenants.php"
        class="<?php echo ($current_page == 'intervenants') ? 'active' : ''; ?>">

            <i class="material-icons">

                people

            </i>

            Intervenants

        </a>

        <!-- MISSIONS -->

        <a href="missions.php"
        class="<?php echo ($current_page == 'missions') ? 'active' : ''; ?>">

            <i class="material-icons">

                assignment

            </i>

            Missions

        </a>

        <!-- EVALUATIONS -->

        <a href="evaluations.php"
        class="<?php echo ($current_page == 'evaluations') ? 'active' : ''; ?>">

            <i class="material-icons">

                star

            </i>

            Évaluation des services

        </a>

        <!-- PAIEMENTS -->

        <a href="paiements.php"
        class="<?php echo ($current_page == 'paiements') ? 'active' : ''; ?>">

            <i class="material-icons">

                payments

            </i>

            Paiements

        </a>

        <!-- PARAMETRES -->

        <a href="parametres.php"
        class="<?php echo ($current_page == 'parametres') ? 'active' : ''; ?>">

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