<!-- NAVBAR -->
<?php

$current_page = basename($_SERVER['PHP_SELF']);

?>

<nav class="blue darken-4">

    <div class="nav-wrapper"
         style="
         display:flex;
         align-items:center;
         justify-content:space-between;
         padding:0 30px;">

        <!-- LOGO -->

        <a href="index.php"
           class="brand-logo"
           style="
           position:relative;
           left:auto;
           transform:none;
           margin-top:5px;">

            <img
            src="assets/images/brand1.PNG"
            alt="Infinitia Logo"
            style="
            height:50px;
            width:auto;
            display:block;">

        </a>

        <!-- MENU DESKTOP -->

        <ul class="right hide-on-med-and-down"
            style="
            display:flex;
            align-items:center;
            gap:15px;
            margin:0;">

           <li>
    <a href="index.php"
       class="<?= ($current_page == 'index.php') ? 'active-nav' : ''; ?>">
        Accueil
    </a>
</li>

<li>
    <a href="services.php"
       class="<?= ($current_page == 'services.php') ? 'active-nav' : ''; ?>">
        Services
    </a>
</li>

<li>
    <a href="about.php"
       class="<?= ($current_page == 'about.php') ? 'active-nav' : ''; ?>">
        À propos
    </a>
</li>

<li>
    <a href="contact.php"
       class="<?= ($current_page == 'contact.php') ? 'active-nav' : ''; ?>">
        Contact
    </a>
</li>

            <li>

                <a href="login.php"
                   class="btn blue lighten-1"
                   style="
                   border-radius:40px;
                   padding:0 25px;">

                    <i class="material-icons left">
                        login
                    </i>

                    Connexion

                </a>

            </li>

            <li>

                <a href="register.php"
                   class="btn pink accent-2"
                   style="
                   border-radius:40px;
                   padding:0 25px;">

                    <i class="material-icons left">
                        person_add
                    </i>

                    S'inscrire

                </a>

            </li>

        </ul>

        <!-- MENU MOBILE -->

        <a href="#"
           data-target="mobile-menu"
           class="sidenav-trigger">

            <i class="material-icons">
                menu
            </i>

        </a>

    </div>

</nav>