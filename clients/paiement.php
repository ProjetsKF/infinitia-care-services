<?php require_once("../config/app.php"); ?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(dirname(__DIR__) . "/includes/pwa-head.php"); ?>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Evaluations

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

        $current_page = "paiement";

        include("menucli.php");

        ?>

        <!-- =========================================
             MAIN CONTENT
        ========================================= -->

        <div class="main-content">

        <!-- TOPBAR -->

<div class="topbar">

    <div>

        <div class="page-title">

            <i class="material-icons left"
               style="vertical-align:middle; margin-right:8px;">

                payments

            </i>

            Paiements

        </div>

        <div class="welcome-text">

            Consultez l'historique de vos paiements et suivez l'état de vos transactions.

        </div>

    </div>

</div>
   

          <!-- LISTE DES INTERVENANTS -->
<div class="table-card">

    <div class="table-header">

        <div class="table-title">

            Historique des paiements

        </div>

    </div>

    <div class="table-tools">

    <div class="search-box">

        <i class="material-icons">
            search
        </i>

        <input type="text"
               id="searchPayment"
               placeholder="Rechercher un paiement...">

    </div>

</div>

    <table class="highlight responsive-table">

        <thead>

            <tr>

                <th>Référence</th>
                <th>Mission</th>
                <th>Montant</th>
                <th>Méthode</th>
                <th>Téléphone</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>

            </tr>

        </thead>

        <tbody>

            <tr>

                <td>PAY-001</td>

                <td>Nettoyage jardin</td>

                <td>300.00 USD</td>

                <td>M-Pesa</td>

                <td>243991234567</td>

                <td>28/05/2026</td>

                <td>

                    <span class="status completed">

                        Payé

                    </span>

                </td>

                <td>

                    <a href="#"
                       class="green-text"
                       title="Voir">

                        <i class="material-icons">
                            visibility
                        </i>

                    </a>

                </td>

            </tr>

            <tr>

                <td>PAY-002</td>

                <td>Nettoyage maison</td>

                <td>150.00 USD</td>

                <td>Airtel Money</td>

                <td>243975551234</td>

                <td>04/06/2026</td>

                <td>

                    <span class="status progress">

                        En traitement

                    </span>

                </td>

                <td>

                    <a href="#"
                       class="green-text"
                       title="Voir">

                        <i class="material-icons">
                            visibility
                        </i>

                    </a>

                </td>

            </tr>

            <tr>

                <td>PAY-003</td>

                <td>Repassage</td>

                <td>75.00 USD</td>

                <td>Maxicash</td>

                <td>243820000000</td>

                <td>10/06/2026</td>

                <td>

                    <span class="status pending">

                        En attente

                    </span>

                </td>

                <td>

                    <a href="#"
                       class="green-text"
                       title="Payer">

                        <i class="material-icons">
                            payment
                        </i>

                    </a>

                </td>

            </tr>

        </tbody>

    </table>

</div>

        </div>

    </div>

    <!-- MATERIALIZE JS -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<div id="paymentDetails" class="modal">

    <div class="modal-content">

        <h4>Détails du paiement</h4>

        <p><strong>Référence :</strong> PAY-001</p>

        <p><strong>Mission :</strong> Nettoyage jardin</p>

        <p><strong>Montant :</strong> 300.00 USD</p>

        <p><strong>Méthode :</strong> M-Pesa</p>

        <p><strong>Téléphone :</strong> 243991234567</p>

        <p><strong>Référence transaction :</strong> MP240528001</p>

        <p><strong>Statut :</strong> Payé</p>

        <p><strong>Date :</strong> 28/05/2026 14:25</p>

    </div>

    <div class="modal-footer">

        <a href="#!"
           class="modal-close btn">

            Fermer

        </a>

    </div>

</div>
</body>

</html>
