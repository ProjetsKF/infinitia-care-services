<?php

session_start();

require_once("config/database.php");

$categories = array();

$sql = "
SELECT
    id,
    name,
    description,
    icon
FROM service_categories
ORDER BY name ASC
";

$result = mysqli_query($conn, $sql);

if($result)
{
    while($row = mysqli_fetch_assoc($result))
    {
        $categories[] = $row;
    }
}

$is_client = false;

if(isset($_SESSION["user_id"]) && isset($_SESSION["role_id"]))
{
    if($_SESSION["role_id"] == 2)
    {
        $is_client = true;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Offres de services | INFINITIA CARE SERVICES</title>

    <link rel="icon"
          type="image/x-icon"
          href="assets/images/ico.ico">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">
          <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet"
          href="assets/css/style.css">

    <style>

        body{
            font-family:'Poppins', sans-serif;
            background:#f4f6fb;
        }

        .offers-hero{
            background:linear-gradient(135deg,#071f7a,#7b1fa2);
            color:#fff;
            padding:70px 8%;
            text-align:center;
        }

        .offers-hero h1{
            font-size:48px;
            font-weight:800;
            margin-bottom:15px;
        }

        .offers-container{
            padding:50px 7%;
        }

        .offer-card{
            background:#fff;
            border-radius:22px;
            padding:30px;
            min-height:260px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
            margin-bottom:30px;
            transition:0.3s;
        }

        .offer-card:hover{
            transform:translateY(-5px);
        }

        .offer-icon{
            width:70px;
            height:70px;
            border-radius:18px;
            background:linear-gradient(135deg,#071f7a,#e91e63);
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            margin-bottom:20px;
        }

        .offer-icon i{
            font-size:36px;
        }

        .offer-card h5{
            color:#071f7a;
            font-weight:700;
            margin-bottom:15px;
        }

        .offer-card p{
            color:#555;
            min-height:70px;
        }

        .offer-actions{
            margin-top:25px;
        }
        .hero-top{

    display:flex;

    justify-content:flex-start;

    margin-bottom:35px;

}

.back-home{

    background:rgba(255,255,255,.15);

    color:#fff !important;

    border:2px solid rgba(255,255,255,.25);

    border-radius:50px;

    padding:0 20px;

    height:48px;

    line-height:48px;

    font-weight:600;

    transition:.3s;

}

.back-home:hover{

    background:#ffffff;

    color:#0b2c87 !important;

    transform:translateX(-3px);

}

.back-home i{

    line-height:48px;

}

    </style>

</head>

<body>
<section class="offers-hero">

    <div class="hero-top">

        <a href="index.php"
           class="btn-flat back-home">

            <i class="material-icons left">

                arrow_back

            </i>

            Retour à l'accueil

        </a>

    </div>

    <h1>

        Nos offres de services

    </h1>

    <p>

        Découvrez les services proposés par INFINITIA CARE SERVICES
        pour les ménages, entreprises et particuliers.

    </p>

</section>

    <div class="offers-container">

        <div class="row">

            <?php if(count($categories) > 0){ ?>

                <?php foreach($categories as $category){ ?>

                    <div class="col s12 m6 l4">

                        <div class="offer-card">

                            <div class="offer-icon">

                                <i class="material-icons">
                                    <?php echo htmlspecialchars($category['icon']); ?>
                                </i>

                            </div>

                            <h5>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </h5>

                            <p>
                                <?php echo htmlspecialchars($category['description']); ?>
                            </p>

                            <div class="offer-actions">

                                <?php if($is_client){ ?>

                                    <a
                                        href="clients/new-request.php?category_id=<?php echo (int)$category['id']; ?>"
                                        class="btn teal waves-effect waves-light">

                                        Demander ce service

                                        <i class="material-icons right">
                                            send
                                        </i>

                                    </a>

                                <?php }else{ ?>

                                    <a
                                        href="login.php"
                                        class="btn blue darken-3 waves-effect waves-light">

                                        Se connecter pour demander

                                        <i class="material-icons right">
                                            login
                                        </i>

                                    </a>

                                <?php } ?>

                            </div>

                        </div>

                    </div>

                <?php } ?>

            <?php }else{ ?>

                <div class="col s12">

                    <div class="card-panel center-align">

                        Aucune offre disponible pour le moment.

                    </div>

                </div>

            <?php } ?>

        </div>

    </div>

     <?php include "includes/footer.php"; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

</body>

</html>