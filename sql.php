<?php

$connect = new mysqli("localhost", "root", "", "cinema");
if ($connect->connect_error) {
    // Agar root/cinema ulanmasa, default kinobot ga urinib ko'rish
    $connect = new mysqli("localhost", "kinobot", "admin1997 ", "admin1997");
}
$connect->set_charset("utf8mb4");

?>