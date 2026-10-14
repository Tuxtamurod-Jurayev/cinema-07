<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'sql.php';

// Users step ustunini TEXT qilish
$connect->query("ALTER TABLE users MODIFY COLUMN step TEXT");

// extract_clean_movie_title
function extract_clean_movie_title($caption, $file_name = '') {
    $title = '';
    if (!empty($caption)) {
        $lines = preg_split("/\r\n|\n|\r/", trim($caption));
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            if (preg_match('/^(https?:\/\/|t\.me\/|@)/i', $line)) continue;
            if (preg_match('/(kanal|obuna|admin|reklama|a\'zo|bosing|ulashing|bizning)/ui', $line)) continue;
            if (preg_match('/(hajmi|formati|tili|davomiyligi|sifati|yili|janr|til|davlat)\s*[:\-]/ui', $line)) continue;
            
            $cleaned = preg_replace('/^[\p{P}\p{S}\s]+/u', '', $line);
            $cleaned = preg_replace('/^(kino\s*nomi|film\s*nomi|nomi|film|kino)\s*[\:\-]?\s*/ui', '', $cleaned);
            $cleaned = preg_replace('/^[\p{P}\p{S}\s]+/u', '', $cleaned);
            $cleaned = preg_replace('/[\p{P}\p{S}\s]+$/u', '', $cleaned);
            $cleaned = preg_replace('/\s*(uzbek\s*tilida|o\'zbek\s*tilida|o\'zbekcha\s*tarjima|tarjima\s*kino|full\s*hd|tas-ix\s*skachat|skachat).*$/ui', '', $cleaned);
            $cleaned = trim($cleaned);
            
            if (mb_strlen($cleaned, 'UTF-8') >= 2) {
                $title = $cleaned;
                break;
            }
        }
    }
    return $title;
}

// Barcha "Nomsiz film" larni avtomatik tuzatish
$res = $connect->query("SELECT id, caption, title FROM movie_data WHERE title = 'Nomsiz film'");
$fixed = 0;
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $raw_cap = base64_decode($row['caption']);
        $clean = extract_clean_movie_title($raw_cap);
        if (!empty($clean)) {
            $safe_clean = $connect->real_escape_string($clean);
            $connect->query("UPDATE movie_data SET title = '$safe_clean', caption = '' WHERE id = '{$row['id']}'");
            echo "Yangilandi ID {$row['id']}: $clean<br>";
            $fixed++;
        }
    }
}
echo "Jami tuzatilgan filmlar soni: $fixed<br>";

echo "<h2>Bazadagi filmlar hozirgi holati:</h2><pre>";
$res2 = $connect->query("SELECT id, kino_ids, title, caption FROM movie_data ORDER BY id DESC");
while ($r = $res2->fetch_assoc()) {
    print_r($r);
}
echo "</pre>";
