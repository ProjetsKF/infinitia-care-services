<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"])){

    header("Location: " . app_url("login"));
    exit();

}

if(!isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 2){

    header("Location: " . app_url("login"));
    exit();

}

$user_id = (int)$_SESSION["user_id"];
$client_id = 0;
$payments = array();
$total_a_payer = 0;
$paiements_en_attente = 0;
$paiements_en_traitement = 0;
$paiements_payes = 0;

function safe_text($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function array_value($row, $key)
{
    if(isset($row[$key]) && $row[$key] !== NULL){

        return $row[$key];

    }

    return "";
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

function status_badge_class($status)
{
    if($status == "en_attente"){

        return "orange";

    }

    if($status == "en_traitement"){

        return "blue";

    }

    if($status == "paye"){

        return "green";

    }

    if($status == "echoue"){

        return "red";

    }

    return "grey";
}

function status_label($status)
{
    if($status == "en_attente"){

        return "En attente";

    }

    if($status == "en_traitement"){

        return "En traitement";

    }

    if($status == "paye"){

        return "Paye";

    }

    if($status == "echoue"){

        return "Echoue";

    }

    if($status == "annule"){

        return "Annule";

    }

    return "Non renseigne";
}

function method_label($method)
{
    if($method == "maxicash"){

        return "Maxicash";

    }

    if($method == "mpesa"){

        return "M-Pesa";

    }

    if($method == "airtel_money"){

        return "Airtel Money";

    }

    if($method == "orange_money"){

        return "Orange Money";

    }

    if($method == "especes"){

        return "Especes";

    }

    return "Non renseigne";
}

function money_label($amount, $currency)
{
    if($amount === NULL || $amount === ""){

        return "0.00 " . display_value($currency);

    }

    return number_format((float)$amount, 2) . " " . display_value($currency);
}

function generate_transaction_reference($payment_id)
{
    return "ICS-" . date("YmdHis") . "-" . str_pad((int)$payment_id, 6, "0", STR_PAD_LEFT);
}

$sql = "

SELECT id
FROM clients
WHERE user_id = ?
LIMIT 1

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $client_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if($client_id <= 0){

    header("Location: " . app_url("login"));
    exit();

}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

    if($action == "init_payment"){

        $payment_id = isset($_POST["payment_id"])
            ? (int)$_POST["payment_id"]
            : 0;

        $payment_method = isset($_POST["payment_method"])
            ? trim($_POST["payment_method"])
            : "";

        $phone_number = isset($_POST["phone_number"])
            ? trim($_POST["phone_number"])
            : "";

        $allowed_methods = array(
            "maxicash",
            "mpesa",
            "airtel_money",
            "orange_money"
        );

        if($payment_id <= 0 || !in_array($payment_method, $allowed_methods) || $phone_number == ""){

            $_SESSION["error"] = "Veuillez renseigner une methode de paiement et un numero valide.";
            header("Location: " . app_url("client/paiements"));
            exit();

        }

        $current_status = "";
        $current_reference = "";

        $sql = "

        SELECT
            p.status,
            p.transaction_reference
        FROM payments p
        INNER JOIN missions m
        ON m.id = p.mission_id
        INNER JOIN service_requests sr
        ON sr.id = m.service_request_id
        WHERE p.id = ?
        AND p.client_id = ?
        AND sr.client_id = ?
        LIMIT 1

        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $payment_id,
            $client_id,
            $client_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result(
            $stmt,
            $current_status,
            $current_reference
        );

        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if($current_status != "en_attente" && $current_status != "echoue"){

            $_SESSION["error"] = "Ce paiement ne peut pas etre initie.";
            header("Location: " . app_url("client/paiements"));
            exit();

        }

        $transaction_reference = $current_reference;

        if($transaction_reference === NULL || $transaction_reference == ""){

            $transaction_reference = generate_transaction_reference($payment_id);

        }

        /*
         * Integration Mobile Money future :
         * - appeler ici l'API du fournisseur selectionne ;
         * - envoyer le montant, la devise, le numero et la reference interne ;
         * - stocker l'identifiant externe retourne dans external_transaction_id ;
         * - ne passer le paiement a "paye" qu'apres confirmation API/callback.
         */

        $sql = "

        UPDATE payments
        SET
            payment_method = ?,
            phone_number = ?,
            transaction_reference = ?,
            status = 'en_traitement'
        WHERE id = ?
        AND client_id = ?
        AND status IN ('en_attente', 'echoue')

        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssii",
            $payment_method,
            $phone_number,
            $transaction_reference,
            $payment_id,
            $client_id
        );

        if(mysqli_stmt_execute($stmt)){

            $_SESSION["success"] = "Le paiement a ete initie et est en cours de traitement.";

        }else{

            $_SESSION["error"] = "Erreur lors de l'initialisation du paiement.";

        }

        mysqli_stmt_close($stmt);

        header("Location: " . app_url("client/paiements"));
        exit();

    }

}

