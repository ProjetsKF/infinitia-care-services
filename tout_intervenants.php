<?php

session_start();

require_once("config/database.php");

/* Pagination */
$limit = 10;

$page = 1;

if(isset($_GET['page']) && (int)$_GET['page'] > 0)
{
    $page = (int)$_GET['page'];
}

$offset = ($page - 1) * $limit;

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

$result_count = mysqli_query($conn, $sql_count);

$total_intervenants = 0;

if($result_count)
{
    $row_count = mysqli_fetch_assoc($result_count);
    $total_intervenants = (int)$row_count['total'];
}

$total_pages = ceil($total_intervenants / $limit);

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
    c.bio
FROM users u
INNER JOIN candidates c
    ON u.id = c.user_id
WHERE u.role_id = 3
AND u.status = 'active'
AND c.verification_status = 'verifie'
ORDER BY u.first_name ASC
LIMIT ?, ?
";

$stmt = mysqli_prepare($conn, $sql);

$intervenants = array();

if($stmt)
{
    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $offset,
        $limit
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while($row = mysqli_fetch_assoc($result))
    {
        $intervenants[] = $row;
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

    <title>Nos intervenants | INFINITIA CARE SERVICES</title>

    <link rel="icon"
          type="image/x-icon"
          href="assets/images/ico.ico">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">
    
          <link rel="stylesheet" href="assets/css/style.css">

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

    </style>

</head>

<body>

<section class="page-hero">

    <a href="index.php" class="back-home">
        <i class="material-icons left">arrow_back</i>
        Retour à l'accueil
    </a>

    <h1>Nos intervenants</h1>

    <p>
        Découvrez les intervenants vérifiés et disponibles sur INFINITIA CARE SERVICES.
    </p>

</section>

<div class="content-wrapper">

    <div class="row">

        <?php if(count($intervenants) > 0){ ?>

            <?php foreach($intervenants as $intervenant){ ?>

                <div class="col s12 m6 l4">

                    <div class="intervenant-card">

                        <?php if(!empty($intervenant['profile_photo'])){ ?>

                            <img
                                src="<?php echo htmlspecialchars($intervenant['profile_photo']); ?>"
                                class="intervenant-photo"
                                alt="Photo intervenant">

                        <?php }else{ ?>

                            <img
                                src="assets/images/default-user.png"
                                class="intervenant-photo"
                                alt="Photo intervenant">

                        <?php } ?>

                        <h5>
                            <?php
                            echo htmlspecialchars(
                                $intervenant['first_name'] . ' ' .
                                $intervenant['last_name']
                            );
                            ?>
                        </h5>

                        <div class="intervenant-info">

                            <i class="material-icons tiny">location_on</i>

                            <?php echo htmlspecialchars($intervenant['city']); ?>

                        </div>

                        <div class="intervenant-info">

                            <i class="material-icons tiny">work</i>

                            <?php echo (int)$intervenant['experience_years']; ?> ans d'expérience

                        </div>

                        <?php

                        $status_class = 'hors_ligne';
                        $status_label = 'Hors ligne';

                        if($intervenant['availability_status'] == 'disponible')
                        {
                            $status_class = 'disponible';
                            $status_label = 'Disponible';
                        }
                        elseif($intervenant['availability_status'] == 'occupé')
                        {
                            $status_class = 'occupe';
                            $status_label = 'Occupé';
                        }

                        ?>

                        <span class="badge-status <?php echo $status_class; ?>">
                            <?php echo $status_label; ?>
                        </span>

                        <?php if(!empty($intervenant['bio'])){ ?>

                            <p style="margin-top:15px;color:#666;">
                                <?php echo htmlspecialchars(substr($intervenant['bio'],0,120)); ?>...
                            </p>

                        <?php } ?>

                    </div>

                </div>

            <?php } ?>

        <?php }else{ ?>

            <div class="col s12">

                <div class="empty-box">

                    Aucun intervenant disponible pour le moment.

                </div>

            </div>

        <?php } ?>

    </div>

    <?php if($total_pages > 1){ ?>

        <div class="center-align">

            <ul class="pagination">

                <?php if($page > 1){ ?>

                    <li class="waves-effect">
                        <a href="tout_intervenants.php?page=<?php echo $page - 1; ?>">
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

                <?php for($i = 1; $i <= $total_pages; $i++){ ?>

                    <li class="<?php echo ($i == $page) ? 'active' : 'waves-effect'; ?>">
                        <a href="tout_intervenants.php?page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>

                <?php } ?>

                <?php if($page < $total_pages){ ?>

                    <li class="waves-effect">
                        <a href="tout_intervenants.php?page=<?php echo $page + 1; ?>">
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