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
   RECUPERATION CANDIDAT
========================== */

$sql = "
SELECT id
FROM candidates
WHERE user_id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt)
{
    $_SESSION['error'] =
    "Erreur lors de la récupération du profil.";

    header("Location: " . app_url("intervenant/competences"));
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

if(!$candidate)
{
    $_SESSION['error'] =
    "Profil candidat introuvable.";

    header("Location: " . app_url("intervenant/competences"));
    exit();
}

$candidate_id = $candidate['id'];

/* ==========================
   VERIFICATION POST
========================== */

if($_SERVER['REQUEST_METHOD'] != 'POST')
{
    header("Location: " . app_url("intervenant/competences"));
    exit();
}

/* ==========================
   COMPETENCES
========================== */

if(
    !isset($_POST['skills']) ||
    !is_array($_POST['skills']) ||
    count($_POST['skills']) == 0
)
{
    $_SESSION['error'] =
    "Veuillez sélectionner au moins une compétence.";

    header("Location: " . app_url("intervenant/competences"));
    exit();
}

$skills = $_POST['skills'];

/* ==========================
   AUTRES CHAMPS
========================== */

if(isset($_POST['level']))
{
    $level = trim($_POST['level']);
}
else
{
    $level = 'Débutant';
}

if(isset($_POST['years_experience']))
{
    $years_experience = (int)$_POST['years_experience'];
}
else
{
    $years_experience = 0;
}

if(isset($_POST['description']))
{
    $description = trim($_POST['description']);
}
else
{
    $description = '';
}

/* ==========================
   INSERTIONS
========================== */

$insert_count = 0;

foreach($skills as $skill_name)
{
    $skill_name = trim($skill_name);

    if(empty($skill_name))
    {
        continue;
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

    if(!$stmt)
    {
        continue;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $candidate_id,
        $skill_name
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0)
    {
        continue;
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

    if(!$stmt)
    {
        continue;
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

    if(mysqli_stmt_execute($stmt))
    {
        $insert_count++;
    }
}

/* ==========================
   MESSAGE FINAL
========================== */

if($insert_count > 0)
{
    $_SESSION['success'] =
    $insert_count . " compétence(s) enregistrée(s) avec succès.";
}
else
{
    $_SESSION['error'] =
    "Aucune nouvelle compétence n'a été enregistrée.";
}

header("Location: " . app_url("intervenant/competences"));
exit();

?>