$sql = "

SELECT
    p.id,
    p.mission_id,
    p.amount,
    p.currency,
    p.payment_method,
    p.transaction_reference,
    p.phone_number,
    p.status,
    p.paid_at,
    p.created_at,

    sr.title,
    sr.service_date,

    u.first_name,
    u.last_name

FROM payments p

INNER JOIN missions m
ON m.id = p.mission_id

INNER JOIN service_requests sr
ON sr.id = m.service_request_id

INNER JOIN candidates c
ON c.id = m.candidate_id

INNER JOIN users u
ON u.id = c.user_id

WHERE p.client_id = ?
AND sr.client_id = ?
AND m.mission_status IN ('en_cours', 'terminee')

ORDER BY p.created_at DESC

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $client_id,
    $client_id
);

mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result(
    $stmt,
    $payment_id,
    $mission_id,
    $amount,
    $currency,
    $payment_method,
    $transaction_reference,
    $phone_number,
    $status,
    $paid_at,
    $created_at,
    $title,
    $service_date,
    $first_name,
    $last_name
);

while(mysqli_stmt_fetch($stmt)){

    $payments[] = array(
        "id" => $payment_id,
        "mission_id" => $mission_id,
        "amount" => $amount,
        "currency" => $currency,
        "payment_method" => $payment_method,
        "transaction_reference" => $transaction_reference,
        "phone_number" => $phone_number,
        "status" => $status,
        "paid_at" => $paid_at,
        "created_at" => $created_at,
        "title" => $title,
        "service_date" => $service_date,
        "first_name" => $first_name,
        "last_name" => $last_name
    );

    if($status == "en_attente" || $status == "echoue"){

        $total_a_payer = $total_a_payer + (float)$amount;

    }

    if($status == "en_attente"){

        $paiements_en_attente++;

    }

    if($status == "en_traitement"){

        $paiements_en_traitement++;

    }

    if($status == "paye"){

        $paiements_payes++;

    }

}

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Paiements | INFINITIA

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

</head>

