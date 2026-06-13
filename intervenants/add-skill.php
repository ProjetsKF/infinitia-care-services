<?php

session_start();

require_once("../config/database.php");

/* ==========================
   VERIFICATION CONNEXION
========================== */

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}

$user_id = $_SESSION['user_id'];

/* ==========================
   RECUPERATION CANDIDAT
========================== */

$sql = "
SELECT id
FROM candidates
WHERE user_id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    $_SESSION['error'] =
    "Erreur lors de la récupération du profil.";

    header("Location: mes-competences.php");
    exit();

}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$candidate = mysqli_fetch_assoc($result);

if(!$candidate){

    $_SESSION['error'] =
    "Profil candidat introuvable.";

    header("Location: mes-competences.php");
    exit();

}

$candidate_id = $candidate['id'];

/* ==========================
   VERIFICATION POST
========================== */

if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    header("Location: mes-competences.php");
    exit();

}

/* ==========================
   RECUPERATION DONNEES
========================== */

$skill_name =
trim($_POST['skill_name'] ?? '');

$level =
trim($_POST['level'] ?? 'Débutant');

$years_experience =
(int)($_POST['years_experience'] ?? 0);

$description =
trim($_POST['description'] ?? '');

/* ==========================
   VALIDATION
========================== */

if(empty($skill_name)){

    $_SESSION['error'] =
    "Veuillez saisir une compétence.";

    header("Location: mes-competences.php");
    exit();

}

/* ==========================
   VERIFICATION DOUBLON
========================== */

$sql = "
SELECT id
FROM candidate_skills
WHERE candidate_id = ?
AND skill_name = ?
AND is_active = 1
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    $_SESSION['error'] =
    "Erreur lors de la vérification.";

    header("Location: mes-competences.php");
    exit();

}

mysqli_stmt_bind_param(

    $stmt,

    "is",

    $candidate_id,
    $skill_name

);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0){

    $_SESSION['error'] =
    "Cette compétence existe déjà.";

    header("Location: mes-competences.php");
    exit();

}

/* ==========================
   INSERTION
========================== */

$sql = "
INSERT INTO candidate_skills
(

    candidate_id,
    skill_name,
    level,
    description,
    years_experience,
    is_active

)

VALUES
(

    ?, ?, ?, ?, ?, 1

)
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    $_SESSION['error'] =
    "Erreur de préparation SQL.";

    header("Location: mes-competences.php");
    exit();

}

mysqli_stmt_bind_param(

    $stmt,

    "isssi",

    $candidate_id,
    $skill_name,
    $level,
    $description,
    $years_experience

);

if(mysqli_stmt_execute($stmt)){

    $_SESSION['success'] =
    "Compétence enregistrée avec succès.";

}else{

    $_SESSION['error'] =
    "Erreur lors de l'enregistrement.";

}

header("Location: mes-competences.php");
exit();

?>