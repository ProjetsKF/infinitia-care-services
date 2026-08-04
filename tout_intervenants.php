<?php

session_start();

require_once("config/database.php");

function pagination_url($page_number, $search)
{
    $params = array(
        "page" => (int)$page_number
    );

    if($search != ""){
        $params["search"] = $search;
    }

    return app_url_with_query("intervenants/", $params);
}

function public_profile_photo_path($profile_photo)
{
    if($profile_photo === NULL || trim($profile_photo) === ""){
        return app_url("assets/images/default-user.png");
    }

    $profile_photo = str_replace("\\", "/", trim($profile_photo));

    if(strpos($profile_photo, ":") !== false){
        return app_url("assets/images/default-user.png");
    }

    if(strpos($profile_photo, "../uploads/") === 0){
        $profile_photo = substr($profile_photo, 3);
    }

    if(strpos($profile_photo, "..") !== false){
        return app_url("assets/images/default-user.png");
    }

    if(strpos($profile_photo, "uploads/") === 0){
        return app_url($profile_photo);
    }

    if(strpos($profile_photo, "/") !== false){
        return app_url("assets/images/default-user.png");
    }

    return app_url("uploads/profiles/" . $profile_photo);
}

$limit = 20;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

if($page < 1){
    $page = 1;
}

if(strlen($search) > 100){
    $search = substr($search, 0, 100);
}

$search_like = "%" . $search . "%";

/* Nombre total d'intervenants visibles */
$sql_count = "
SELECT COUNT(*) AS total
FROM users u
INNER JOIN candidates c
    ON u.id = c.user_id
WHERE u.role_id = 3
AND u.status = 'active'
AND c.verification_status = 'verifie'
";

$total_intervenants = 0;

if($search != ""){
    $sql_count .= "
    AND (
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        OR c.city LIKE ?
        OR c.bio LIKE ?
        OR c.availability_status LIKE ?
    )
    ";

    $stmt_count = mysqli_prepare($conn, $sql_count);

    if($stmt_count){
        mysqli_stmt_bind_param(
            $stmt_count,
            "ssssss",
            $search_like,
            $search_like,
            $search_like,
            $search_like,
            $search_like,
            $search_like
        );

        if(mysqli_stmt_execute($stmt_count)){
            mysqli_stmt_bind_result($stmt_count, $total_intervenants);
            mysqli_stmt_fetch($stmt_count);
            $total_intervenants = (int)$total_intervenants;
        }

        mysqli_stmt_close($stmt_count);
    }
}else{
    $result_count = mysqli_query($conn, $sql_count);

    if($result_count){
        $row_count = mysqli_fetch_assoc($result_count);

        if($row_count && isset($row_count["total"])){
            $total_intervenants = (int)$row_count["total"];
        }

        mysqli_free_result($result_count);
    }
}

$total_pages = (int)ceil($total_intervenants / $limit);

if($total_pages > 0 && $page > $total_pages){
    $page = $total_pages;
}

if($total_pages < 1){
    $page = 1;
}

$offset = ($page - 1) * $limit;

/* Liste des intervenants */
$sql = "
SELECT
    u.id,
    u.first_name,
    u.last_name,
    u.profile_photo,
    u.phone,
    c.city,
    c.experience_years,
    c.availability_status,
    c.bio,
    c.photo_consent
FROM users u
INNER JOIN candidates c
    ON u.id = c.user_id
WHERE u.role_id = 3
AND u.status = 'active'
AND c.verification_status = 'verifie'
";

if($search != ""){
    $sql .= "
    AND (
        u.first_name LIKE ?
        OR u.last_name LIKE ?
        OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
        OR c.city LIKE ?
        OR c.bio LIKE ?
        OR c.availability_status LIKE ?
    )
    ";
}

$sql .= "
ORDER BY u.first_name ASC, u.last_name ASC
LIMIT ?, ?
";

$stmt = mysqli_prepare($conn, $sql);

$intervenants = array();

