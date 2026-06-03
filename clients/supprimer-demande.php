<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"])){

    header("Location: ../login.php");
    exit();

}

if(!isset($_GET['id'])){

    $_SESSION['error'] = "Demande introuvable.";

    header("Location: mes-demandes.php");
    exit();

}

$user_id = $_SESSION["user_id"];

$request_id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| Vérifier que la demande appartient au client connecté
|--------------------------------------------------------------------------
*/

$sql = "SELECT id
        FROM service_requests
        WHERE id = ?
        AND client_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $request_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0){

    $_SESSION['error'] =
    "Vous n'êtes pas autorisé à supprimer cette demande.";

    header("Location: mes-demandes.php");
    exit();

}

/*
|--------------------------------------------------------------------------
| Suppression
|--------------------------------------------------------------------------
*/

$sql = "DELETE FROM service_requests
        WHERE id = ?
        AND client_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $request_id,
    $user_id
);

if(mysqli_stmt_execute($stmt)){

    $_SESSION['success'] =
    "La demande a été supprimée avec succès.";

}else{

    $_SESSION['error'] =
    "Une erreur est survenue lors de la suppression.";

}

header("Location: mes-demandes.php");
exit();