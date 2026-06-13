<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once("../config/database.php");

/* =====================================
   VERIFICATION CONNEXION
===================================== */

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit();

}

$user_id = (int)$_SESSION['user_id'];

/* =====================================
   VERIFICATION POST
===================================== */

if($_SERVER['REQUEST_METHOD'] != 'POST'){

    header("Location: mes-documents.php");
    exit();

}

/* =====================================
   RECUPERATION CANDIDAT
===================================== */

$sql = "

SELECT id

FROM candidates

WHERE user_id = ?

LIMIT 1

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0){

    $_SESSION['error'] =
    "Profil candidat introuvable.";

    header("Location: mes-documents.php");
    exit();

}

$candidate = mysqli_fetch_assoc($result);

$candidate_id = (int)$candidate['id'];

/* =====================================
   TYPE DOCUMENT
===================================== */

$document_type = trim($_POST['document_type']);

if(empty($document_type)){

    $_SESSION['error'] =
    "Veuillez sélectionner un type de document.";

    header("Location: mes-documents.php");
    exit();

}

/* =====================================
   FICHIER
===================================== */

if(
    !isset($_FILES['document']) ||
    $_FILES['document']['error'] != 0
){

    $_SESSION['error'] =
    "Veuillez sélectionner un fichier.";

    header("Location: mes-documents.php");
    exit();

}

/* =====================================
   EXTENSIONS AUTORISEES
===================================== */

$allowed_extensions = [

    'pdf',

    'jpg',
    'jpeg',
    'png',

    'doc',
    'docx'

];

$file_name =
$_FILES['document']['name'];

$tmp_name =
$_FILES['document']['tmp_name'];

$extension =
strtolower(
    pathinfo(
        $file_name,
        PATHINFO_EXTENSION
    )
);

if(
    !in_array(
        $extension,
        $allowed_extensions
    )
){

    $_SESSION['error'] =
    "Format non autorisé.";

    header("Location: mes-documents.php");
    exit();

}

/* =====================================
   DOSSIER UPLOAD
===================================== */

$upload_dir =
"../uploads/documents/";

if(!is_dir($upload_dir)){

    mkdir(
        $upload_dir,
        0777,
        true
    );

}

/* =====================================
   NOM UNIQUE
===================================== */

$new_file_name =

time()
."_"
.uniqid()
."."
.$extension;

$destination =

$upload_dir
.$new_file_name;

/* =====================================
   UPLOAD
===================================== */

if(
    !move_uploaded_file(
        $tmp_name,
        $destination
    )
){

    $_SESSION['error'] =
    "Erreur lors du téléversement.";

    header("Location: mes-documents.php");
    exit();

}

/* =====================================
   CHEMIN BD
===================================== */

$file_path =

"uploads/documents/"
.$new_file_name;

/* =====================================
   INSERTION
===================================== */

$sql = "

INSERT INTO candidate_documents(

    candidate_id,
    document_type,
    file_path,
    verified

)

VALUES(

    ?,
    ?,
    ?,
    0

)

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    $_SESSION['error'] =
    mysqli_error($conn);

    header("Location: mes-documents.php");
    exit();

}

mysqli_stmt_bind_param(

    $stmt,

    "iss",

    $candidate_id,
    $document_type,
    $file_path

);

if(mysqli_stmt_execute($stmt)){

    $_SESSION['success'] =
    "Document ajouté avec succès.";

}else{

    $_SESSION['error'] =
    mysqli_stmt_error($stmt);

}

/* =====================================
   REDIRECTION
===================================== */

header("Location: mes-documents.php");
exit();

?>