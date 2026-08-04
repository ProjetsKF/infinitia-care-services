<?php

require_once(__DIR__ . "/config/app.php");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#D84B8A">
    <title>Connexion indisponible | INFINITIA Care Services</title>
    <style>
        *{box-sizing:border-box}
        html,body{min-height:100%;margin:0}
        body{align-items:center;background:linear-gradient(145deg,#fff7fb 0%,#fff 55%,#f4f6ff 100%);color:#263238;display:flex;font-family:Arial,Helvetica,sans-serif;justify-content:center;padding:24px}
        .offline-card{background:#fff;border-radius:22px;box-shadow:0 18px 50px rgba(38,50,56,.14);max-width:560px;padding:44px 36px;text-align:center;width:100%}
        .offline-icon{border-radius:24px;height:112px;margin-bottom:24px;width:112px}
        h1{color:#D84B8A;font-size:32px;line-height:1.2;margin:0 0 18px}
        p{color:#546e7a;font-size:18px;line-height:1.65;margin:0 auto 30px;max-width:460px}
        .retry-button{background:#D84B8A;border:0;border-radius:999px;color:#fff;cursor:pointer;font-size:17px;font-weight:700;min-height:48px;padding:12px 30px}
        .retry-button:focus,.retry-button:hover{background:#bd356f;outline:3px solid rgba(216,75,138,.25);outline-offset:3px}
        @media (max-width:480px){.offline-card{padding:34px 22px}.offline-icon{height:92px;width:92px}h1{font-size:27px}p{font-size:16px}}
    </style>
</head>
<body>
    <main class="offline-card">
        <img class="offline-icon" src="<?php echo app_url_html("assets/images/pwa/icon-192.png"); ?>" alt="INFINITIA Care Services">
        <h1>Connexion indisponible</h1>
        <p>INFINITIA Care Services ne peut pas se connecter au serveur. Vérifiez votre connexion Internet, puis réessayez.</p>
        <button type="button" class="retry-button" onclick="window.location.reload();">Réessayer</button>
    </main>
</body>
</html>
