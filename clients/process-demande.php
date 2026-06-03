<?php

session_start();

require_once("../config/database.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // ========================================
    // VERIFIER UTILISATEUR CONNECTE
    // ========================================

    if(!isset($_SESSION["user_id"])){

        $_SESSION["error"] =
        "Veuillez vous connecter.";

        header("Location: ../login.php");
        exit();

    }

    // ========================================
    // RECUPERER CLIENT_ID
    // ========================================

    $user_id = $_SESSION["user_id"];

    $sql_client = "

    SELECT id

    FROM clients

    WHERE user_id = ?

    LIMIT 1

    ";

    $stmt_client = $conn->prepare($sql_client);

    $stmt_client->bind_param(
        "i",
        $user_id
    );

    $stmt_client->execute();

    $result_client =
    $stmt_client->get_result();

    if($result_client->num_rows == 0){

        $_SESSION["error"] =
        "Profil client introuvable.";

        header("Location: mes-demandes.php");
        exit();

    }

    $client =
    $result_client->fetch_assoc();

    $client_id =
    $client["id"];

    // ========================================
    // DONNEES FORMULAIRE
    // ========================================

    $category_id =
    intval($_POST["category_id"]);

    $title =
    trim($_POST["title"]);

    $description =
    trim($_POST["description"]);

    $location =
    trim($_POST["location"]);

    $service_date =
    $_POST["service_date"];

    $duration =
    intval($_POST["duration"]);

    $budget =
    floatval($_POST["budget"]);

    $urgency_level =
    $_POST["urgency_level"];

    $status =
    "en_attente";

    // ========================================
    // INSERTION DEMANDE
    // ========================================

    $sql = "

    INSERT INTO service_requests(

        client_id,
        category_id,
        title,
        description,
        location,
        service_date,
        duration,
        budget,
        urgency_level,
        status

    )

    VALUES(

        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?

    )

    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(

        "iisssssdss",

        $client_id,
        $category_id,
        $title,
        $description,
        $location,
        $service_date,
        $duration,
        $budget,
        $urgency_level,
        $status

    );

    // ========================================
    // EXECUTION
    // ========================================

    if($stmt->execute()){

        $_SESSION["success"] =
        "Votre demande a été envoyée avec succès.";

    }else{

        $_SESSION["error"] =
        "Erreur lors de l'enregistrement : " .
        $stmt->error;

    }

    header("Location: mes-demandes.php");
    exit();

}

header("Location: mes-demandes.php");
exit();

?>