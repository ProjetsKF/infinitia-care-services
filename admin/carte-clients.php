<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 1){

    header("Location: " . app_url("login"));
    exit();

}

function map_safe_text($value)
{
    if($value === NULL){

        return "";

    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function map_get_filter($name, $max_length)
{
    if(!isset($_GET[$name]) || is_array($_GET[$name])){

        return "";

    }

    $value = trim((string)$_GET[$name]);

    if(strlen($value) > $max_length){

        $value = substr($value, 0, $max_length);

    }

    return $value;
}

function map_bind_params($stmt, $types, &$params)
{
    if($types === ""){

        return true;

    }

    $arguments = array($types);
    $index = 0;

    for($index = 0; $index < count($params); $index++){

        $arguments[] = &$params[$index];

    }

    return call_user_func_array(array($stmt, "bind_param"), $arguments);
}

function map_client_type_label($type)
{
    if($type === "company"){

        return "Entreprise";

    }

    if($type === "expatriate"){

        return "Expatrié";

    }

    return "Particulier";
}

function map_pagination_url($page, $client_name, $city, $client_type)
{
    $params = array(
        "client_name" => $client_name,
        "city" => $city,
        "client_type" => $client_type,
        "page" => (int)$page
    );

    return app_url_with_query("admin/localisation-clients", $params);
}

$client_name = map_get_filter("client_name", 150);
$city = map_get_filter("city", 100);
$client_type = map_get_filter("client_type", 20);
$allowed_types = array("individual", "company", "expatriate");

if($client_type !== "" && !in_array($client_type, $allowed_types, true)){

    $client_type = "";

}

$cities = array();
$cities_sql = "
SELECT DISTINCT city
FROM clients
WHERE city IS NOT NULL
AND city <> ''
ORDER BY city ASC
";
$cities_stmt = mysqli_prepare($conn, $cities_sql);

if($cities_stmt){

    mysqli_stmt_execute($cities_stmt);
    mysqli_stmt_bind_result($cities_stmt, $city_result);

    while(mysqli_stmt_fetch($cities_stmt)){

        $cities[] = $city_result;

    }

    mysqli_stmt_close($cities_stmt);

}

$conditions = array(
    "c.gps_location IS NOT NULL",
    "TRIM(c.gps_location) <> ''"
);
$types = "";
$params = array();

if($client_name !== ""){

    $conditions[] = "(
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR CONCAT_WS(' ', u.first_name, u.last_name) LIKE ?
        OR c.company_name LIKE ?
    )";
    $name_search = "%" . $client_name . "%";
    $types .= "ssss";
    $params[] = $name_search;
    $params[] = $name_search;
    $params[] = $name_search;
    $params[] = $name_search;

}

if($city !== ""){

    $conditions[] = "c.city = ?";
    $types .= "s";
    $params[] = $city;

}

if($client_type !== ""){

    $conditions[] = "c.client_type = ?";
    $types .= "s";
    $params[] = $client_type;

}

$sql = "
SELECT
    c.id,
    c.client_type,
    c.company_name,
    c.address,
    c.city,
    c.gps_location,
    c.created_at,
    u.first_name,
    u.last_name,
    u.email,
    u.phone
FROM clients c
INNER JOIN users u
ON u.id = c.user_id
WHERE " . implode(" AND ", $conditions) . "
ORDER BY c.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
$clients_found = 0;
$invalid_positions = 0;
$clients = array();
$map_clients = array();
$query_error = false;

if(!$stmt){

    $query_error = true;

}else{

    if(!map_bind_params($stmt, $types, $params) || !mysqli_stmt_execute($stmt)){

        $query_error = true;

    }else{

        mysqli_stmt_bind_result(
            $stmt,
            $result_id,
            $result_client_type,
            $result_company_name,
            $result_address,
            $result_city,
            $result_gps_location,
            $result_created_at,
            $result_first_name,
            $result_last_name,
            $result_email,
            $result_phone
        );

        while(mysqli_stmt_fetch($stmt)){

            $clients_found++;
            $parts = explode(",", $result_gps_location);

            if(count($parts) !== 2){

                $invalid_positions++;
                continue;

            }

            $latitude_text = trim($parts[0]);
            $longitude_text = trim($parts[1]);

            if(!is_numeric($latitude_text) || !is_numeric($longitude_text)){

                $invalid_positions++;
                continue;

            }

            $latitude = floatval($latitude_text);
            $longitude = floatval($longitude_text);

            if($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180){

                $invalid_positions++;
                continue;

            }

            $full_name = trim($result_first_name . " " . $result_last_name);
            $display_name = $full_name;

            if($result_client_type === "company" && trim((string)$result_company_name) !== ""){

                $display_name = trim($result_company_name);

            }

            $client = array(
                "id" => (int)$result_id,
                "name" => $display_name,
                "type" => map_client_type_label($result_client_type),
                "city" => $result_city,
                "address" => $result_address,
                "latitude" => $latitude,
                "longitude" => $longitude,
                "email" => $result_email,
                "phone" => $result_phone
            );

            $clients[] = $client;
            $map_clients[] = $client;

        }

    }

    mysqli_stmt_close($stmt);

}

$displayed_positions = count($clients);
$per_page = 20;
$total_clients = count($clients);
$total_pages = max(1, (int)ceil($total_clients / $per_page));
$page = 1;

if(isset($_GET["page"]) && !is_array($_GET["page"])){

    $page = (int)$_GET["page"];

}

if($page < 1){

    $page = 1;

}

if($page > $total_pages){

    $page = $total_pages;

}

$offset = ($page - 1) * $per_page;
$paginated_clients = array_slice($clients, $offset, $per_page);

$map_json = json_encode(
    $map_clients,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

if($map_json === false){

    $map_json = "[]";

}

?>

<!DOCTYPE html>
<html lang="fr">
<head>

    <?php require_once(dirname(__DIR__) . "/includes/pwa-head.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Localisation des clients | INFINITIA</title>
    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIINfQ3ynhED3sBbr6qU1Hd5HfzglaGNVwI=" crossorigin="">

    <style>
        .filter-card,
        .map-card,
        .clients-table-card,
        .map-summary-card{
            background:#ffffff;
            border-radius:14px;
            box-shadow:0 8px 22px rgba(0,0,0,.08);
        }

        .filter-card,
        .map-card,
        .clients-table-card{
            margin-bottom:22px;
            padding:18px;
        }

        .filter-actions{
            align-items:center;
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            min-height:66px;
        }

        .filter-actions .btn,
        .filter-actions .btn-flat,
        .map-action{
            border-radius:22px;
        }

        .map-summary-card{
            min-height:112px;
            padding:18px;
        }

        .map-summary-card h5{
            color:#2f3b55;
            font-size:15px;
            font-weight:600;
            margin:12px 0 6px;
        }

        .map-summary-card h3{
            color:#081f78;
            font-size:30px;
            font-weight:800;
            margin:0;
        }

        #clients-map{
            border-radius:12px;
            display:block;
            height:600px;
            max-width:100%;
            overflow:hidden;
            position:relative;
            width:100%;
            z-index:1;
        }

        #clients-map .leaflet-tile,
        #clients-map .leaflet-marker-icon,
        #clients-map .leaflet-marker-shadow{
            max-height:none !important;
            max-width:none !important;
        }

        #clients-map .leaflet-tile{
            height:256px !important;
            width:256px !important;
        }

        .map-heading,
        .table-heading{
            color:#081f78;
            font-size:20px;
            font-weight:700;
            margin:0 0 16px;
        }

        .map-empty-message{
            margin-bottom:18px;
        }

        .map-popup-title{
            color:#081f78;
            font-size:16px;
            font-weight:700;
            margin-bottom:8px;
        }

        .map-popup-line{
            margin:4px 0;
        }

        .map-popup-link{
            display:inline-block;
            margin-top:10px;
        }

        .table-responsive{
            overflow-x:auto;
        }

        .table-actions{
            display:flex;
            flex-wrap:wrap;
            gap:7px;
            min-width:250px;
        }

        .table-pagination-info{
            margin:-8px 0 18px;
        }

        .pagination-wrap{
            margin-top:22px;
            text-align:center;
        }

        .pagination-wrap .pagination{
            display:inline-flex;
            flex-wrap:wrap;
            justify-content:center;
        }

        @media only screen and (max-width:600px){
            #clients-map{
                height:430px;
            }

            .filter-actions{
                align-items:stretch;
                flex-direction:column;
            }

            .filter-actions .btn,
            .filter-actions .btn-flat{
                margin:0;
                text-align:center;
                width:100%;
            }
        }
    </style>
