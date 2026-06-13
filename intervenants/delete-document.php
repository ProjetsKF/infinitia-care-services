<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}

if(!isset($_GET['id'])){

    header("Location: mes-documents.php");
    exit();

}

$document_id = (int)$_GET['id'];

$sql = "

SELECT
    cd.*,
    c.user_id

FROM candidate_documents cd

INNER JOIN candidates c
ON cd.candidate_id = c.id

WHERE cd.id = ?

LIMIT 1

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $document_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$document = mysqli_fetch_assoc($result);

if(!$document){

    $_SESSION['error'] =
    "Document introuvable.";

    header("Location: mes-documents.php");
    exit();

}

if($document['user_id'] != $_SESSION['user_id']){

    $_SESSION['error'] =
    "Accès refusé.";

    header("Location: mes-documents.php");
    exit();

}

$file = "../".$document['file_path'];

if(file_exists($file)){

    unlink($file);

}

$sql = "

DELETE FROM candidate_documents

WHERE id = ?

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $document_id
);

if(mysqli_stmt_execute($stmt)){

    $_SESSION['success'] =
    "Document supprimé avec succès.";

}else{

    $_SESSION['error'] =
    "Erreur lors de la suppression.";

}

header("Location: mes-documents.php");
exit();

?>