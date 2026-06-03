<?php

session_start();

require_once("../config/database.php");

$id            = $_POST['id'];
$title         = $_POST['title'];
$description   = $_POST['description'];
$location      = $_POST['location'];
$service_date  = $_POST['service_date'];
$duration      = $_POST['duration'];
$budget        = $_POST['budget'];
$urgency_level = $_POST['urgency_level'];

$sql = "UPDATE service_requests
        SET
            title=?,
            description=?,
            location=?,
            service_date=?,
            duration=?,
            budget=?,
            urgency_level=?
        WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssssidsi",
    $title,
    $description,
    $location,
    $service_date,
    $duration,
    $budget,
    $urgency_level,
    $id
);

mysqli_stmt_execute($stmt);

$_SESSION['success'] =
"Demande modifiée avec succès.";

header("Location: mes-demandes.php");
exit;