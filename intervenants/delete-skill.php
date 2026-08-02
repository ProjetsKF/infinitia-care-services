<?php

session_start();

require_once("../config/database.php");

/* ==========================
   VERIFICATION CONNEXION
========================== */

if(!isset($_SESSION['user_id']))
{
    header("Location: " . app_url("login"));
    exit();
}

$user_id = $_SESSION['user_id'];

/* ==========================
   VERIFICATION ID
========================== */

if(
    !isset($_GET['id']) ||
    empty($_GET['id'])
)
{
    $_SESSION['error'] =
    "Compétence invalide.";

    header("Location: " . app_url("intervenant/competences"));
    exit();
}

$skill_id = (int)$_GET['id'];

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
AND cs.is_active = 1
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt)
{
    $_SESSION['error'] =
    "Erreur de vérification.";

    header("Location: " . app_url("intervenant/competences"));
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
    "Compétence introuvable.";

    header("Location: " . app_url("intervenant/competences"));
    exit();
}

/* ==========================
   SUPPRESSION LOGIQUE
========================== */

$sql = "
UPDATE candidate_skills
SET
    is_active = 0
WHERE id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt)
{
    $_SESSION['error'] =
    "Erreur de préparation SQL.";

    header("Location: " . app_url("intervenant/competences"));
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $skill_id
);

if(mysqli_stmt_execute($stmt))
{
    $_SESSION['success'] =
    "Compétence supprimée avec succès.";
}
else
{
    $_SESSION['error'] =
    "Erreur lors de la suppression.";
}

header("Location: " . app_url("intervenant/competences"));
exit();

?>