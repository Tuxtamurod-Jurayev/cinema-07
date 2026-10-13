<?php

// Alwaysdata MySQL sozlamalari
$db_host = "mysql-zevs97.alwaysdata.net";
$db_user = "zevs97";
$db_pass = "19971125mm!!";
$db_name = "zevs97_cinema";

// 1. Alwaysdata serveriga ulanish
$connect = @new mysqli($db_host, $db_user, $db_pass, $db_name);

// 2. Agar ulanmasa, lokal/ichki serverdan urinib ko'rish
if ($connect->connect_error) {
    $connect = @new mysqli("localhost", $db_user, $db_pass, $db_name);
    if ($connect->connect_error) {
        // Kompyuterdagi lokal baza
        $connect = new mysqli("localhost", "root", "", "cinema");
    }
}

$connect->set_charset("utf8mb4");

?>