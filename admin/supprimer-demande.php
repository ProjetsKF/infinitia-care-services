<?php

session_start();

require_once(dirname(__DIR__) . "/config/database.php");
require_once(dirname(__DIR__) . "/includes/admin-delete-security.php");

admin_delete_require_admin();

function delete_request_redirect()
{
    header("Location: " . app_url("admin/demandes"));
    exit();
}

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    $_SESSION["error"] = "La suppression d'une demande doit être confirmée depuis l'interface administrateur.";
    delete_request_redirect();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!admin_delete_validate_csrf($csrf_token)){
    $_SESSION["error"] = "La demande de suppression a expiré. Veuillez réessayer.";
    delete_request_redirect();
}

$request_id = isset($_POST["request_id"]) ? intval($_POST["request_id"]) : 0;

if($request_id <= 0){
    $_SESSION["error"] = "Identifiant de demande invalide.";
    delete_request_redirect();
}

$conn->begin_transaction();
$stmt = mysqli_prepare($conn, "SELECT id FROM service_requests WHERE id = ? LIMIT 1 FOR UPDATE");

if(!$stmt){
    $conn->rollback();
    error_log("Erreur verrouillage demande : " . mysqli_error($conn));
    $_SESSION["error"] = "Impossible de vérifier la demande pour le moment.";
    delete_request_redirect();
}

$existing_id = 0;
mysqli_stmt_bind_param($stmt, "i", $request_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $existing_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if($existing_id <= 0){
    $conn->rollback();
    $_SESSION["error"] = "Demande introuvable ou déjà supprimée.";
    delete_request_redirect();
}

$mission_total = admin_delete_count($conn, "SELECT COUNT(*) FROM missions WHERE service_request_id = ? FOR UPDATE", $request_id);

if($mission_total < 0){
    $conn->rollback();
    $_SESSION["error"] = "Impossible de vérifier les dépendances de cette demande.";
    delete_request_redirect();
}

if($mission_total > 0){
    $conn->rollback();
    $_SESSION["error"] = "Impossible de supprimer cette demande car une mission lui est associée. Son historique doit être conservé.";
    delete_request_redirect();
}

if(!admin_delete_execute($conn, "DELETE FROM service_requests WHERE id = ?", $request_id)){
    $conn->rollback();
    $_SESSION["error"] = "Une erreur est survenue lors de la suppression de la demande.";
    delete_request_redirect();
}

$conn->commit();
$_SESSION["success"] = "Demande supprimée avec succès.";
delete_request_redirect();

?>
