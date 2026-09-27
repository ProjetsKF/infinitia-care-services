<?php

if(PHP_SAPI !== "cli"){
    http_response_code(404);
    exit;
}

$password = getenv("INFINITIA_ADMIN_PASSWORD");

if($password === false || $password === ""){
    fwrite(STDERR, "La variable INFINITIA_ADMIN_PASSWORD est obligatoire.\n");
    exit(1);
}

echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;

?>
