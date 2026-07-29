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

$document_type = isset($_POST['document_type'])
    ? trim($_POST['document_type'])
    : "";

if(empty($document_type)){

    $_SESSION['error'] =
    "Veuillez sélectionner un type de document.";

    header("Location: mes-documents.php");
    exit();

}

/* =====================================
   FICHIER
===================================== */

if(!isset($_FILES['document'])){

    $_SESSION['error'] = "Veuillez sélectionner un fichier.";
    header("Location: mes-documents.php");
    exit();

}

$upload_error = (int)$_FILES['document']['error'];

$upload_error_messages = array(
    UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la taille maximale autorisée par le serveur.",
    UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la taille maximale autorisée par le formulaire.",
    UPLOAD_ERR_PARTIAL => "Le fichier n’a été que partiellement téléversé.",
    UPLOAD_ERR_NO_FILE => "Veuillez sélectionner un fichier.",
    UPLOAD_ERR_NO_TMP_DIR => "Le dossier temporaire du serveur est indisponible.",
    UPLOAD_ERR_CANT_WRITE => "Le serveur n’a pas pu enregistrer le fichier.",
    UPLOAD_ERR_EXTENSION => "Une extension PHP a interrompu le téléversement."
);

if($upload_error !== UPLOAD_ERR_OK){

    error_log("Erreur upload document PHP : code " . $upload_error);

    $_SESSION['error'] = isset($upload_error_messages[$upload_error])
        ? $upload_error_messages[$upload_error]
        : "Une erreur inconnue est survenue pendant le téléversement.";

    header("Location: mes-documents.php");
    exit();

}

$max_file_size = 10 * 1024 * 1024;

if((int)$_FILES['document']['size'] > $max_file_size){

    $_SESSION['error'] = "Le fichier ne doit pas dépasser 10 Mo.";
    header("Location: mes-documents.php");
    exit();

}

/* =====================================
   EXTENSIONS AUTORISEES
===================================== */

$allowed_extensions = array(

    'pdf',

    'jpg',
    'jpeg',
    'png',

    'doc',
    'docx'

);

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
   VERIFICATION MIME
===================================== */

$allowed_mime_types = array(
    'pdf' => array('application/pdf'),
    'jpg' => array('image/jpeg'),
    'jpeg' => array('image/jpeg'),
    'png' => array('image/png'),
    'doc' => array('application/msword'),
    'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
);

if(!function_exists('finfo_open') || !is_uploaded_file($tmp_name)){

    $_SESSION['error'] = "Le fichier téléversé n’a pas pu être vérifié.";
    header("Location: mes-documents.php");
    exit();

}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = $finfo ? finfo_file($finfo, $tmp_name) : false;

if($finfo){
    finfo_close($finfo);
}

if($mime_type === false
    || !isset($allowed_mime_types[$extension])
    || !in_array($mime_type, $allowed_mime_types[$extension], true)){

    $_SESSION['error'] = "Le contenu du fichier ne correspond pas au format autorisé.";
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
