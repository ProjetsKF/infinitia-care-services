<?php

session_start();

require_once("../config/database.php");

$user_id = $_SESSION['user_id'];

$sql = "

SELECT

    u.*,

    c.birth_date,
    c.gender,
    c.address,
    c.city,
    c.nationality,
    c.marital_status,
    c.education_level,
    c.experience_years,
    c.bio,
    c.availability_status,
    c.verification_status,
    c.emergency_contact

FROM users u

LEFT JOIN candidates c
ON u.id = c.user_id

WHERE u.id = ?

LIMIT 1

";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$intervenant = $result->fetch_assoc();


?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

      Profil Intervenant | INFINITIA

    </title>

    <link rel="icon"
          type="image/x-icon"
          href="../assets/images/ico.ico">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="../assets/css/style.css">

</head>

<body>

<div class="dashboard">

    <?php

   $current_page = "profil";

    include("menuin.php");

    ?>

<div class="main-content" id="printArea">
    
    <div class="page-title">
    Mon Profil
    </div>

<div class="profile-header-card">

    <div class="profile-header-content">

        <!-- PHOTO -->

        <div class="profile-photo-section">

            <div class="profile-photo">

                <?php if(!empty($intervenant['profile_photo'])): ?>

                    <img
                        src="../<?php echo htmlspecialchars($intervenant['profile_photo']); ?>"
                        alt="Photo de profil">

                <?php else: ?>

                    <img
                        src="../assets/images/default-user.png"
                        alt="Photo de profil">

                <?php endif; ?>

            </div>

            <!-- EXPERIENCE -->

            <div class="experience-badge-photo">

                <i class="material-icons tiny">workspace_premium</i>

                <?php echo (int)$intervenant['experience_years']; ?> ans d'expérience

            </div>

        </div>

        <!-- INFOS -->

        <div class="profile-info">

            <h2>

                <?php
                echo htmlspecialchars(
                    $intervenant['first_name'].' '.
                    $intervenant['last_name']
                );
                ?>

            </h2>

            <p class="profile-role">

                Intervenant INFINITIA CARE SERVICES

            </p>

            <div class="profile-details">

                <span>

                    <i class="material-icons tiny">location_on</i>

                    <?php echo htmlspecialchars($intervenant['city']); ?>

                </span>

                <span>

                    <i class="material-icons tiny">phone</i>

                    <?php echo htmlspecialchars($intervenant['phone']); ?>

                </span>

                <span>

                    <i class="material-icons tiny">email</i>

                    <?php echo htmlspecialchars($intervenant['email']); ?>

                </span>

            </div>

        </div>

        <!-- ACTIONS -->

        <div class="profile-actions">

            <a
                href="#!"
                onclick="printProfile();"
                class="btn-large blue darken-4 action-btn">

                <i class="material-icons left">print</i>

                Imprimer le profil

            </a>

            <a
                href="#modalProfil"
                class="btn-large teal modal-trigger action-btn">

                <i class="material-icons left">edit</i>

                Modifier mon profil

            </a>

            <a
                href="#modalProfessionnel"
                class="btn-large orange modal-trigger action-btn">

                <i class="material-icons left">work</i>

                Infos professionnelles

            </a>

            <a
                href="#modalPassword"
                class="btn-large red modal-trigger action-btn">

                <i class="material-icons left">lock</i>

                Sécurité du compte

            </a>

        </div>

    </div>

</div>

<br>

<div class="table-card">

    <div class="table-title">

        Informations personnelles

    </div>

    <table class="highlight">

        <tr>
            <th>Date de naissance</th>
            <td><?php echo htmlspecialchars($intervenant['birth_date']); ?></td>
        </tr>

        <tr>
            <th>Sexe</th>
            <td><?php echo htmlspecialchars($intervenant['gender']); ?></td>
        </tr>

        <tr>
            <th>Adresse</th>
            <td><?php echo htmlspecialchars($intervenant['address']); ?></td>
        </tr>

        <tr>
            <th>Ville</th>
            <td><?php echo htmlspecialchars($intervenant['city']); ?></td>
        </tr>

        <tr>
            <th>Nationalité</th>
            <td><?php echo htmlspecialchars($intervenant['nationality']); ?></td>
        </tr>

        <tr>
            <th>Etat civil</th>
            <td><?php echo htmlspecialchars($intervenant['marital_status']); ?></td>
        </tr>

        <tr>
            <th>Contact d'urgence</th>
            <td><?php echo htmlspecialchars($intervenant['emergency_contact']); ?></td>
        </tr>

    </table>

