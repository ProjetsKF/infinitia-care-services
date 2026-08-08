<?php

session_start();

require_once(dirname(__DIR__) . "/config/database.php");
require_once(dirname(__DIR__) . "/includes/admin-delete-security.php");

admin_delete_require_admin();

function delete_user_redirect()
{
    header("Location: " . app_url("admin/utilisateurs"));
    exit();
}

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    $_SESSION["error"] = "La suppression d'un utilisateur doit être confirmée depuis l'interface administrateur.";
    delete_user_redirect();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!admin_delete_validate_csrf($csrf_token)){
    $_SESSION["error"] = "La demande de suppression a expiré. Veuillez réessayer.";
    delete_user_redirect();
}

$user_id = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : 0;
$connected_user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0;

if($user_id <= 0){
    $_SESSION["error"] = "Identifiant d'utilisateur invalide.";
    delete_user_redirect();
}

if($user_id === $connected_user_id){
    $_SESSION["error"] = "Vous ne pouvez pas supprimer votre propre compte administrateur.";
    delete_user_redirect();
}

$conn->begin_transaction();
$stmt = mysqli_prepare($conn, "SELECT id, role_id FROM users WHERE id = ? LIMIT 1 FOR UPDATE");

if(!$stmt){
    $conn->rollback();
    error_log("Erreur verrouillage utilisateur : " . mysqli_error($conn));
    $_SESSION["error"] = "Impossible de vérifier cet utilisateur pour le moment.";
    delete_user_redirect();
}

$existing_id = 0;
$role_id = 0;
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $existing_id, $role_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if($existing_id <= 0){
    $conn->rollback();
    $_SESSION["error"] = "Utilisateur introuvable ou déjà supprimé.";
    delete_user_redirect();
}

$client_id = admin_delete_count($conn, "SELECT COALESCE(MAX(id), 0) FROM clients WHERE user_id = ? FOR UPDATE", $user_id);
$candidate_id = admin_delete_count($conn, "SELECT COALESCE(MAX(id), 0) FROM candidates WHERE user_id = ? FOR UPDATE", $user_id);

if($client_id < 0 || $candidate_id < 0){
    $conn->rollback();
    $_SESSION["error"] = "Impossible de vérifier les profils liés à cet utilisateur.";
    delete_user_redirect();
}

$dependencies = array();

if($client_id > 0){
    $request_total = admin_delete_count($conn, "SELECT COUNT(*) FROM service_requests WHERE client_id = ? FOR UPDATE", $client_id);
    $payment_total = admin_delete_count($conn, "SELECT COUNT(*) FROM payments WHERE client_id = ? FOR UPDATE", $client_id);
    $client_review_total = admin_delete_count($conn, "SELECT COUNT(*) FROM service_reviews WHERE client_id = ? FOR UPDATE", $client_id);

    if($request_total < 0 || $payment_total < 0 || $client_review_total < 0){
        $conn->rollback();
        $_SESSION["error"] = "Impossible de vérifier les données liées à ce client.";
        delete_user_redirect();
    }

    if($request_total > 0){ $dependencies[] = $request_total . " demande(s) client"; }
    if($payment_total > 0){ $dependencies[] = $payment_total . " paiement(s)"; }
    if($client_review_total > 0){ $dependencies[] = $client_review_total . " évaluation(s) client"; }
}

if($candidate_id > 0){
    $mission_total = admin_delete_count($conn, "SELECT COUNT(*) FROM missions WHERE candidate_id = ? FOR UPDATE", $candidate_id);
    $training_total = admin_delete_count($conn, "SELECT COUNT(*) FROM candidate_trainings WHERE candidate_id = ? FOR UPDATE", $candidate_id);
    $candidate_review_total = admin_delete_count($conn, "SELECT COUNT(*) FROM service_reviews WHERE candidate_id = ? FOR UPDATE", $candidate_id);

    if($mission_total < 0 || $training_total < 0 || $candidate_review_total < 0){
        $conn->rollback();
        $_SESSION["error"] = "Impossible de vérifier les données liées à cet intervenant.";
        delete_user_redirect();
    }

    if($mission_total > 0){ $dependencies[] = $mission_total . " mission(s)"; }
    if($training_total > 0){ $dependencies[] = $training_total . " formation(s)"; }
    if($candidate_review_total > 0){ $dependencies[] = $candidate_review_total . " évaluation(s) intervenant"; }
}

$assigned_mission_total = admin_delete_count($conn, "SELECT COUNT(*) FROM missions WHERE assigned_by = ? FOR UPDATE", $user_id);
$assigned_training_total = admin_delete_count($conn, "SELECT COUNT(*) FROM candidate_trainings WHERE assigned_by = ? FOR UPDATE", $user_id);

if($assigned_mission_total < 0 || $assigned_training_total < 0){
    $conn->rollback();
    $_SESSION["error"] = "Impossible de vérifier l'historique administratif de cet utilisateur.";
    delete_user_redirect();
}

if($assigned_mission_total > 0){ $dependencies[] = $assigned_mission_total . " affectation(s) de mission"; }
if($assigned_training_total > 0){ $dependencies[] = $assigned_training_total . " affectation(s) de formation"; }

if(count($dependencies) > 0){
    $conn->rollback();
    $_SESSION["error"] = "Cet utilisateur possède encore " . implode(", ", $dependencies) . ". Désactivez ou suspendez le compte au lieu de le supprimer.";
    delete_user_redirect();
}

if($candidate_id > 0 &&
        (!admin_delete_execute($conn, "DELETE FROM candidate_skills WHERE candidate_id = ?", $candidate_id) ||
         !admin_delete_execute($conn, "DELETE FROM candidate_documents WHERE candidate_id = ?", $candidate_id) ||
         !admin_delete_execute($conn, "DELETE FROM candidates WHERE id = ?", $candidate_id))){
    $conn->rollback();
    $_SESSION["error"] = "Impossible de supprimer le profil intervenant associé.";
    delete_user_redirect();
}

if($client_id > 0 && !admin_delete_execute($conn, "DELETE FROM clients WHERE id = ?", $client_id)){
    $conn->rollback();
    $_SESSION["error"] = "Impossible de supprimer le profil client associé.";
    delete_user_redirect();
}

if(!admin_delete_execute($conn, "DELETE FROM users WHERE id = ?", $user_id)){
    $conn->rollback();
    $_SESSION["error"] = "Une erreur est survenue lors de la suppression de l'utilisateur.";
    delete_user_redirect();
}

$conn->commit();
$_SESSION["success"] = "Utilisateur supprimé avec succès.";
delete_user_redirect();

?>
