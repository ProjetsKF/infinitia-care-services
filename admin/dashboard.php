<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1){

    header("Location: " . app_url("login"));
    exit();

}

function safe_text($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function scalar_count($conn, $sql)
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

function fetch_group_counts($conn, $sql, $key_name, $value_name)
{
    $items = array();
    $result = mysqli_query($conn, $sql);

    if($result){

        while($row = mysqli_fetch_assoc($result)){

            $key = "";
            $value = 0;

            if(isset($row[$key_name]) && $row[$key_name] !== NULL){

                $key = $row[$key_name];

            }

            if(isset($row[$value_name]) && $row[$value_name] !== NULL){

                $value = (int)$row[$value_name];

            }

            $items[$key] = $value;

        }

        mysqli_free_result($result);

    }

    return $items;
}

function json_data($value)
{
    return json_encode($value);
}

$stats = array(
    "clients" => scalar_count($conn, "SELECT COUNT(*) AS total FROM users WHERE role_id = 2"),
    "intervenants" => scalar_count($conn, "SELECT COUNT(*) AS total FROM users WHERE role_id = 3"),
    "demandes_attente" => scalar_count($conn, "SELECT COUNT(*) AS total FROM service_requests WHERE status = 'en_attente'"),
    "paiements_attente" => scalar_count($conn, "SELECT COUNT(*) AS total FROM payments WHERE status = 'en_attente'"),
    "missions_cours" => scalar_count($conn, "SELECT COUNT(*) AS total FROM missions WHERE mission_status = 'en_cours'"),
    "missions_terminees" => scalar_count($conn, "SELECT COUNT(*) AS total FROM missions WHERE mission_status = 'terminee'"),
    "evaluations" => scalar_count($conn, "SELECT COUNT(*) AS total FROM service_reviews")
);

$month_labels_fr = array(
    "Jan",
    "Fev",
    "Mar",
    "Avr",
    "Mai",
    "Juin",
    "Juil",
    "Aout",
    "Sep",
    "Oct",
    "Nov",
    "Dec"
);

$demandes_month_labels = array();
$demandes_month_keys = array();
$demandes_month_values = array();

for($i = 11; $i >= 0; $i--){

    $timestamp = strtotime("-" . $i . " months");
    $month_key = date("Y-m", $timestamp);
    $month_number = (int)date("n", $timestamp);
    $month_label = $month_labels_fr[$month_number - 1] . " " . date("Y", $timestamp);

    $demandes_month_keys[] = $month_key;
    $demandes_month_labels[] = $month_label;
    $demandes_month_values[$month_key] = 0;

}

$sql = "
SELECT
    DATE_FORMAT(created_at, '%Y-%m') AS mois,
    COUNT(*) AS total
FROM service_requests
WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01')
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
";

$monthly_counts = fetch_group_counts($conn, $sql, "mois", "total");

foreach($monthly_counts as $month_key => $total){

    if(isset($demandes_month_values[$month_key])){

        $demandes_month_values[$month_key] = (int)$total;

    }

}

$demandes_month_data = array();

foreach($demandes_month_keys as $month_key){

    $demandes_month_data[] = (int)$demandes_month_values[$month_key];

}

$mission_statuses = array(
    "en_attente" => "En attente",
    "affectee" => "Affectee",
    "en_cours" => "En cours",
    "terminee" => "Terminee"
);

$payment_statuses = array(
    "en_attente" => "En attente",
    "en_traitement" => "En traitement",
    "paye" => "Paye",
    "echoue" => "Echoue"
);

$mission_counts_raw = fetch_group_counts(
    $conn,
    "SELECT mission_status, COUNT(*) AS total FROM missions GROUP BY mission_status",
    "mission_status",
    "total"
);

$payment_counts_raw = fetch_group_counts(
    $conn,
    "SELECT status, COUNT(*) AS total FROM payments GROUP BY status",
    "status",
    "total"
);

$mission_labels = array();
$mission_data = array();
$mission_total = 0;

foreach($mission_statuses as $status_key => $status_label){

    $count = 0;

    if(isset($mission_counts_raw[$status_key])){

        $count = (int)$mission_counts_raw[$status_key];

    }

    $mission_total = $mission_total + $count;
    $mission_labels[] = $status_label;
    $mission_data[] = $count;

}

$payment_labels = array();
$payment_data = array();
$payment_total = 0;

foreach($payment_statuses as $status_key => $status_label){

    $count = 0;

    if(isset($payment_counts_raw[$status_key])){

        $count = (int)$payment_counts_raw[$status_key];

    }

    $payment_total = $payment_total + $count;
    $payment_labels[] = $status_label;
    $payment_data[] = $count;

}

$user_distribution_labels = array("Clients", "Intervenants");
$user_distribution_data = array(
    (int)$stats["clients"],
    (int)$stats["intervenants"]
);

$review_counts_raw = fetch_group_counts(
    $conn,
    "SELECT note_generale, COUNT(*) AS total FROM service_reviews GROUP BY note_generale",
    "note_generale",
    "total"
);

$review_labels = array("5 etoiles", "4 etoiles", "3 etoiles", "2 etoiles", "1 etoile");
$review_data = array();

for($note = 5; $note >= 1; $note--){

    $count = 0;
    $note_key = (string)$note;

    if(isset($review_counts_raw[$note_key])){

        $count = (int)$review_counts_raw[$note_key];

    }

    $review_data[] = $count;

}

$top_services_labels = array();
$top_services_data = array();

$sql = "
SELECT
    sc.name AS category_name,
    COUNT(sr.id) AS total
FROM service_requests sr
INNER JOIN service_categories sc
ON sc.id = sr.category_id
GROUP BY sc.id, sc.name
ORDER BY total DESC
LIMIT 6
";

$result = mysqli_query($conn, $sql);

if($result){

    while($row = mysqli_fetch_assoc($result)){

        $service_name = "Service";
        $service_total = 0;

        if(isset($row["category_name"]) && $row["category_name"] !== NULL && $row["category_name"] != ""){

            $service_name = $row["category_name"];

        }

        if(isset($row["total"]) && $row["total"] !== NULL){

            $service_total = (int)$row["total"];

        }

        $top_services_labels[] = $service_name;
        $top_services_data[] = $service_total;

    }

    mysqli_free_result($result);

}

if(count($top_services_labels) == 0){

    $top_services_labels[] = "Aucun service";
    $top_services_data[] = 0;

}

$today_label = date("d/m/Y");

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(dirname(__DIR__) . "/includes/pwa-head.php"); ?>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>
        Tableau de bord Admin | INFINITIA
    </title>

    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <link rel="preconnect"
    href="https://fonts.googleapis.com">

    <link rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

    <style>

.admin-summary-card{

    background:#ffffff;

    border-radius:14px;

    padding:16px 18px;

    min-height:92px;

    box-shadow:0 8px 22px rgba(0,0,0,.08);

}

.summary-wrapper{

    display:flex;

    align-items:center;

    gap:16px;

}

.summary-content{

    min-width:0;

}

.summary-content h5{

    color:#2f3b55;

    font-size:15px;

    font-weight:600;

    margin:0 0 8px 0;

    line-height:1.25;

}

.summary-line{

    display:flex;

    align-items:baseline;

    gap:10px;

    flex-wrap:wrap;

}

.summary-value{

    color:#081f78;

    font-size:28px;

    font-weight:800;

    line-height:1;

}

.summary-info{

    color:#8b95a7;

    font-size:13px;

    line-height:1.2;

}

.card-icon{

    width:56px;

    height:56px;

    border-radius:14px;

    display:flex;

    align-items:center;

    justify-content:center;

    flex:0 0 56px;

}

.card-icon i{

    color:#ffffff;

    font-size:30px;

}

.admin-chart-card{

    background:#ffffff;

    border-radius:18px;

    padding:22px;

    box-shadow:0 8px 22px rgba(0,0,0,.08);

    margin-bottom:24px;

}

.admin-chart-title{

    color:#081f78;

    font-size:20px;

    font-weight:700;

    margin-bottom:18px;

}

.chart-box{

    position:relative;

    height:300px;

}

.chart-box.small{

    height:260px;

}

.topbar-date{

    color:#081f78;

    font-weight:600;

    background:#ffffff;

    border-radius:12px;

    padding:12px 16px;

    box-shadow:0 4px 12px rgba(0,0,0,.06);

}

</style>

</head>

<body class="admin-module">

    <div class="dashboard">

        <?php

        $current_page = "dashboard";

        include("menuadmin.php");

        ?>

        <div class="main-content">

            <!-- TOPBAR -->

            <div class="topbar" style="display:flex; justify-content:space-between; gap:20px; align-items:center;">

                <div>

                    <div class="page-title">

                        Tableau de bord

                    </div>

                    <div class="welcome-text">

                        Vue d'ensemble de l'activite de la plateforme INFINITIA Care Services.

                    </div>

                </div>

                <div class="topbar-date">
                    <?php echo safe_text($today_label); ?>
                </div>

            </div>

            <!-- STATS -->

            <div class="row intervenant-stat-grid admin-stat-grid">

                <div class="col s12 m6 l3">

                    <div class="admin-summary-card">

                        <div class="summary-wrapper">

                            <div class="card-icon blue-gradient">
                                <i class="material-icons">group</i>
                            </div>

                            <div class="summary-content">
                                <h5>Clients inscrits</h5>
                                <div class="summary-line">
                                    <span class="summary-value"><?php echo (int)$stats["clients"]; ?></span>
                                    <span class="summary-info">donnees actuelles</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="col s12 m6 l3">

                    <div class="admin-summary-card">

                        <div class="summary-wrapper">

                            <div class="card-icon pink-gradient">
                                <i class="material-icons">engineering</i>
                            </div>

                            <div class="summary-content">
                                <h5>Intervenants</h5>
                                <div class="summary-line">
                                    <span class="summary-value"><?php echo (int)$stats["intervenants"]; ?></span>
                                    <span class="summary-info">donnees actuelles</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="col s12 m6 l3">

                    <div class="admin-summary-card">

                        <div class="summary-wrapper">

                            <div class="card-icon gold-gradient">
                                <i class="material-icons">pending_actions</i>
                            </div>

                            <div class="summary-content">
                                <h5>Demandes en attente</h5>
                                <div class="summary-line">
                                    <span class="summary-value"><?php echo (int)$stats["demandes_attente"]; ?></span>
                                    <span class="summary-info">donnees actuelles</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="col s12 m6 l3">

                    <div class="admin-summary-card">

                        <div class="summary-wrapper">

                            <div class="card-icon blue-gradient">
                                <i class="material-icons">payments</i>
                            </div>

                            <div class="summary-content">
                                <h5>Paiements en attente</h5>
                                <div class="summary-line">
                                    <span class="summary-value"><?php echo (int)$stats["paiements_attente"]; ?></span>
                                    <span class="summary-info">donnees actuelles</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="row intervenant-stat-grid admin-stat-grid">

                <div class="col s12 m6 l4">

                    <div class="admin-summary-card">

                        <div class="summary-wrapper">

                            <div class="card-icon blue-gradient">
                                <i class="material-icons">work</i>
                            </div>

                            <div class="summary-content">
                                <h5>Missions en cours</h5>
                                <div class="summary-line">
                                    <span class="summary-value"><?php echo (int)$stats["missions_cours"]; ?></span>
                                    <span class="summary-info">donnees actuelles</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="col s12 m6 l4">

                    <div class="admin-summary-card">

                        <div class="summary-wrapper">

                            <div class="card-icon pink-gradient">
                                <i class="material-icons">task_alt</i>
                            </div>

                            <div class="summary-content">
                                <h5>Missions terminees</h5>
                                <div class="summary-line">
                                    <span class="summary-value"><?php echo (int)$stats["missions_terminees"]; ?></span>
                                    <span class="summary-info">donnees actuelles</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="col s12 m12 l4">

                    <div class="admin-summary-card">

                        <div class="summary-wrapper">

                            <div class="card-icon gold-gradient">
                                <i class="material-icons">star</i>
                            </div>

                            <div class="summary-content">
                                <h5>Evaluations recues</h5>
                                <div class="summary-line">
                                    <span class="summary-value"><?php echo (int)$stats["evaluations"]; ?></span>
                                    <span class="summary-info">donnees actuelles</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- CHARTS -->

            <div class="row">

                <div class="col s12">

                    <div class="admin-chart-card">

                        <div class="admin-chart-title">
                            Evolution des demandes (12 derniers mois)
                        </div>

                        <div class="chart-box">
                            <canvas id="requestsChart"></canvas>
                        </div>

                    </div>

                </div>

            </div>

            <div class="row">

                <div class="col s12 l6">

                    <div class="admin-chart-card">

                        <div class="admin-chart-title">
                            Repartition des missions
                        </div>

                        <div class="chart-box small">
                            <canvas id="missionsChart"></canvas>
                        </div>

                    </div>

                </div>

                <div class="col s12 l6">

                    <div class="admin-chart-card">

                        <div class="admin-chart-title">
                            Repartition des paiements
                        </div>

                        <div class="chart-box small">
                            <canvas id="paymentsChart"></canvas>
                        </div>

                    </div>

                </div>

            </div>

            <div class="row">

                <div class="col s12 l6">

                    <div class="admin-chart-card">

                        <div class="admin-chart-title">
                            Repartition des utilisateurs
                        </div>

                        <div class="chart-box small">
                            <canvas id="usersChart"></canvas>
                        </div>

                    </div>

                </div>

                <div class="col s12 l6">

                    <div class="admin-chart-card">

                        <div class="admin-chart-title">
                            Evaluations par note
                        </div>

                        <div class="chart-box small">
                            <canvas id="reviewsChart"></canvas>
                        </div>

                    </div>

                </div>

            </div>

            <div class="row">

                <div class="col s12">

                    <div class="admin-chart-card">

                        <div class="admin-chart-title">
                            Top des services demandes
                        </div>

                        <div class="chart-box">
                            <canvas id="servicesChart"></canvas>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>

    var chartColors = {
        blue: '#081f78',
        pink: '#e83e8c',
        gold: '#d89b2b',
        green: '#2e7d32',
        orange: '#f39c12',
        red: '#c62828',
        grey: '#8e8e8e',
        lightBlue: '#42a5f5'
    };

    function buildPercentLabels(labels, data)
    {
        var total = 0;
        var i;

        for(i = 0; i < data.length; i++){
            total += Number(data[i]);
        }

        var output = [];

        for(i = 0; i < labels.length; i++){
            var value = Number(data[i]);
            var percent = 0;

            if(total > 0){
                percent = Math.round((value / total) * 100);
            }

            output.push(labels[i] + ' : ' + value + ' (' + percent + '%)');
        }

        return output;
    }

    var requestLabels = <?php echo json_data($demandes_month_labels); ?>;
    var requestData = <?php echo json_data($demandes_month_data); ?>;
    var missionLabelsBase = <?php echo json_data($mission_labels); ?>;
    var missionData = <?php echo json_data($mission_data); ?>;
    var paymentLabelsBase = <?php echo json_data($payment_labels); ?>;
    var paymentData = <?php echo json_data($payment_data); ?>;
    var userLabels = <?php echo json_data($user_distribution_labels); ?>;
    var userData = <?php echo json_data($user_distribution_data); ?>;
    var reviewLabels = <?php echo json_data($review_labels); ?>;
    var reviewData = <?php echo json_data($review_data); ?>;
    var serviceLabels = <?php echo json_data($top_services_labels); ?>;
    var serviceData = <?php echo json_data($top_services_data); ?>;

    var missionLabels = buildPercentLabels(missionLabelsBase, missionData);
    var paymentLabels = buildPercentLabels(paymentLabelsBase, paymentData);

    new Chart(document.getElementById('requestsChart'), {
        type: 'line',
        data: {
            labels: requestLabels,
            datasets: [{
                label: 'Demandes',
                data: requestData,
                borderColor: chartColors.blue,
                backgroundColor: 'rgba(8,31,120,0.12)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('missionsChart'), {
        type: 'doughnut',
        data: {
            labels: missionLabels,
            datasets: [{
                data: missionData,
                backgroundColor: [
                    chartColors.orange,
                    chartColors.lightBlue,
                    chartColors.green,
                    chartColors.grey
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(document.getElementById('paymentsChart'), {
        type: 'doughnut',
        data: {
            labels: paymentLabels,
            datasets: [{
                data: paymentData,
                backgroundColor: [
                    chartColors.orange,
                    chartColors.lightBlue,
                    chartColors.green,
                    chartColors.red
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(document.getElementById('usersChart'), {
        type: 'bar',
        data: {
            labels: userLabels,
            datasets: [{
                label: 'Utilisateurs',
                data: userData,
                backgroundColor: [
                    chartColors.blue,
                    chartColors.pink
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('reviewsChart'), {
        type: 'bar',
        data: {
            labels: reviewLabels,
            datasets: [{
                label: 'Evaluations',
                data: reviewData,
                backgroundColor: chartColors.gold
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('servicesChart'), {
        type: 'bar',
        data: {
            labels: serviceLabels,
            datasets: [{
                label: 'Demandes',
                data: serviceData,
                backgroundColor: chartColors.blue
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    </script>

</body>

</html>
