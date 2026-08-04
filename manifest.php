<?php

require_once(__DIR__ . "/config/app.php");

header("Content-Type: application/manifest+json; charset=UTF-8");
header("Cache-Control: public, max-age=3600");

$manifest = array(
    "name" => "INFINITIA Care Services",
    "short_name" => "INFINITIA Care",
    "description" => "Plateforme professionnelle de gestion des services d'assistance à domicile, des clients et des intervenantes.",
    "lang" => "fr",
    "dir" => "ltr",
    "start_url" => app_url(""),
    "scope" => app_url(""),
    "display" => "standalone",
    "orientation" => "portrait-primary",
    "theme_color" => "#D84B8A",
    "background_color" => "#FFFFFF",
    "icons" => array(
        array("src" => app_url("assets/images/pwa/icon-192.png"), "sizes" => "192x192", "type" => "image/png", "purpose" => "any"),
        array("src" => app_url("assets/images/pwa/icon-512.png"), "sizes" => "512x512", "type" => "image/png", "purpose" => "any"),
        array("src" => app_url("assets/images/pwa/icon-maskable-192.png"), "sizes" => "192x192", "type" => "image/png", "purpose" => "maskable"),
        array("src" => app_url("assets/images/pwa/icon-maskable-512.png"), "sizes" => "512x512", "type" => "image/png", "purpose" => "maskable")
    )
);

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
