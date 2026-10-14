<?php
// Xatoliklar fatal exception bo'lib to'xtatib qo'ymasligi uchun
mysqli_report(MYSQLI_REPORT_OFF);

$connect = null;

// 1. Hosting MySQL (ISPmanager) - asosiy server
try {
    $conn = @new mysqli("localhost", "6acf820b59c8d_cinema", "KinoBot2026Pass!", "6acf820b59c8d_cinema");
    if (!$conn->connect_error) {
        $connect = $conn;
    }
} catch (Throwable $e) {
    $connect = null;
}

// 2. Hosting MySQL 127.0.0.1
if (!$connect) {
    try {
        $conn = @new mysqli("127.0.0.1", "6acf820b59c8d_cinema", "KinoBot2026Pass!", "6acf820b59c8d_cinema");
        if (!$conn->connect_error) {
            $connect = $conn;
        }
    } catch (Throwable $e) {
        $connect = null;
    }
}

// 3. Lokal kompyuterdagi MySQL (XAMPP / MariaDB)
try {
    if (!$connect) {
        $conn = @new mysqli("127.0.0.1", "root", "", "cinema");
        if (!$conn->connect_error) {
            $connect = $conn;
        }
    }
} catch (Throwable $e) {
    $connect = null;
}

// 2. Agar 127.0.0.1 ulanmasa, 'localhost' orqali urinib ko'rish
if (!$connect) {
    try {
        $conn = @new mysqli("localhost", "root", "", "cinema");
        if (!$conn->connect_error) {
            $connect = $conn;
        }
    } catch (Throwable $e) {
        $connect = null;
    }
}

// 3. Agar lokal baza bo'lmasa, Alwaysdata serveriga ulanish
if (!$connect) {
    $db_host = "mysql-zevs97.alwaysdata.net";
    $db_user = "zevs97";
    $db_pass = "19971125mm!!";
    $db_name = "zevs97_cinema";

    try {
        $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        if (!$conn->connect_error) {
            $connect = $conn;
        }
    } catch (Throwable $e) {
        $connect = null;
    }
}

// 4. Oxirgi variant: local 'zevs97'
if (!$connect) {
    try {
        $conn = @new mysqli("127.0.0.1", "zevs97", "19971125mm!!", "zevs97_cinema");
        if (!$conn->connect_error) {
            $connect = $conn;
        }
    } catch (Throwable $e) {
        $connect = null;
    }
}

if ($connect && !$connect->connect_error) {
    $connect->set_charset("utf8mb4");
} else {
    error_log("MySQL Ulanish xatosi: Ma'lumotlar bazasiga ulanib bo'lmadi!");
}
?>