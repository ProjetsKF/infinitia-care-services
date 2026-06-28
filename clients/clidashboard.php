<!-- =========================================
     CLIENT DASHBOARD
     FICHIER : clidashboard.php
========================================= -->
<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"])){

    header("Location: ../login.php");
    exit();

}

if(!isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 2){

    header("Location: ../login.php");
    exit();

}

$user_id = (int)$_SESSION["user_id"];

$client_id = 0;
$total_demandes = 0;
$demandes_en_attente = 0;
$missions_en_cours = 0;
$missions_terminees = 0;
$dernieres_demandes = array();

$client = array(
    "first_name" => "",
    "last_name" => "",
    "email" => "",
    "phone" => "",
    "profile_photo" => "",
    "status" => "",
    "client_type" => "",
    "company_name" => "",
    "address" => "",
    "city" => "",
    "gps_location" => ""
);

function dashboard_count_query($conn, $sql, $client_id)
{
    $total = 0;
    $stmt = mysqli_prepare($conn, $sql);

    if($stmt){

        mysqli_stmt_bind_param($stmt, "i", $client_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $total);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

    }

    return (int)$total;
}

$sql = "

SELECT

    clients.id,

    users.first_name,
    users.last_name,
    users.email,
    users.phone,
    users.profile_photo,
    users.status,

    clients.client_type,
    clients.company_name,
    clients.address,
    clients.city,
    clients.gps_location

FROM users

INNER JOIN clients
ON users.id = clients.user_id

WHERE users.id = ?

LIMIT 1

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result(
    $stmt,
    $client_id,
    $first_name,
    $last_name,
    $email,
    $phone,
    $profile_photo,
    $status,
    $client_type,
    $company_name,
    $address,
    $city,
    $gps_location
);

if(mysqli_stmt_fetch($stmt)){

    $client["first_name"] = $first_name;
    $client["last_name"] = $last_name;
    $client["email"] = $email;
    $client["phone"] = $phone;
    $client["profile_photo"] = $profile_photo;
    $client["status"] = $status;
    $client["client_type"] = $client_type;
    $client["company_name"] = $company_name;
    $client["address"] = $address;
    $client["city"] = $city;
    $client["gps_location"] = $gps_location;

}

mysqli_stmt_close($stmt);

if($client_id <= 0){

    header("Location: ../login.php");
    exit();

}

/* Statistiques limitees au client connecte. */
$total_demandes = dashboard_count_query(
    $conn,
    "SELECT COUNT(*) FROM service_requests WHERE client_id = ?",
    $client_id
);

$demandes_en_attente = dashboard_count_query(
    $conn,
    "SELECT COUNT(*) FROM service_requests WHERE client_id = ? AND status = 'en_attente'",
    $client_id
);

$missions_en_cours = dashboard_count_query(
    $conn,
    "SELECT COUNT(*)
     FROM missions m
     INNER JOIN service_requests sr
     ON sr.id = m.service_request_id
     WHERE sr.client_id = ?
     AND m.mission_status = 'en_cours'",
    $client_id
);

$missions_terminees = dashboard_count_query(
    $conn,
    "SELECT COUNT(*)
     FROM missions m
     INNER JOIN service_requests sr
     ON sr.id = m.service_request_id
     WHERE sr.client_id = ?
     AND m.mission_status = 'terminee'",
    $client_id
);

$sql = "

SELECT
    id,
    title,
    created_at,
    status
FROM service_requests
WHERE client_id = ?
ORDER BY created_at DESC
LIMIT 5

";

$stmt = mysqli_prepare($conn, $sql);

