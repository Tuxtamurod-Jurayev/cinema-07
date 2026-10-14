<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'sql.php';

echo "<h2>1. Kinolar (Oxirgi 5 ta):</h2><pre>";
$res = $connect->query("SELECT id, title, kino_ids, caption, downloads FROM movie_data ORDER BY id DESC LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query error: " . $connect->error;
}
echo "</pre>";

echo "<h2>2. Admin user (365446274) holati:</h2><pre>";
$res = $connect->query("SELECT * FROM users WHERE user_id = '365446274'");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
echo "</pre>";
