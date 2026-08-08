<?php

session_start();

require_once(dirname(__DIR__) . "/config/database.php");
require_once(dirname(__DIR__) . "/includes/admin-delete-security.php");

admin_delete_require_admin();

function delete_candidate_redirect()
{
    header("Location: " . app_url("admin/intervenants"));
    exit();
}

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    $_SESSION["error"] = "La suppression d'un intervenant doit être confirmée depuis l'interface administrateur.";
    delete_candidate_redirect();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!admin_delete_validate_csrf($csrf_token)){
    $_SESSION["error"] = "La demande de suppression a expiré. Veuillez réessayer.";
    delete_candidate_redirect();
}

$candidate_id = isset($_POST["candidate_id"]) ? intval($_POST["candidate_id"]) : 0;

if($candidate_id <= 0){
    $_SESSION["error"] = "Identifiant d'intervenant invalide.";
    delete_candidate_redirect();
}

$conn->begin_transaction();
$stmt = mysqli_prepare($conn, "SELECT c.user_id, u.role_id FROM candidates c INNER JOIN users u ON u.id = c.user_id WHERE c.id = ? LIMIT 1 FOR UPDATE");

if(!$stmt){
    $conn->rollback();
    error_log("Erreur verrouillage intervenant : " . mysqli_error($conn));
    $_SESSION["error"] = "Impossible de vérifier cet intervenant pour le moment.";
    delete_candidate_redirect();
}

$user_id = 0;
$role_id = 0;
mysqli_stmt_bind_param($stmt, "i", $candidate_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $user_id, $role_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if($user_id <= 0 || (int)$role_id !== 3){
    $conn->rollback();
    $_SESSION["error"] = "Intervenant introuvable ou déjà supprimé.";
    delete_candidate_redirect();
}

$dependencies = array();
$mission_total = admin_delete_count($conn, "SELECT COUNT(*) FROM missions WHERE candidate_id = ? FOR UPDATE", $candidate_id);
$training_total = admin_delete_count($conn, "SELECT COUNT(*) FROM candidate_trainings WHERE candidate_id = ? FOR UPDATE", $candidate_id);
$review_total = admin_delete_count($conn, "SELECT COUNT(*) FROM service_reviews WHERE candidate_id = ? FOR UPDATE", $candidate_id);
$client_profile_total = admin_delete_count($conn, "SELECT COUNT(*) FROM clients WHERE user_id = ? FOR UPDATE", $user_id);
$assigned_mission_total = admin_delete_count($conn, "SELECT COUNT(*) FROM missions WHERE assigned_by = ? FOR UPDATE", $user_id);
$assigned_training_total = admin_delete_count($conn, "SELECT COUNT(*) FROM candidate_trainings WHERE assigned_by = ? FOR UPDATE", $user_id);

if($mission_total < 0 || $training_total < 0 || $review_total < 0 || $client_profile_total < 0 || $assigned_mission_total < 0 || $assigned_training_total < 0){
    $conn->rollback();
    $_SESSION["error"] = "Impossible de vérifier toutes les dépendances de cet intervenant.";
    delete_candidate_redirect();
}

if($mission_total > 0){ $dependencies[] = $mission_total . " mission(s)"; }
if($training_total > 0){ $dependencies[] = $training_total . " formation(s)"; }
if($review_total > 0){ $dependencies[] = $review_total . " évaluation(s)"; }
if($client_profile_total > 0){ $dependencies[] = "un profil client"; }
if($assigned_mission_total > 0){ $dependencies[] = $assigned_mission_total . " affectation(s) de mission"; }
if($assigned_training_total > 0){ $dependencies[] = $assigned_training_total . " affectation(s) de formation"; }

if(count($dependencies) > 0){
    $conn->rollback();
    $_SESSION["error"] = "Suppression impossible : cet intervenant possède " . implode(", ", $dependencies) . ". Désactivez ou rejetez plutôt son compte.";
    delete_candidate_redirect();
}

if(!admin_delete_execute($conn, "DELETE FROM candidate_skills WHERE candidate_id = ?", $candidate_id) ||
        !admin_delete_execute($conn, "DELETE FROM candidate_documents WHERE candidate_id = ?", $candidate_id) ||
        !admin_delete_execute($conn, "DELETE FROM candidates WHERE id = ?", $candidate_id) ||
        !admin_delete_execute($conn, "DELETE FROM users WHERE id = ?", $user_id)){
    $conn->rollback();
    $_SESSION["error"] = "Une erreur est survenue lors de la suppression de l'intervenant.";
    delete_candidate_redirect();
}

$conn->commit();
$_SESSION["success"] = "Intervenant supprimé avec succès.";
delete_candidate_redirect();

?>