<body>

    <div class="dashboard">

       <?php

        $current_page = "paiements";

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

                        Consultez vos paiements mensuels et initiez un paiement Mobile Money.

                    </div>

                </div>

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

            <!-- STATISTIQUES -->

            <div class="row">

                <div class="col s12 m6 l3">

                    <div class="dashboard-card">

                        <div class="card-icon gold-gradient">

                            <i class="material-icons">
                                account_balance_wallet
                            </i>

                        </div>

                        <h5>Total a payer</h5>

                        <h3>
                            <?php echo safe_text(number_format((float)$total_a_payer, 2)); ?>
                        </h3>

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

                        <h3>
                            <?php echo (int)$paiements_en_attente; ?>
                        </h3>

                    </div>

                </div>

                <div class="col s12 m6 l3">

                    <div class="dashboard-card">

                        <div class="card-icon blue-gradient">

                            <i class="material-icons">
                                sync
                            </i>

                        </div>

                        <h5>En traitement</h5>

                        <h3>
                            <?php echo (int)$paiements_en_traitement; ?>
                        </h3>

                    </div>

                </div>

                <div class="col s12 m6 l3">

                    <div class="dashboard-card">

                        <div class="card-icon pink-gradient">

                            <i class="material-icons">
                                check_circle
                            </i>

                        </div>

                        <h5>Payes</h5>

                        <h3>
                            <?php echo (int)$paiements_payes; ?>
                        </h3>

                    </div>

                </div>

            </div>

            <?php if(count($payments) > 0){ ?>

                <div class="table-card">

                    <div class="table-header">

                        <div class="table-title">

                            Historique des paiements

                        </div>

                    </div>

                    <table class="highlight responsive-table">

                        <thead>

                            <tr>

                                <th>Service</th>
                                <th>Intervenant</th>
                                <th>Montant</th>
                                <th>Methode</th>
                                <th>Numero</th>
                                <th>Reference</th>
                                <th>Statut</th>
                                <th>Date paiement</th>
                                <th>Date creation</th>
                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach($payments as $payment){ ?>

                                <?php

                                $payment_id_value = (int)array_value($payment, "id");
                                $mission_id_value = (int)array_value($payment, "mission_id");
                                $amount_value = array_value($payment, "amount");
                                $currency_value = array_value($payment, "currency");
                                $method_value = array_value($payment, "payment_method");
                                $phone_value = array_value($payment, "phone_number");
                                $reference_value = array_value($payment, "transaction_reference");
                                $status_value = array_value($payment, "status");
                                $paid_at_value = array_value($payment, "paid_at");
                                $created_at_value = array_value($payment, "created_at");
                                $title_value = array_value($payment, "title");
                                $first_name_value = array_value($payment, "first_name");
                                $last_name_value = array_value($payment, "last_name");
                                $full_name_value = trim($first_name_value . " " . $last_name_value);

                                if($full_name_value == ""){

                                    $full_name_value = "Intervenant";

                                }

                                ?>

                                <tr>

                                    <td>
                                        <?php echo safe_text(display_value($title_value)); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text($full_name_value); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(money_label($amount_value, $currency_value)); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(method_label($method_value)); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(display_value($phone_value)); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(display_value($reference_value)); ?>
                                    </td>

                                    <td>

                                        <span class="new badge <?php echo status_badge_class($status_value); ?>"
                                              data-badge-caption="">
                                            <?php echo safe_text(status_label($status_value)); ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?php echo safe_text(format_date_fr($paid_at_value)); ?>
                                    </td>

                                    <td>
                                        <?php echo safe_text(format_date_fr($created_at_value)); ?>
                                    </td>

                                    <td>

                                        <?php if($status_value == "en_attente" || $status_value == "echoue"){ ?>

                                            <a href="#payment<?php echo $payment_id_value; ?>"
                                               class="btn-small waves-effect waves-light modal-trigger">
                                                Payer
                                            </a>

                                        <?php }elseif($status_value == "en_traitement"){ ?>

                                            <span class="blue-text">
                                                Paiement en cours
                                            </span>

                                        <?php }elseif($status_value == "paye"){ ?>

                                            <span class="green-text">
                                                Paye
                                            </span>

                                        <?php }else{ ?>

                                            <span class="grey-text">
                                                Indisponible
                                            </span>

                                        <?php } ?>

                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

                <?php foreach($payments as $payment){ ?>

                    <?php

                    $payment_id_value = (int)array_value($payment, "id");
                    $mission_id_value = (int)array_value($payment, "mission_id");
                    $amount_value = array_value($payment, "amount");
                    $currency_value = array_value($payment, "currency");
                    $title_value = array_value($payment, "title");
                    $first_name_value = array_value($payment, "first_name");
                    $last_name_value = array_value($payment, "last_name");
                    $full_name_value = trim($first_name_value . " " . $last_name_value);

                    if($full_name_value == ""){

                        $full_name_value = "Intervenant";

                    }

                    ?>

                    <div id="payment<?php echo $payment_id_value; ?>"
                         class="modal modal-fixed-footer">

                        <form action="<?php echo app_url_html("client/paiements"); ?>" method="POST">

                            <input type="hidden"
                                   name="action"
                                   value="init_payment">

                            <input type="hidden"
                                   name="payment_id"
                                   value="<?php echo $payment_id_value; ?>">

                            <div class="modal-content">

                                <h4>
                                    Initier le paiement
                                </h4>

                                <p>
                                    <strong>Mission :</strong>
                                    MIS-<?php echo str_pad($mission_id_value, 3, "0", STR_PAD_LEFT); ?>
                                </p>

                                <p>
                                    <strong>Service :</strong>
                                    <?php echo safe_text(display_value($title_value)); ?>
                                </p>

                                <p>
                                    <strong>Intervenant :</strong>
                                    <?php echo safe_text($full_name_value); ?>
                                </p>

                                <p>
                                    <strong>Montant :</strong>
                                    <?php echo safe_text(money_label($amount_value, $currency_value)); ?>
                                </p>

                                <p>
                                    <strong>Devise :</strong>
                                    <?php echo safe_text(display_value($currency_value)); ?>
                                </p>

                                <div class="input-field">

                                    <select name="payment_method" required>

                                        <option value="" disabled selected>
                                            Choisir une methode
                                        </option>

                                        <option value="maxicash">
                                            Maxicash
                                        </option>

                                        <option value="mpesa">
                                            M-Pesa
                                        </option>

                                        <option value="airtel_money">
                                            Airtel Money
                                        </option>

                                        <option value="orange_money">
                                            Orange Money
                                        </option>

                                    </select>

                                    <label>Methode de paiement</label>

                                </div>

                                <div class="input-field">

                                    <input type="text"
                                           name="phone_number"
                                           id="phone_number<?php echo $payment_id_value; ?>"
                                           required>

                                    <label for="phone_number<?php echo $payment_id_value; ?>">
                                        Numero Mobile Money
                                    </label>

                                </div>

                            </div>

                            <div class="modal-footer">

                                <a href="#!"
                                   class="modal-close btn-flat">
                                    Annuler
                                </a>

                                <button type="submit"
                                        class="btn waves-effect waves-light">
                                    Lancer le paiement
                                </button>

                            </div>

                        </form>

                    </div>

                <?php } ?>

            <?php }else{ ?>

                <div class="card">

                    <div class="card-content center">

                        <i class="material-icons large blue-text text-darken-4">
                            payments
                        </i>

                        <h5>
                            Aucun paiement n'est disponible pour le moment.
                        </h5>

                        <p class="grey-text text-darken-1">
                            Vos paiements mensuels apparaitront ici des qu'une mission en cours sera facturee.
                        </p>

                    </div>

                </div>

            <?php } ?>

        </div>

    </div>

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

        M.updateTextFields();

    });

    </script>

</body>

</html>
