<?php

session_start();

if (!isset($_SESSION['candidate_user_id'])) {

    header("Location: register-candidate.php");
    exit();

}

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <title>Étape 2</title>

</head>

<body>

    <h2>
    Étape 2 : Informations personnelles
</h2>

<p>
    Bonjour
    <strong>
        <?php echo $_SESSION['first_name'] ?? ''; ?>
    </strong>
</p>

<p>
    Veuillez compléter les informations suivantes :
</p>

<ul>

    <li>Date de naissance</li>

    <li>Sexe</li>

    <li>Adresse</li>

    <li>Ville</li>

    <li>Nationalité</li>

    <li>État civil</li>

</ul>

 <pre>
<?php print_r($_SESSION); ?>
    </pre>

</body>

</html>