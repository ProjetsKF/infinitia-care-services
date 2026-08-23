<?php

session_start();

require_once(dirname(__DIR__) . "/config/database.php");
require_once(dirname(__DIR__) . "/includes/admin-delete-security.php");

admin_delete_require_admin();

function delete_review_redirect()
{
    header("Location: " . app_url("admin/evaluations"));
    exit();
}

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    $_SESSION["error"] = "La suppression d'une évaluation doit être confirmée depuis l'interface administrateur.";
    delete_review_redirect();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!admin_delete_validate_csrf($csrf_token)){
    $_SESSION["error"] = "La demande de suppression a expiré. Veuillez réessayer.";
    delete_review_redirect();
}

$evaluation_id = isset($_POST["evaluation_id"]) ? intval($_POST["evaluation_id"]) : 0;

if($evaluation_id <= 0){
    $_SESSION["error"] = "Identifiant d'évaluation invalide.";
    delete_review_redirect();
}

if(!mysqli_begin_transaction($conn)){
    error_log("Erreur démarrage transaction suppression évaluation : " . mysqli_error($conn));
    $_SESSION["error"] = "Impossible de vérifier cette évaluation pour le moment.";
    delete_review_redirect();
}

$stmt = mysqli_prepare($conn, "SELECT id FROM service_reviews WHERE id = ? LIMIT 1 FOR UPDATE");

if(!$stmt){
    mysqli_rollback($conn);
    error_log("Erreur préparation verrouillage évaluation : " . mysqli_error($conn));
    $_SESSION["error"] = "Impossible de vérifier cette évaluation pour le moment.";
    delete_review_redirect();
}

$existing_id = 0;
mysqli_stmt_bind_param($stmt, "i", $evaluation_id);

if(!mysqli_stmt_execute($stmt)){
    error_log("Erreur verrouillage évaluation : " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    mysqli_rollback($conn);
    $_SESSION["error"] = "Impossible de vérifier cette évaluation pour le moment.";
    delete_review_redirect();
}

mysqli_stmt_bind_result($stmt, $existing_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if($existing_id <= 0){
    mysqli_rollback($conn);
    $_SESSION["error"] = "Évaluation introuvable ou déjà supprimée.";
    delete_review_redirect();
}

if(!admin_delete_execute($conn, "DELETE FROM service_reviews WHERE id = ?", $evaluation_id)){
    mysqli_rollback($conn);
    $_SESSION["error"] = "Une erreur est survenue lors de la suppression de l'évaluation.";
    delete_review_redirect();
}

if(!mysqli_commit($conn)){
    error_log("Erreur validation suppression évaluation : " . mysqli_error($conn));
    mysqli_rollback($conn);
    $_SESSION["error"] = "Une erreur est survenue lors de la suppression de l'évaluation.";
    delete_review_redirect();
}

$_SESSION["success"] = "Évaluation supprimée avec succès.";
delete_review_redirect();

?>