</head>

<body class="admin-module">
<div class="dashboard">
    <?php
    $current_page = "carte-clients";
    include("menuadmin.php");
    ?>

    <div class="main-content">
        <div class="topbar">
            <div>
                <div class="page-title">Localisation des clients</div>
                <div class="welcome-text">
                    Visualisez et filtrez les positions GPS enregistrées des clients.
                </div>
            </div>
        </div>

        <?php if($query_error){ ?>
            <div class="card-panel red white-text">
                Impossible de charger les positions des clients pour le moment.
            </div>
        <?php } ?>

        <div class="filter-card">
            <form action="<?php echo app_url_html("admin/localisation-clients"); ?>" method="GET">
                <div class="row" style="margin-bottom:0;">
                    <div class="input-field col s12 m6 l3">
                        <i class="material-icons prefix">search</i>
                        <input type="text" name="client_name" id="client_name"
                               value="<?php echo map_safe_text($client_name); ?>">
                        <label for="client_name" class="<?php if($client_name !== ""){ echo "active"; } ?>">
                            Nom du client
                        </label>
                    </div>

                    <div class="input-field col s12 m6 l3">
                        <select name="city" id="city">
                            <option value="">Toutes les villes</option>
                            <?php foreach($cities as $city_option){ ?>
                                <option value="<?php echo map_safe_text($city_option); ?>"
                                    <?php if($city === $city_option){ echo "selected"; } ?>>
                                    <?php echo map_safe_text($city_option); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <label for="city">Ville</label>
                    </div>

                    <div class="input-field col s12 m6 l3">
                        <select name="client_type" id="client_type">
                            <option value="">Tous les types</option>
                            <option value="individual" <?php if($client_type === "individual"){ echo "selected"; } ?>>Particulier</option>
                            <option value="company" <?php if($client_type === "company"){ echo "selected"; } ?>>Entreprise</option>
                            <option value="expatriate" <?php if($client_type === "expatriate"){ echo "selected"; } ?>>Expatrié</option>
                        </select>
                        <label for="client_type">Type de client</label>
                    </div>

                    <div class="col s12 m6 l3 filter-actions">
                        <button type="submit" class="btn waves-effect waves-light">
                            <i class="material-icons left">filter_list</i>Filtrer
                        </button>
                        <a href="<?php echo app_url_html("admin/localisation-clients"); ?>" class="btn-flat waves-effect">Réinitialiser</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="row intervenant-stat-grid admin-stat-grid">
            <div class="col s12 m4">
                <div class="map-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">groups</i></div>
                    <h5>Clients trouvés</h5>
                    <h3><?php echo (int)$clients_found; ?></h3>
                </div>
            </div>
            <div class="col s12 m4">
                <div class="map-summary-card">
                    <div class="card-icon blue-gradient"><i class="material-icons">location_on</i></div>
                    <h5>Positions affichées</h5>
                    <h3><?php echo (int)$displayed_positions; ?></h3>
                </div>
            </div>
            <div class="col s12 m4">
                <div class="map-summary-card">
                    <div class="card-icon pink-gradient"><i class="material-icons">location_off</i></div>
                    <h5>Coordonnées invalides</h5>
                    <h3><?php echo (int)$invalid_positions; ?></h3>
                </div>
            </div>
        </div>

        <div class="map-card">
            <h2 class="map-heading">Carte des positions</h2>

            <?php if($displayed_positions === 0){ ?>
                <div class="card-panel amber lighten-4 brown-text text-darken-4 map-empty-message">
                    Aucune position GPS valide ne correspond aux filtres sélectionnés.
                </div>
            <?php } ?>

            <div id="clients-map" aria-label="Carte de localisation des clients"></div>
        </div>

        <div class="clients-table-card">
            <h2 class="table-heading">
                Clients affichés
                <span class="grey-text" style="font-size:14px;font-weight:400;">
                    (<?php echo (int)$displayed_positions; ?> résultat(s))
                </span>
            </h2>

            <?php if($displayed_positions > 0){ ?>
                <p class="grey-text table-pagination-info">
                    Page <?php echo (int)$page; ?> sur <?php echo (int)$total_pages; ?>
                    &mdash; total de <?php echo (int)$total_clients; ?> client(s).
                </p>

                <div class="table-responsive">
                    <table class="highlight responsive-table intervenant-table mobile-card-table admin-responsive-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Ville</th>
                                <th>Adresse</th>
                                <th>Coordonnées</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($paginated_clients as $local_index => $client){ ?>
                                <?php $global_index = $offset + $local_index; ?>
                                <tr class="mobile-card-row">
                                    <td data-label="Client"><?php echo map_safe_text($client["name"]); ?></td>
                                    <td data-label="Type"><?php echo map_safe_text($client["type"]); ?></td>
                                    <td data-label="Ville"><?php echo map_safe_text($client["city"]); ?></td>
                                    <td data-label="Adresse"><?php echo map_safe_text($client["address"]); ?></td>
                                    <td data-label="Coordonnées">
                                        <?php echo map_safe_text($client["latitude"] . ", " . $client["longitude"]); ?>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="table-actions admin-actions">
                                            <button type="button" class="btn-small waves-effect waves-light map-action"
                                                    data-marker-index="<?php echo (int)$global_index; ?>">
                                                Voir sur la carte
                                            </button>
                                            <a class="btn-small blue darken-4 map-action"
                                               href="https://www.google.com/maps?q=<?php echo rawurlencode($client["latitude"] . "," . $client["longitude"]); ?>"
                                               target="_blank" rel="noopener noreferrer">
                                                Google Maps
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrap">
                    <ul class="pagination">
                        <?php if($page > 1){ ?>
                            <li class="waves-effect">
                                <a href="<?php echo map_safe_text(map_pagination_url($page - 1, $client_name, $city, $client_type)); ?>">
                                    Précédent
                                </a>
                            </li>
                        <?php }else{ ?>
                            <li class="disabled"><a href="#!">Précédent</a></li>
                        <?php } ?>

                        <?php for($page_number = 1; $page_number <= $total_pages; $page_number++){ ?>
                            <?php if($page_number === $page){ ?>
                                <li class="active"><a href="#!"><?php echo (int)$page_number; ?></a></li>
                            <?php }else{ ?>
                                <li class="waves-effect">
                                    <a href="<?php echo map_safe_text(map_pagination_url($page_number, $client_name, $city, $client_type)); ?>">
                                        <?php echo (int)$page_number; ?>
                                    </a>
                                </li>
                            <?php } ?>
                        <?php } ?>

                        <?php if($page < $total_pages){ ?>
                            <li class="waves-effect">
                                <a href="<?php echo map_safe_text(map_pagination_url($page + 1, $client_name, $city, $client_type)); ?>">
                                    Suivant
                                </a>
                            </li>
                        <?php }else{ ?>
                            <li class="disabled"><a href="#!">Suivant</a></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php }else{ ?>
                <p class="grey-text">Aucun client à afficher dans la liste.</p>
            <?php } ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    M.FormSelect.init(document.querySelectorAll('select'));

    var clients = <?php echo $map_json; ?>;
    var defaultPosition = [-10.7167, 25.4667];
    var map = L.map('clients-map').setView(defaultPosition, 12);
    var markers = [];

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    function addPopupLine(container, label, value) {
        var line = document.createElement('div');
        var strong = document.createElement('strong');

        line.className = 'map-popup-line';
        strong.textContent = label + ' : ';
        line.appendChild(strong);
        line.appendChild(document.createTextNode(value || 'Non renseigné'));
        container.appendChild(line);
    }

    clients.forEach(function(client) {
        var marker = L.marker([client.latitude, client.longitude]).addTo(map);
        var popup = document.createElement('div');
        var title = document.createElement('div');
        var googleLink = document.createElement('a');

        title.className = 'map-popup-title';
        title.textContent = client.name || 'Client';
        popup.appendChild(title);

        addPopupLine(popup, 'Type', client.type);
        addPopupLine(popup, 'Ville', client.city);
        addPopupLine(popup, 'Adresse', client.address);
        addPopupLine(popup, 'Latitude', String(client.latitude));
        addPopupLine(popup, 'Longitude', String(client.longitude));
        addPopupLine(popup, 'E-mail', client.email);

        if(client.phone){
            addPopupLine(popup, 'Téléphone', client.phone);
        }

        googleLink.className = 'btn-small blue darken-4 map-popup-link';
        googleLink.href = 'https://www.google.com/maps?q=' +
            encodeURIComponent(client.latitude + ',' + client.longitude);
        googleLink.target = '_blank';
        googleLink.rel = 'noopener noreferrer';
        googleLink.textContent = 'Ouvrir dans Google Maps';
        popup.appendChild(googleLink);

        marker.bindPopup(popup);
        markers.push(marker);
    });

    function updateMapView() {
        if(markers.length > 1){
            map.fitBounds(L.featureGroup(markers).getBounds(), {padding:[35, 35]});
        }else if(markers.length === 1){
            map.setView(markers[0].getLatLng(), 15);
        }else{
            map.setView(defaultPosition, 12);
        }
    }

    updateMapView();

    window.addEventListener('load', function() {
        setTimeout(function() {
            map.invalidateSize(true);

            if(markers.length > 1){
                map.fitBounds(
                    L.featureGroup(markers).getBounds(),
                    {padding:[35, 35]}
                );
            }else if(markers.length === 1){
                map.setView(markers[0].getLatLng(), 15);
            }
        }, 300);

        setTimeout(function() {
            map.invalidateSize(true);
        }, 800);
    });

    window.addEventListener('resize', function() {
        map.invalidateSize(true);
    });

    Array.prototype.forEach.call(document.querySelectorAll('.map-action[data-marker-index]'), function(button) {
        button.addEventListener('click', function() {
            var markerIndex = parseInt(button.getAttribute('data-marker-index'), 10);

            if(markers[markerIndex]){
                map.setView(markers[markerIndex].getLatLng(), 16);
                markers[markerIndex].openPopup();
                document.getElementById('clients-map').scrollIntoView({behavior:'smooth', block:'center'});
            }
        });
    });
});
</script>
</body>
</html>