if($stmt)
{
    if($search != ""){
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssii",
            $search_like,
            $search_like,
            $search_like,
            $search_like,
            $search_like,
            $search_like,
            $offset,
            $limit
        );
    }else{
        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $offset,
            $limit
        );
    }

    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_bind_result(
            $stmt,
            $user_id,
            $first_name,
            $last_name,
            $profile_photo,
            $phone,
            $city,
            $experience_years,
            $availability_status,
            $bio,
            $photo_consent
        );

        while(mysqli_stmt_fetch($stmt)){
            $intervenants[] = array(
                "id" => $user_id,
                "first_name" => $first_name,
                "last_name" => $last_name,
                "profile_photo" => $profile_photo,
                "phone" => $phone,
                "city" => $city,
                "experience_years" => $experience_years,
                "availability_status" => $availability_status,
                "bio" => $bio,
                "photo_consent" => $photo_consent
            );
        }
    }

    mysqli_stmt_close($stmt);
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(__DIR__ . "/includes/pwa-head.php"); ?>

    <base href="<?php echo app_url_html(""); ?>">

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Nos intervenants | INFINITIA CARE SERVICES</title>

    <link rel="icon"
          type="image/x-icon"
          href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">
    
          <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>

        body{
            font-family:'Poppins', sans-serif;
            background:#f4f6fb;
            margin:0;
        }

        .page-hero{
            background:linear-gradient(135deg,#071f7a,#8e24aa);
            color:#ffffff;
            padding:60px 8%;
            text-align:center;
        }

        .page-hero h1{
            font-size:45px;
            font-weight:800;
            margin-bottom:10px;
        }

        .page-hero p{
            font-size:18px;
            max-width:800px;
            margin:0 auto;
        }

        .back-home{
            display:inline-block;
            margin-bottom:25px;
            background:rgba(255,255,255,.15);
            color:#ffffff;
            padding:12px 25px;
            border-radius:40px;
            font-weight:600;
        }

        .back-home:hover{
            background:#ffffff;
            color:#071f7a;
        }

        .content-wrapper{
            padding:50px 7%;
        }

        .intervenant-card{
            background:#ffffff;
            border-radius:22px;
            padding:25px;
            margin-bottom:30px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
            min-height:330px;
            text-align:center;
        }

        .intervenant-photo{
            width:120px;
            height:120px;
            border-radius:50%;
            object-fit:cover;
            margin-bottom:15px;
            border:5px solid #f4f6fb;
        }

        .intervenant-card h5{
            color:#071f7a;
            font-weight:800;
            margin-bottom:8px;
        }

        .intervenant-info{
            color:#555;
            margin-bottom:8px;
        }

        .badge-status{
            display:inline-block;
            margin-top:10px;
            padding:8px 16px;
            border-radius:30px;
            font-weight:600;
            color:#ffffff;
        }

        .disponible{
            background:#009688;
        }

        .occupe{
            background:#ff9800;
        }

        .hors_ligne{
            background:#9e9e9e;
        }

        .pagination li.active{
            background:#071f7a;
        }

        .empty-box{
            background:#ffffff;
            border-radius:20px;
            padding:40px;
            text-align:center;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
        }

        .search-section{
            max-width:1000px;
            margin:35px auto 10px;
            padding:0 7%;
        }

        .search-form{
            background:#ffffff;
            border-radius:18px;
            padding:18px;
            box-shadow:0 10px 30px rgba(0,0,0,.08);
            display:flex;
            align-items:center;
            gap:12px;
        }

        .search-input-wrapper{
            flex:1;
            display:flex;
            align-items:center;
            gap:10px;
            border:1px solid #e0e0e0;
            border-radius:12px;
            padding:0 15px;
        }

        .search-input-wrapper i{
            color:#071f7a;
        }

        .search-input-wrapper input{
            border-bottom:none !important;
            box-shadow:none !important;
            margin:0 !important;
        }

        .search-button{
            background:#071f7a;
            border-radius:10px;
        }

        .search-button:hover,
        .search-button:focus{
            background:#8e24aa;
        }

        .reset-button{
            border-radius:10px;
        }

        .results-summary{
            color:#455a64;
            font-weight:600;
            margin:0 0 25px;
            text-align:center;
        }

        .empty-box .show-all-link{
            display:inline-block;
            margin-top:18px;
            color:#071f7a;
            font-weight:600;
        }

        .pagination .ellipsis{
            padding:0 10px;
            line-height:30px;
        }

        @media(max-width:700px){
            .search-form{
                flex-direction:column;
                align-items:stretch;
            }

            .search-button,
            .reset-button{
                width:100%;
                text-align:center;
            }
        }

    </style>

</head>

<body>

<section class="page-hero">

    <a href="<?php echo app_url_html(""); ?>" class="back-home">
        <i class="material-icons left">arrow_back</i>
        Retour à l'accueil
    </a>

    <h1>Nos intervenants</h1>

    <p>
        Découvrez les intervenants vérifiés et disponibles sur INFINITIA CARE SERVICES.
    </p>

</section>

<!-- INFORMATION SUR LE CONSENTEMENT ET LE DROIT À L'IMAGE -->

<div class="consent-notice-wrapper">

    <div class="consent-notice">

        <div class="consent-notice-icon">

            <i class="material-icons">
                verified_user
            </i>

        </div>

        <div class="consent-notice-content">

            <h6>
                Protection du droit à l’image
            </h6>

            <p>
                Les photographies réelles sont affichées uniquement avec le consentement
                des personnes concernées. Lorsqu’un intervenant ne donne pas ou retire
                son consentement, une image générique est affichée à la place. Son profil
                professionnel reste néanmoins visible, conformément à
                l’article 23 de l’Ordonnance-loi n° 86-033 du 5 avril 1986
                relative aux droits d’auteur ainsi qu’aux dispositions du Code
                du numérique de la République Démocratique du Congo relatives
                à la protection des données personnelles. Toute reproduction,
                diffusion ou utilisation sans autorisation est interdite.
            </p>

        </div>

    </div>

</div>

<div class="search-section">

    <form action="<?php echo app_url_html("intervenants/"); ?>" method="GET" class="search-form">

        <div class="search-input-wrapper">
            <i class="material-icons">search</i>

            <input
                type="text"
                name="search"
                maxlength="100"
                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Rechercher par nom, ville ou disponibilité">
        </div>

        <button type="submit" class="btn search-button">
            <i class="material-icons left">search</i>
            Rechercher
        </button>

        <?php if($search != ""){ ?>
            <a href="<?php echo app_url_html("intervenants/"); ?>" class="btn-flat reset-button">
                Réinitialiser
            </a>
        <?php } ?>

    </form>

</div>

<div class="content-wrapper">

    <div class="results-summary">
        <?php if($search != ""){ ?>
            <?php echo (int)$total_intervenants; ?> résultat(s) trouvé(s) pour
            “<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>”
        <?php }else{ ?>
            <?php echo (int)$total_intervenants; ?> intervenant(s) vérifié(s)
        <?php } ?>
    </div>

    <div class="row">

        <?php if(count($intervenants) > 0){ ?>

            <?php foreach($intervenants as $intervenant){ ?>

                <div class="col s12 m6 l4">

                    <div class="intervenant-card">

                        <?php
                        $can_show_real_photo =
                            isset($intervenant["photo_consent"])
                            && (int)$intervenant["photo_consent"] === 1
                            && isset($intervenant["profile_photo"])
                            && trim((string)$intervenant["profile_photo"]) != "";

                        $photo_path = $can_show_real_photo
                            ? public_profile_photo_path($intervenant["profile_photo"])
                            : app_url("assets/images/default-user.png");
                        ?>

                        <img
                            src="<?php echo htmlspecialchars($photo_path, ENT_QUOTES, 'UTF-8'); ?>"
                            class="intervenant-photo"
                            alt="Photo intervenant">

                        <h5>
                            <?php
                            echo htmlspecialchars(
                                $intervenant['first_name'] . ' ' .
                                $intervenant['last_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </h5>

                        <div class="intervenant-info">

                            <i class="material-icons tiny">location_on</i>

                            <?php
                            $display_city = isset($intervenant["city"])
                                && trim((string)$intervenant["city"]) != ""
                                ? $intervenant["city"]
                                : "Ville non renseignée";

                            echo htmlspecialchars($display_city, ENT_QUOTES, "UTF-8");
                            ?>

                        </div>

                        <div class="intervenant-info">

                            <i class="material-icons tiny">work</i>

                            <?php
                            $experience = isset($intervenant["experience_years"])
                                ? (int)$intervenant["experience_years"]
                                : 0;
                            ?>
                            <?php echo $experience; ?>
                            <?php echo $experience == 1 ? "an d’expérience" : "ans d’expérience"; ?>

                        </div>

                        <?php

                        $status_class = 'hors_ligne';
                        $status_label = 'Hors ligne';

                        $current_status = isset($intervenant["availability_status"])
                            ? $intervenant["availability_status"]
                            : "hors_ligne";

                        if($current_status == 'disponible')
                        {
                            $status_class = 'disponible';
                            $status_label = 'Disponible';
                        }
                        elseif($current_status == 'occupé')
                        {
                            $status_class = 'occupe';
                            $status_label = 'Occupé';
                        }

                        ?>

                        <span class="badge-status <?php echo $status_class; ?>">
                            <?php echo $status_label; ?>
                        </span>

                        <?php if(isset($intervenant["bio"]) && trim((string)$intervenant["bio"]) != ""){ ?>

                            <p style="margin-top:15px;color:#666;">
                                <?php
                                $bio_text = (string)$intervenant["bio"];
                                $bio_excerpt = substr($bio_text, 0, 120);

                                echo htmlspecialchars($bio_excerpt, ENT_QUOTES, "UTF-8");

                                if(strlen($bio_text) > 120){
                                    echo "...";
                                }
                                ?>
                            </p>

                        <?php } ?>

                    </div>

                </div>

            <?php } ?>

        <?php }else{ ?>

            <div class="col s12">

                <div class="empty-box">

                    <?php if($search != ""){ ?>
                        Aucun intervenant ne correspond à votre recherche.
                        <br>
                        <a href="<?php echo app_url_html("intervenants/"); ?>" class="show-all-link">
                            Afficher tous les intervenants
                        </a>
                    <?php }else{ ?>
                        Aucun intervenant disponible pour le moment.
                    <?php } ?>

                </div>

            </div>

        <?php } ?>

    </div>

    <?php if($total_pages > 1){ ?>

        <div class="center-align">

            <ul class="pagination">

                <?php if($page > 1){ ?>

                    <li class="waves-effect">
                        <a href="<?php echo htmlspecialchars(pagination_url($page - 1, $search), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="material-icons">chevron_left</i>
                        </a>
                    </li>

                <?php }else{ ?>

                    <li class="disabled">
                        <a href="#!">
                            <i class="material-icons">chevron_left</i>
                        </a>
                    </li>

                <?php } ?>

                <?php
                $pagination_pages = array();
                $pagination_pages[1] = true;
                $pagination_pages[$total_pages] = true;
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for($i = $start_page; $i <= $end_page; $i++){
                    $pagination_pages[$i] = true;
                }

                ksort($pagination_pages);
                $previous_page_number = 0;

                foreach($pagination_pages as $page_number => $unused){
                    if($previous_page_number > 0 && $page_number > $previous_page_number + 1){
                ?>
                        <li class="disabled ellipsis"><span>...</span></li>
                <?php } ?>

                    <li class="<?php echo ($page_number == $page) ? 'active' : 'waves-effect'; ?>">
                        <a href="<?php echo htmlspecialchars(pagination_url($page_number, $search), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo (int)$page_number; ?>
                        </a>
                    </li>

                <?php
                    $previous_page_number = $page_number;
                }
                ?>

                <?php if($page < $total_pages){ ?>

                    <li class="waves-effect">
                        <a href="<?php echo htmlspecialchars(pagination_url($page + 1, $search), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="material-icons">chevron_right</i>
                        </a>
                    </li>

                <?php }else{ ?>

                    <li class="disabled">
                        <a href="#!">
                            <i class="material-icons">chevron_right</i>
                        </a>
                    </li>

                <?php } ?>

            </ul>

        </div>

    <?php } ?>

</div>

 <?php include "includes/footer.php"; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

</body>
</html>