if($stmt){

    mysqli_stmt_bind_param($stmt, "i", $client_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result(
        $stmt,
        $request_id,
        $request_title,
        $request_created_at,
        $request_status
    );

    while(mysqli_stmt_fetch($stmt)){

        $dernieres_demandes[] = array(
            "id" => $request_id,
            "title" => $request_title,
            "created_at" => $request_created_at,
            "status" => $request_status
        );

    }

    mysqli_stmt_close($stmt);

}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Tableau de bord Client | INFINITIA

    </title>

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

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
       <link rel="stylesheet" href="../assets/css/style.css">

    <!-- ICON -->

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

</head>

<body>

    <div class="dashboard">

       <?php

        $current_page = "dashboard";

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

                        Tableau de Bord

                    </div>

                    <div class="welcome-text">

                        Bienvenue cher(e) <?php echo htmlspecialchars($client['first_name'], ENT_QUOTES, 'UTF-8'); ?> sur votre espace client.

                    </div>

                </div>

            </div>

            <!-- STATS -->

            <div class="row">

                <!-- CARD 1 -->

                <div class="col s12 m6 l3">

                    <div class="dashboard-card">

                        <div class="card-icon blue-gradient">

                            <i class="material-icons">

                                assignment

                            </i>

                        </div>

                        <h5>

                            Demandes envoy&eacute;es

                        </h5>

                        <h3>

                            <?php echo (int)$total_demandes; ?>

                        </h3>

                    </div>

                </div>

                <!-- CARD 2 -->

                <div class="col s12 m6 l3">

                    <div class="dashboard-card">

                        <div class="card-icon gold-gradient">

                            <i class="material-icons">

                                schedule

                            </i>

                        </div>

                        <h5>

                            Demandes en attente

                        </h5>

                        <h3>

                            <?php echo (int)$demandes_en_attente; ?>

                        </h3>

                    </div>

                </div>

                <!-- CARD 3 -->

                <div class="col s12 m6 l3">

                    <div class="dashboard-card">

                        <div class="card-icon pink-gradient">

                            <i class="material-icons">

                                engineering

                            </i>

                        </div>

                        <h5>

                            Missions en cours

                        </h5>

                        <h3>

                            <?php echo (int)$missions_en_cours; ?>

                        </h3>

                    </div>

                </div>

                <!-- CARD 4 -->

                <div class="col s12 m6 l3">

                    <div class="dashboard-card">

                        <div class="card-icon blue-gradient">

                            <i class="material-icons">

                                task_alt

                            </i>

                        </div>

                        <h5>

                            Missions termin&eacute;es

                        </h5>

                        <h3>

                            <?php echo (int)$missions_terminees; ?>

                        </h3>

                    </div>

                </div>

            </div>

            <!-- TABLE -->

            <div class="table-card">

                <div class="table-title">

                    Derni&egrave;res Demandes

                </div>

                <table class="highlight responsive-table">

                    <thead>

                        <tr>

                            <th>R&eacute;f&eacute;rence</th>
                            <th>Service demand&eacute;</th>
                            <th>Date de cr&eacute;ation</th>
                            <th>Statut</th>
                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if(count($dernieres_demandes) > 0){ ?>

                            <?php foreach($dernieres_demandes as $demande){ ?>

                                <?php

                                $status_value = "";
                                $title_value = "";
                                $created_at_value = "";

                                if(isset($demande["status"]) && $demande["status"] !== NULL){

                                    $status_value = $demande["status"];

                                }

                                if(isset($demande["title"]) && $demande["title"] !== NULL){

                                    $title_value = $demande["title"];

                                }

                                if(isset($demande["created_at"]) && $demande["created_at"] !== NULL){

                                    $created_at_value = $demande["created_at"];

                                }

                                $status_class = "pending";

                                if($status_value == "en_cours"){

                                    $status_class = "progress";

                                }

                                if($status_value == "terminee"){

                                    $status_class = "completed";

                                }

                                $status_label = ucfirst(
                                    str_replace(
                                        "_",
                                        " ",
                                        $status_value
                                    )
                                );

                                $created_at_label = "";

                                if($created_at_value !== ""){

                                    $created_at_label = date(
                                        "d/m/Y",
                                        strtotime($created_at_value)
                                    );

                                }

                                ?>

                                <tr>

                                    <td>

                                        #<?php echo str_pad((int)$demande["id"], 3, "0", STR_PAD_LEFT); ?>

                                    </td>

                                    <td>

                                        <?php echo htmlspecialchars($title_value, ENT_QUOTES, 'UTF-8'); ?>

                                    </td>

                                    <td>

                                        <?php echo htmlspecialchars($created_at_label, ENT_QUOTES, 'UTF-8'); ?>

                                    </td>

                                    <td>

                                        <span class="status <?php echo $status_class; ?>">

                                            <?php echo htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8'); ?>

                                        </span>

                                    </td>

                                    <td>

                                        <a href="mes-demandes.php"
                                           class="green-text"
                                           title="Voir">

                                            <i class="material-icons">

                                                visibility

                                            </i>

                                        </a>

                                    </td>

                                </tr>

                            <?php } ?>

                        <?php }else{ ?>

                            <tr>

                                <td colspan="5" class="center-align">

                                    Aucune demande n'a encore &eacute;t&eacute; cr&eacute;&eacute;e.

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- MATERIALIZE JS -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

</body>

</html>
