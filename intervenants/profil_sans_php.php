<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        Profil utilisateur | INFINITIA

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

    

    <div class="main-content">

       <!-- =========================================
     HEADER PROFIL
========================================= -->

<div class="profile-header-card">

    <div class="profile-left">

        <div class="profile-avatar">

            

                <img
                src="../uploads/profiles/<?= htmlspecialchars($intervenant['profile_photo']); ?>"
                alt="Photo Profil">

            

                <i class="material-icons">
                    account_circle
                </i>

            

        </div>

        <div class="profile-details">

           <h2>

                <?= htmlspecialchars(
                    ($intervenant['first_name'] ?? '')
                    .' '.
                    ($intervenant['last_name'] ?? '')
                ); ?>

            </h2>

            <p class="profile-role">

                Intervenant INFINITIA CARE SERVICES

            </p>

            <div class="profile-meta">

                <span>

                    <i class="material-icons tiny">
                        location_on
                    </i>

                    <?= !empty($intervenant['city'] ?? '')
                        ? htmlspecialchars($intervenant['city'])
                        : 'Ville non renseignée'; ?>

                </span>

                <span>

                    <i class="material-icons tiny">
                        phone
                    </i>

                    <?= !empty($intervenant['phone'] ?? '')
                        ? htmlspecialchars($intervenant['phone'])
                        : 'Téléphone non renseigné'; ?>

                </span>

                <span>

                    <i class="material-icons tiny">
                        email
                    </i>

                    <?= htmlspecialchars(
                        $intervenant['email']
                        ?? 'Non renseigné'
                    ); ?>

                </span>

            </div>

        </div>

    </div>

    <div class="profile-right">

        

            <div class="card-panel green lighten-4 green-text text-darken-4">

                <?= $_SESSION['success']; ?>

            </div>

            

            

            

            <div class="card-panel red lighten-4 red-text text-darken-4">

                <?= $_SESSION['error']; ?>

            </div>

            

            

                    

<div class="profile-badges">

    <!-- DISPONIBILITE -->

    <span class="<?= $badgeDisponibilite; ?>">

        

    </span>

    <!-- VERIFICATION -->

    <span class="<?= $badgeVerification; ?>">

        

    </span>

    <!-- EXPERIENCE -->

    <span class="badge-experience">

        

    </span>

</div>

       <a href="#modalProfil"
   class="btn waves-effect waves-light modal-trigger">

    Ouvrir le modal test

</a>

    </div>

</div>


        <!-- PROFIL RAPIDE -->
        <br>

        <div class="table-card">

            <div class="table-title">

               Informations personnelles

            </div>

          <table class="highlight responsive-table">

    <tbody>

        <tr>

            <th>Nom complet</th>

            <td>

                <?= htmlspecialchars(
                    ($intervenant['first_name'] ?? '')
                    .' '.
                    ($intervenant['last_name'] ?? '')
                ); ?>

            </td>

        </tr>

        <tr>

            <th>Email</th>

            <td>

                <?= htmlspecialchars(
                    $intervenant['email']
                    ?? 'Non renseigné'
                ); ?>

            </td>

        </tr>

        <tr>

            <th>Téléphone</th>

            <td>

                <?= htmlspecialchars(
                    $intervenant['phone']
                    ?? 'Non renseigné'
                ); ?>

            </td>

        </tr>

        <tr>

            <th>Ville</th>

            <td>

                <?= htmlspecialchars(
                    $intervenant['city']
                    ?? 'Non renseignée'
                ); ?>

            </td>

        </tr>

        <tr>

            <th>Expérience</th>

            <td>

                <?= htmlspecialchars(
                    $intervenant['experience_years']
                    ?? '0'
                ); ?> ans

            </td>

        </tr>

        <tr>

            <th>Disponibilité</th>

            <td>

                <?= htmlspecialchars(
                    $intervenant['availability_status']
                    ?? 'hors_ligne'
                ); ?>

            </td>

        </tr>

    </tbody>

</table>

        </div>


    </div>

</div>

<!-- =========================================
     MODAL TEST
========================================= -->

<div id="modalProfil" class="modal">

    <div class="modal-content">

        <h4>Test Modal</h4>

        <p>

            Si vous voyez ce message, cela signifie que
            Materialize et le modal fonctionnent correctement.

        </p>

    </div>

    <div class="modal-footer">

        <a href="#!"
           class="modal-close btn blue">

            Fermer

        </a>

    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function(){

    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

});

</script>
</body>
</html>