</div>

<br>

<div class="table-card">

    <div class="table-title">

        Informations professionnelles

    </div>

    <table class="highlight">

        <tr>
            <th>Niveau d'étude</th>
            <td><?php echo htmlspecialchars($intervenant['education_level']); ?></td>
        </tr>

        <tr>
            <th>Expérience</th>
            <td><?php echo htmlspecialchars($intervenant['experience_years']); ?> ans</td>
        </tr>

        <tr>
            <th>Disponibilité</th>
            <td><?php echo htmlspecialchars($intervenant['availability_status']); ?></td>
        </tr>

        <tr>
            <th>Vérification</th>
            <td><?php echo htmlspecialchars($intervenant['verification_status']); ?></td>
        </tr>

        <tr>
            <th>Statut</th>
            <td><?php echo htmlspecialchars($intervenant['status']); ?></td>
        </tr>

    </table>

</div>

<br>

<div class="table-card">

    <div class="table-title">

        Biographie

    </div>

    <p style="padding:20px;">

        <?php echo nl2br(htmlspecialchars($intervenant['bio'])); ?>

    </p>

        </div>


    </div>
  
</div>
        <!-- MODAL MODIFICATION N°1 -->

  <div id="modalProfil" class="modal">

    <div class="modal-content">

        <h4>Modifier mon profil</h4>

        <form action="update-profile.php"
              method="POST"
              enctype="multipart/form-data">

            <div class="input-field">
                <input type="text"
                       name="first_name"
                       value="<?php echo htmlspecialchars($intervenant['first_name']); ?>">
                <label class="active">Prénom</label>
            </div>

            <div class="input-field">
                <input type="text"
                       name="last_name"
                       value="<?php echo htmlspecialchars($intervenant['last_name']); ?>">
                <label class="active">Nom</label>
            </div>

            <div class="input-field">
                <input type="tel"
                       name="phone"
                       value="<?php echo htmlspecialchars($intervenant['phone']); ?>">
                <label class="active">Téléphone</label>
            </div>
            <div class="input-field">

            <input
                type="date"
                name="birth_date"
                value="<?php echo htmlspecialchars($intervenant['birth_date']); ?>">

            <label class="active">
                Date de naissance
            </label>

        </div>

        <div class="input-field">

    <select name="gender">

        <option value="Homme"
            <?php if($intervenant['gender']=="Homme"){ echo "selected"; } ?>>
            Homme
        </option>

        <option value="Femme"
            <?php if($intervenant['gender']=="Femme"){ echo "selected"; } ?>>
            Femme
        </option>

    </select>

    <label>
        Sexe
    </label>

</div>

            <div class="input-field">
                <input type="text"
                       name="city"
                       value="<?php echo htmlspecialchars($intervenant['city']); ?>">
                <label class="active">Ville</label>
            </div>

            <div class="input-field">
                <textarea
                name="address"
                class="materialize-textarea"><?php echo htmlspecialchars($intervenant['address']); ?></textarea>

                <label class="active">Adresse</label>
            </div>

            <div class="file-field input-field">

                <div class="btn teal">

                    <span>Photo</span>

                    <input
                    type="file"
                    name="profile_photo">

                </div>

                <div class="file-path-wrapper">

                    <input
                    class="file-path validate"
                    type="text">

                </div>

            </div>

            <br>

            <button
            type="submit"
            class="btn teal">

                Enregistrer

            </button>

        </form>

    </div>

</div>


 <!-- MODAL MODIFICATION N°2 -->

