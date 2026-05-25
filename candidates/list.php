<?php

require_once "../config/database.php";

$query = $pdo->query("SELECT * FROM candidates");

$candidates = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

</head>

<body>

    <?php include "../includes/header.php"; ?>

<div class="container">

<h4>Liste des candidats</h4>

<table class="striped">

<thead>
<tr>
    <th>ID</th>
    <th>Ville</th>
    <th>Nationalité</th>
</tr>
</thead>

<tbody>

<?php foreach($candidates as $candidate): ?>

<tr>
    <td><?= $candidate['id'] ?></td>
    <td><?= $candidate['city'] ?></td>
    <td><?= $candidate['nationality'] ?></td>
</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</body>
</html>