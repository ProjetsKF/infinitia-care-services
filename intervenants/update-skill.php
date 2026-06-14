<?php

session_start();

require_once("../config/database.php");

/* ==========================
   VERIFICATION CONNEXION
========================== */

if(!isset($_SESSION['user_id']))
{
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ==========================
   VERIFICATION POST
========================== */

if($_SERVER['REQUEST_METHOD'] != 'POST')
{
    header("Location: mes-competences.php");
    exit();
}

/* ==========================
   RECUPERATION DONNEES
========================== */

$skill_id = isset($_POST['skill_id'])
    ? (int)$_POST['skill_id']
    : 0;

$level = isset($_POST['level'])
    ? trim($_POST['level'])
    : '';

$years_experience = isset($_POST['years_experience'])
    ? (int)$_POST['years_experience']
    : 0;

$description = isset($_POST['description'])
    ? trim($_POST['description'])
    : '';

/* ==========================
   VALIDATION
========================== */

if($skill_id <= 0)
{
    $_SESSION['error'] =
    "Compétence invalide.";

    header("Location: mes-competences.php");
    exit();
}

/* ==========================
   VERIFICATION PROPRIETAIRE
========================== */

$sql = "
SELECT
    cs.id
FROM candidate_skills cs
INNER JOIN candidates c
    ON c.id = cs.candidate_id
WHERE cs.id = ?
AND c.user_id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt)
{
    $_SESSION['error'] =
    "Erreur de vérification.";

    header("Location: mes-competences.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $skill_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0)
{
    $_SESSION['error'] =
    "Accès refusé.";

    header("Location: mes-competences.php");
    exit();
}

/* ==========================
   MISE A JOUR
========================== */

$sql = "
UPDATE candidate_skills
SET
    level = ?,
    years_experience = ?,
    description = ?,
    updated_at = CURRENT_TIMESTAMP
WHERE id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt)
{
    $_SESSION['error'] =
    "Erreur de préparation SQL.";

    header("Location: mes-competences.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "sisi",
    $level,
    $years_experience,
    $description,
    $skill_id
);

if(mysqli_stmt_execute($stmt))
{
    $_SESSION['success'] =
    "Compétence mise à jour avec succès.";
}
else
{
    $_SESSION['error'] =
    "Erreur lors de la mise à jour.";
}

header("Location: mes-competences.php");
exit();

?>