<div id="modalProfessionnel" class="modal">

    <div class="modal-content">

        <h4>Informations professionnelles</h4>

        <form action="update-professional-profile.php"
              method="POST">

            <div class="input-field">

                <input
                type="text"
                name="nationality"
                value="<?php echo htmlspecialchars($intervenant['nationality']); ?>">

                <label class="active">
                    Nationalité
                </label>

            </div>

            <div class="input-field">

                <input
                type="text"
                name="education_level"
                value="<?php echo htmlspecialchars($intervenant['education_level']); ?>">

                <label class="active">
                    Niveau d'étude
                </label>

            </div>

            <div class="input-field">

                <input
                type="number"
                min="0"
                name="experience_years"
                value="<?php echo htmlspecialchars($intervenant['experience_years']); ?>">

                <label class="active">
                    Années d'expérience
                </label>

            </div>

            <div class="input-field">

                <select name="availability_status">

                    <option value="disponible"
                    <?php if($intervenant['availability_status']=="disponible") echo "selected"; ?>>
                        Disponible
                    </option>

                    <option value="occupé"
                    <?php if($intervenant['availability_status']=="occupé") echo "selected"; ?>>
                        Occupé
                    </option>

                    <option value="hors_ligne"
                    <?php if($intervenant['availability_status']=="hors_ligne") echo "selected"; ?>>
                        Hors ligne
                    </option>

                </select>

                <label>
                    Disponibilité
                </label>

            </div>

            <div class="input-field">

                <textarea
                name="bio"
                class="materialize-textarea"><?php echo htmlspecialchars($intervenant['bio']); ?></textarea>

                <label class="active">
                    Présentation professionnelle
                </label>

            </div>

            <button
            type="submit"
            class="btn orange">

                Enregistrer

            </button>

        </form>

    </div>

</div>

 <!-- MODAL MODIFICATION N°3 -->

<div id="modalPassword" class="modal">

    <div class="modal-content">

        <h4>Sécurité du compte</h4>

        <form
        action="update-password.php"
        method="POST">

            <div class="input-field">

                <input
                type="password"
                id="current_password"
                name="current_password"
                required>

                <label for="current_password">

                    Mot de passe actuel

                </label>

            </div>

            <div class="input-field">

                <input
                type="password"
                id="new_password"
                name="new_password"
                required>

                <label for="new_password">

                    Nouveau mot de passe

                </label>

            </div>

            <div class="input-field">

                <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required>

                <label for="confirm_password">

                    Confirmer le nouveau mot de passe

                </label>

            </div>

            <div class="modal-footer"
                 style="background:none;">

                <button
                type="submit"
                class="btn red">

                    Mettre à jour

                </button>

            </div>

        </form>

    </div>

</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>

document.addEventListener('DOMContentLoaded', function() {

    var elems =
    document.querySelectorAll('.modal');

    M.Modal.init(elems);

});


document.addEventListener('DOMContentLoaded', function() {

    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

    M.updateTextFields();

    var textareas =
    document.querySelectorAll('.materialize-textarea');

    M.textareaAutoResize(textareas);

    M.FormSelect.init(document.querySelectorAll('select'));

});

</script>

<?php if(isset($_SESSION['success'])): ?>

<script>

document.addEventListener('DOMContentLoaded', function(){

    alert('<?php echo addslashes($_SESSION["success"]); ?>');

});

</script>

<?php unset($_SESSION['success']); ?>

<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>

<script>

document.addEventListener('DOMContentLoaded', function(){

    alert('<?php echo addslashes($_SESSION["error"]); ?>');

});

</script>

<?php unset($_SESSION['error']); ?>

<?php endif; ?>

<script>

function printProfile()
{
    var content = document.getElementById("printArea").innerHTML;

    var printWindow = window.open("", "", "width=1000,height=800");

    printWindow.document.open();

    printWindow.document.write('\
    <html>\
    <head>\
        <title>Profil intervenant</title>\
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">\
        <link rel="stylesheet" href="../assets/css/materialize.min.css">\
        <link rel="stylesheet" href="../assets/css/style.css">\
        <style>\
            body{\
                margin:30px;\
                background:#ffffff;\
            }\
            .action-btn,\
            .modal-trigger,\
            .topbar{\
                display:none !important;\
            }\
        </style>\
    </head>\
    <body>' + content + '</body>\
    </html>');

    printWindow.document.close();

    printWindow.focus();

    setTimeout(function(){

        printWindow.print();

        printWindow.close();

    },500);
}

</script>
</body>
</html>