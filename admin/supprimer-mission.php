<?php

session_start();

require_once(dirname(__DIR__) . "/config/database.php");
require_once(dirname(__DIR__) . "/includes/admin-delete-security.php");

admin_delete_require_admin();

function delete_mission_redirect()
{
    header("Location: " . app_url("admin/missions"));
    exit();
}

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    $_SESSION["error"] = "La suppression d'une mission doit être confirmée depuis l'interface administrateur.";
    delete_mission_redirect();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!admin_delete_validate_csrf($csrf_token)){
    $_SESSION["error"] = "La demande de suppression a expiré. Veuillez réessayer.";
    delete_mission_redirect();
}

$mission_id = isset($_POST["mission_id"]) ? intval($_POST["mission_id"]) : 0;

if($mission_id <= 0){
    $_SESSION["error"] = "Identifiant de mission invalide.";
    delete_mission_redirect();
}

if(!mysqli_begin_transaction($conn)){
    error_log("Erreur démarrage transaction suppression mission : " . mysqli_error($conn));
    $_SESSION["error"] = "Impossible de vérifier cette mission pour le moment.";
    delete_mission_redirect();
}

$stmt = mysqli_prepare($conn, "SELECT id FROM missions WHERE id = ? LIMIT 1 FOR UPDATE");

if(!$stmt){
    mysqli_rollback($conn);
    error_log("Erreur préparation verrouillage mission : " . mysqli_error($conn));
    $_SESSION["error"] = "Impossible de vérifier cette mission pour le moment.";
    delete_mission_redirect();
}

$existing_id = 0;
mysqli_stmt_bind_param($stmt, "i", $mission_id);

if(!mysqli_stmt_execute($stmt)){
    error_log("Erreur verrouillage mission : " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    mysqli_rollback($conn);
    $_SESSION["error"] = "Impossible de vérifier cette mission pour le moment.";
    delete_mission_redirect();
}

mysqli_stmt_bind_result($stmt, $existing_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if($existing_id <= 0){
    mysqli_rollback($conn);
    $_SESSION["error"] = "Mission introuvable ou déjà supprimée.";
    delete_mission_redirect();
}

$payment_total = admin_delete_count($conn, "SELECT COUNT(*) FROM payments WHERE mission_id = ? FOR UPDATE", $mission_id);
$review_total = admin_delete_count($conn, "SELECT COUNT(*) FROM service_reviews WHERE mission_id = ? FOR UPDATE", $mission_id);

if($payment_total < 0 || $review_total < 0){
    mysqli_rollback($conn);
    $_SESSION["error"] = "Impossible de vérifier les données liées à cette mission.";
    delete_mission_redirect();
}

$dependencies = array();

if($payment_total > 0){
    $dependencies[] = $payment_total . " paiement(s)";
}

if($review_total > 0){
    $dependencies[] = $review_total . " évaluation(s)";
}

if(count($dependencies) > 0){
    mysqli_rollback($conn);
    $_SESSION["error"] = "Suppression impossible : cette mission possède " . implode(" et ", $dependencies) . ". Ces données métier doivent être conservées.";
    delete_mission_redirect();
}

if(!admin_delete_execute($conn, "DELETE FROM missions WHERE id = ?", $mission_id)){
    mysqli_rollback($conn);
    $_SESSION["error"] = "Une erreur est survenue lors de la suppression de la mission.";
    delete_mission_redirect();
}

if(!mysqli_commit($conn)){
    error_log("Erreur validation suppression mission : " . mysqli_error($conn));
    mysqli_rollback($conn);
    $_SESSION["error"] = "Une erreur est survenue lors de la suppression de la mission.";
    delete_mission_redirect();
}

$_SESSION["success"] = "Mission supprimée avec succès.";
delete_mission_redirect();

?>
