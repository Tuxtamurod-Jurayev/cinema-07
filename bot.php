<?php
ob_start();
date_default_timezone_set('Asia/Tashkent');

// ==========================================
// ⚙️ ASOSIY SOZLAMALAR
// ==========================================
const API_KEY = "8708095966:AAFkZ8CoN_17QCHmqukGoSkJLnXGGNRMGLw";
$admin_id = "365446274";
$admin_phone = "998930406364";
$owners = [$admin_id];
$bot = "kinolar_07_bot";

require_once 'sql.php';

// ==========================================
// ⚡️ TEZLASHTIRILGAN TELEGRAM BOT FUNKSIYASI
// ==========================================
function bot($method, $datas = []) {
    static $ch = null;
    if ($ch === null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TCP_NODELAY, 1);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . API_KEY . "/" . $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    return json_decode($res);
}

function sms($chat_id, $text, $keyboard = null) {
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML',
    ];
    if ($keyboard !== null) {
        $params['reply_markup'] = $keyboard;
    }
    return bot('sendMessage', $params);
}

function edit($chat_id, $message_id, $text, $keyboard = null) {
    $params = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'HTML',
    ];
    if ($keyboard !== null) {
        $params['reply_markup'] = $keyboard;
    }
    return bot('editMessageText', $params);
}

function del($chat_id, $message_id) {
    return bot('deleteMessage', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
    ]);
}

function accl($callback_query_id, $text, $alert = false) {
    return bot('answerCallbackQuery', [
        'callback_query_id' => $callback_query_id,
        'text' => $text,
        'show_alert' => $alert,
    ]);
}

function step($user_id, $val) {
    global $connect;
    $val = $connect->real_escape_string($val);
    $connect->query("UPDATE users SET step = '$val' WHERE user_id = '$user_id'");
}

function admin($user_id) {
    global $connect, $admin_id;
    if ($user_id == $admin_id) {
        return true;
    }
    $res = $connect->query("SELECT id FROM admins WHERE user_id = '$user_id'");
    return ($res && $res->num_rows > 0);
}

function joinchat($user_id) {
    global $connect;
    if (admin($user_id)) {
        return true;
    }
    $result = $connect->query("SELECT * FROM kanallar");
    if (!$result || $result->num_rows == 0) {
        return true;
    }

    $no_subs = 0;
    $buttons = [];
    while ($row = $result->fetch_assoc()) {
        $type = $row['type'];
        $link = $row['link'];
        $channelID = $row['channelID'];
        $title = !empty($row['title']) ? $row['title'] : "Kanal";

        if ($type == "lock" || $type == "request") {
            if ($type == "request") {
                $check = $connect->query("SELECT user_id FROM requests WHERE id = '$user_id' AND chat_id = '$channelID'");
                if ($check && $check->num_rows > 0) {
                    $buttons[] = ['text' => "✅ $title", 'url' => $link];
                } else {
                    $buttons[] = ['text' => "❌ $title", 'url' => $link];
                    $no_subs++;
                }
            } elseif ($type == "lock") {
                $member = bot('getChatMember', ['chat_id' => $channelID, 'user_id' => $user_id]);
                $status = $member->result->status ?? 'left';
                if ($status == "left" || $status == "kicked") {
                    $buttons[] = ['text' => "❌ $title", 'url' => $link];
                    $no_subs++;
                } else {
                    $buttons[] = ['text' => "✅ $title", 'url' => $link];
                }
            }
        } elseif ($type == "social") {
            $nom = base64_decode($title);
            $buttons[] = ['text' => $nom ?: 'Kanal', 'url' => $link];
        }
    }

    if ($no_subs > 0) {
        $buttons[] = ['text' => "✅ A'zo bo'ldim / Tekshirish", 'callback_data' => "checkSub"];
        $keyboard = json_encode(['inline_keyboard' => array_chunk($buttons, 1)]);
        bot('sendMessage', [
            'chat_id' => $user_id,
            'text' => "<b>❌ Botdan foydalanish uchun quyidagi kanallarga a'zo bo'ling:</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
        exit();
    }
    return true;
}

// ==========================================
// 📥 KELGAN XABARNI QABUL QILISH
// ==========================================
$update = json_decode(file_get_contents('php://input'));
if (!$update) exit();

$message = $update->message ?? null;
$callback = $update->callback_query ?? null;
$chat_join_request = $update->chat_join_request ?? null;
$my_chat_member = $update->my_chat_member ?? null;

// Foydalanuvchi botni bloklaganini tekshirish
if ($my_chat_member) {
    $uid = $my_chat_member->from->id;
    $status = $my_chat_member->new_chat_member->status ?? '';
    if ($status == "kicked") {
        $connect->query("UPDATE users SET holat = '❌' WHERE user_id = '$uid'");
        $date = date('d.m.Y H:i');
        $connect->query("INSERT INTO left_users (user_id, date) VALUES ('$uid', '$date')");
    }
}

// Kanalga qo'shilish so'rovi
if ($chat_join_request) {
    $j_user = $chat_join_request->from->id;
    $j_chat = $chat_join_request->chat->id;
    $connect->query("INSERT INTO requests (id, chat_id) VALUES ('$j_user', '$j_chat')");
}

$cid = $message->chat->id ?? ($callback->message->chat->id ?? null);
$mid = $message->message_id ?? ($callback->message->message_id ?? null);
$text = $message->text ?? '';
$data = $callback->data ?? '';
$qid = $callback->id ?? '';
$first_name = $message->from->first_name ?? ($callback->from->first_name ?? '');

if (!$cid) exit();

// Sozlamalarni olish
$settings = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM settings WHERE id = 1"));
$kanalcha = $settings['movieChannel'] ?? '@kinolar_07_bot';
$holat = $settings['status'] ?? '✅';

// Bot o'chiqligini tekshirish
if ($holat == "❌" && !admin($cid)) {
    if ($text) {
        sms($cid, "⛔️ <b>Bot vaqtinchalik o'chirilgan!</b>\n\n<i>Botda texnik ta'mirlash ishlari olib borilmoqda.</i>");
    } elseif ($data) {
        accl($qid, "⛔️ Bot vaqtinchalik o'chirilgan!", true);
    }
    exit();
}

// Foydalanuvchini bazaga kiritish
if ($message) {
    $user_check = $connect->query("SELECT user_id, step FROM users WHERE user_id = '$cid'");
    if ($user_check && $user_check->num_rows == 0) {
        $registered_date = date("d.m.Y H:i");
        $connect->query("INSERT INTO users (user_id, holat, vaqt, step) VALUES ('$cid', '✅', '$registered_date', 'none')");
        $step = "none";
    } else {
        $row = $user_check->fetch_assoc();
        $step = $row['step'] ?? 'none';
        $connect->query("UPDATE users SET holat = '✅' WHERE user_id = '$cid'");
        $connect->query("DELETE FROM left_users WHERE user_id = '$cid'");
    }
} else {
    $user_check = $connect->query("SELECT step FROM users WHERE user_id = '$cid'");
    $step = ($user_check && $user_check->num_rows > 0) ? $user_check->fetch_assoc()['step'] : 'none';
}

// ==========================================
// 📱 OPTIMALLASHTIRILGAN MENYULAR VA TUGMALAR
// ==========================================

$panel = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "🎬 Kinolar sozlamalari"], ['text' => "📢 Kanallar sozlamalari"]],
        [['text' => "📊 Statistika"], ['text' => "✉️ Xabar yuborish"]],
        [['text' => "👥 Adminlar sozlamalari"], ['text' => "⚙️ Bot holati"]],
        [['text' => "🔙 Asosiy menyu"]]
    ]
]);

$movie_manager = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "➕ Kino yuklash"], ['text' => "🔢 Kod bilan qo'shish"]],
        [['text' => "✏️ Kino tahrirlash"], ['text' => "🗑 Kino o‘chirish"]],
        [['text' => "🗄 Boshqaruv paneli"]]
    ]
]);

$channel_manager = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "➕ Kanal qo'shish"], ['text' => "🗑 Kanal o'chirish"]],
        [['text' => "📢 Asosiy kanal"]],
        [['text' => "🗄 Boshqaruv paneli"]]
    ]
]);

$admin_manager = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "➕ Admin qo'shish"], ['text' => "🗑 Admin o'chirish"]],
        [['text' => "📋 Adminlar ro'yxati"]],
        [['text' => "🗄 Boshqaruv paneli"]]
    ]
]);

$back_admin = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "🗄 Boshqaruv paneli"]]
    ]
]);

// ==========================================
// 🎬 FOYDALANUVCHI BUYRUQLARI
// ==========================================

// /start
if (strpos(strtolower($text), "/start") === 0) {
    joinchat($cid);
    
    // Deep-link: /start 860
    $parts = explode(" ", $text);
    if (isset($parts[1]) && is_numeric($parts[1])) {
        send_movie_by_code($cid, $parts[1]);
        exit();
    }

    sms($cid, "✍🏻 Kino kodini yoki nomini yuboring:");
    step($cid, "none");
    exit();
}

// Obunani qayta tekshirish
if ($data == "checkSub") {
    del($cid, $mid);
    if (joinchat($cid)) {
        sms($cid, "✍🏻 Kino kodini yoki nomini yuboring:");
    }
    exit();
}

// Bosh menyuga qaytish
if ($text == "🔙 Asosiy menyu" || $text == "◀️ Asosiy menyu" || $text == "◀️ Orqaga" || $text == "🔙 Orqaga") {
    sms($cid, "✍🏻 Kino kodini yoki nomini yuboring:");
    step($cid, "none");
    exit();
}

// Top 10 kinolar
if ($text == "/top" || $data == "topRating") {
    if ($data) del($cid, $mid);
    $result = $connect->query("SELECT * FROM movie_data ORDER BY downloads DESC LIMIT 10");
    if ($result && $result->num_rows > 0) {
        $msg = "🔥 <b>Eng ko'p ko'rilgan 10 ta tarjima kino:</b>\n\n";
        $buttons = [];
        $i = 1;
        while ($row = $result->fetch_assoc()) {
            $msg .= "<b>$i.</b> " . htmlspecialchars($row['title']) . " (👁 {$row['downloads']})\n";
            $buttons[] = ['text' => (string)$i, 'callback_data' => "getMovie=" . $row['id']];
            $i++;
        }
        $kb = json_encode(['inline_keyboard' => array_chunk($buttons, 5)]);
        sms($cid, $msg, $kb);
    } else {
        sms($cid, "<b>Hozircha kinolar mavjud emas.</b>");
    }
    exit();
}

// ==========================================
// 🔍 KINO YUBORISH VA QIDIRISH
// ==========================================

// 1. Agar foydalanuvchi kino kodini yuborsa (faqat raqam)
if (is_numeric($text) && ($step == "none" || empty($step))) {
    joinchat($cid);
    send_movie_by_code($cid, $text);
    exit();
}

// Callback orqali kinoni ochish
if (strpos($data, "getMovie=") === 0) {
    $movie_row_id = explode("=", $data)[1];
    del($cid, $mid);
    send_movie_by_id($cid, $movie_row_id);
    exit();
}

if ($data == "close") {
    del($cid, $mid);
    exit();
}

// Kino yuborish funksiyasi (kod orqali)
function send_movie_by_code($chat_id, $code) {
    global $connect, $bot;
    $res = $connect->query("SELECT * FROM movie_data WHERE kino_ids = '$code'");
    if ($res && $res->num_rows > 0) {
        if ($res->num_rows > 1) {
            $msg = "🔍 <b>Kod bo'yicha topilgan filmlar:</b>\n\n";
            $buttons = [];
            $i = 1;
            while ($row = $res->fetch_assoc()) {
                $msg .= "<b>$i.</b> " . htmlspecialchars($row['title']) . "\n";
                $buttons[] = ['text' => (string)$i, 'callback_data' => "getMovie=" . $row['id']];
                $i++;
            }
            $kb = json_encode(['inline_keyboard' => array_chunk($buttons, 5)]);
            sms($chat_id, $msg, $kb);
        } else {
            $row = $res->fetch_assoc();
            send_movie_row($chat_id, $row);
        }
    } else {
        sms($chat_id, "<b>❌ Bunday kodli tarjima kino topilmadi!</b>\n\n<i>Kodni to'g'ri kiritganingizga ishonch hosil qiling.</i>");
    }
}

// Kino yuborish funksiyasi (ID orqali)
function send_movie_by_id($chat_id, $row_id) {
    global $connect;
    $res = $connect->query("SELECT * FROM movie_data WHERE id = '$row_id'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        send_movie_row($chat_id, $row);
    } else {
        sms($chat_id, "<b>❌ Kino topilmadi yoki o'chirilgan!</b>");
    }
}

// Kinoni jo'natishning asosiy qismi
function send_movie_row($chat_id, $row) {
    global $connect, $bot;
    $id = $row['id'];
    $title = htmlspecialchars($row['title']);
    $file_id = $row['movie_id'];
    $kino_code = $row['kino_ids'];
    $downloads = (int)$row['downloads'] + 1;
    
    // Yuklanishlar sonini oshirish
    $connect->query("UPDATE movie_data SET downloads = '$downloads' WHERE id = '$id'");
    
    $caption_text = !empty($row['caption']) ? base64_decode($row['caption']) : "";
    $full_caption = "🎬 <b>{$title}</b>\n";
    if ($caption_text) {
        $full_caption .= "\n" . $caption_text . "\n";
    }
    $full_caption .= "\n<b>🆔 Film kodi:</b> <code>$kino_code</code>";
    $full_caption .= "\n<b>👁 Yuklashlar soni:</b> $downloads";
    $full_caption .= "\n\n<b>🤖 Bizning bot:</b> @$bot";

    bot('sendVideo', [
        'chat_id' => $chat_id,
        'video' => $file_id,
        'caption' => $full_caption,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "♻️ Do'stlarga ulashish", 'url' => "https://t.me/share/url?url=https://t.me/$bot?start=$kino_code"]],
                [['text' => "❌ Yopish", 'callback_data' => "close"]]
            ]
        ])
    ]);
}

// 2. Agar foydalanuvchi matn yozsa (Kino nomi bo'yicha qidirish)
$admin_commands = [
    "/panel", "🗄 Boshqarish", "🗄 Boshqaruv paneli", "🔙 Asosiy menyu", "◀️ Asosiy menyu", "◀️ Orqaga", "🔙 Orqaga", "/start", "/top",
    "🎬 Kinolar sozlamalari", "📺 Kino sozlamalari", "📢 Kanallar sozlamalari", "📊 Statistika", "✉️ Xabar yuborish", "✉ Xabar Yuborish",
    "👥 Adminlar sozlamalari", "👨🏻‍⚖️ Adminlar sozlamalari", "⚙️ Bot holati", "🤖 Bot holati", "➕ Kino yuklash", "📥 Kino yuklash",
    "🔢 Kod bilan qo'shish", "➕ Kino qo'shish (Kod bilan)", "✏️ Kino tahrirlash", "📝 Kino tahrirlash", "🗑 Kino o‘chirish", "🗑️ Kino o‘chirish",
    "➕ Kanal qo'shish", "🗑 Kanal o'chirish", "🗑️ Kanal o'chirish", "📢 Asosiy kanal", "*️⃣ Asosiy kino kanali",
    "➕ Admin qo'shish", "➕ Administrator qo'shish", "🗑 Admin o'chirish", "🗑️ Administrator o‘chirish", "📋 Adminlar ro'yxati", "📋 Administrator ro'yxati"
];

if (!empty($text) && ($step == "none" || empty($step)) && !in_array($text, $admin_commands)) {
    joinchat($cid);
    $search_query = $connect->real_escape_string(trim($text));
    $count_res = $connect->query("SELECT COUNT(*) as total FROM movie_data WHERE title LIKE '%$search_query%'");
    $total = $count_res->fetch_assoc()['total'] ?? 0;

    if ($total == 0) {
        sms($cid, "<b>😔 Hech narsa topilmadi.</b>\n\n<i>Nomni boshqacharoq yozib ko'ring yoki kino kodini yuboring.</i>");
        exit();
    }

    $movies = $connect->query("SELECT * FROM movie_data WHERE title LIKE '%$search_query%' LIMIT 0, 10");
    $msg = "🔍 <b>Qidiruv natijalari (1-" . min($total, 10) . " / $total dan):</b>\n\n";
    $buttons = [];
    $i = 1;
    while ($m = $movies->fetch_assoc()) {
        $msg .= "<b>$i.</b> " . htmlspecialchars($m['title']) . " (Kod: <code>{$m['kino_ids']}</code>)\n";
        $buttons[] = ['text' => (string)$i, 'callback_data' => "getMovie=" . $m['id']];
        $i++;
    }

    $keyboard_rows = array_chunk($buttons, 5);
    $keyboard_rows[] = [
        ['text' => "⬅️", 'callback_data' => "page_{$search_query}_0_back"],
        ['text' => "❌", 'callback_data' => "close"],
        ['text' => "➡️", 'callback_data' => "page_{$search_query}_0_next"]
    ];

    sms($cid, $msg, json_encode(['inline_keyboard' => $keyboard_rows]));
    exit();
}

// Qidiruv sahifalash (Pagination)
if (strpos($data, "page_") === 0) {
    $exp = explode("_", $data);
    $q = $connect->real_escape_string($exp[1]);
    $offset = (int)$exp[2];
    $action = $exp[3];

    $new_offset = ($action == "next") ? $offset + 10 : max(0, $offset - 10);
    $movies = $connect->query("SELECT * FROM movie_data WHERE title LIKE '%$q%' LIMIT $new_offset, 10");

    if ($movies && $movies->num_rows > 0) {
        $count_res = $connect->query("SELECT COUNT(*) as total FROM movie_data WHERE title LIKE '%$q%'");
        $total = $count_res->fetch_assoc()['total'] ?? 0;
        
        $msg = "🔍 <b>Qidiruv natijalari (" . ($new_offset + 1) . "-" . min($new_offset + 10, $total) . " / $total dan):</b>\n\n";
        $buttons = [];
        $i = 1;
        while ($m = $movies->fetch_assoc()) {
            $msg .= "<b>$i.</b> " . htmlspecialchars($m['title']) . " (Kod: <code>{$m['kino_ids']}</code>)\n";
            $buttons[] = ['text' => (string)$i, 'callback_data' => "getMovie=" . $m['id']];
            $i++;
        }
        $keyboard_rows = array_chunk($buttons, 5);
        $keyboard_rows[] = [
            ['text' => "⬅️", 'callback_data' => "page_{$q}_{$new_offset}_back"],
            ['text' => "❌", 'callback_data' => "close"],
            ['text' => "➡️", 'callback_data' => "page_{$q}_{$new_offset}_next"]
        ];
        edit($cid, $mid, $msg, json_encode(['inline_keyboard' => $keyboard_rows]));
    } else {
        accl($qid, "Boshqa natija yo'q!", true);
    }
    exit();
}

// ==========================================
// 🛡 ADMIN PANEL BO'LIMLARI
// ==========================================

if ($text == "/panel" || $text == "🗄 Boshqarish" || $text == "🗄 Boshqaruv paneli") {
    if (admin($cid)) {
        sms($cid, "<b>Admin boshqaruv paneliga xush kelibsiz!</b>", $panel);
        step($cid, "none");
    } else {
        sms($cid, "<b>⚠️ Bu bo'lim faqat administratorlar uchun!</b>");
    }
    exit();
}

// --- 🎬 KINO SOZLAMALARI ---
if (($text == "🎬 Kinolar sozlamalari" || $text == "📺 Kino sozlamalari") && admin($cid)) {
    sms($cid, "<b>🎬 Kinolar boshqaruvi:</b>", $movie_manager);
    step($cid, "none");
    exit();
}

// 1. Kino yuklash (Avtomatik kod beradi)
if (($text == "➕ Kino yuklash" || $text == "📥 Kino yuklash") && admin($cid)) {
    sms($cid, "<b>🍿 Kino nomini kiriting:</b>", $back_admin);
    step($cid, "upload_movie_title");
    exit();
}

if ($step == "upload_movie_title" && admin($cid)) {
    if (!empty($text)) {
        file_put_contents("step/upload_title_{$cid}.txt", $text);
        sms($cid, "<b>✅ Kino nomi qabul qilindi:</b> <i>" . htmlspecialchars($text) . "</i>\n\n<b>Endi kinoni shu yerga video formatida yuboring:</b>", $back_admin);
        step($cid, "upload_movie_video");
    }
    exit();
}

if ($step == "upload_movie_video" && admin($cid)) {
    if (isset($message->video)) {
        $file_id = $message->video->file_id;
        $title_file = "step/upload_title_{$cid}.txt";
        $title = file_exists($title_file) ? file_get_contents($title_file) : "Nomsiz film";
        $title = $connect->real_escape_string(str_replace(["‘","’","´","`"], "'", $title));
        $caption = base64_encode($message->caption ?? '');

        // Avtomatik yangi kod yaratish
        $last_res = $connect->query("SELECT MAX(CAST(kino_ids AS UNSIGNED)) as max_code FROM movie_data");
        $max_code = ($last_res) ? (int)$last_res->fetch_assoc()['max_code'] : 0;
        $new_code = $max_code + 1;

        $connect->query("INSERT INTO movie_data (title, caption, movie_id, downloads, kino_ids, type) VALUES ('$title', '$caption', '$file_id', '0', '$new_code', 'usual')");

        sms($cid, "<b>✅ Kino muvaffaqiyatli saqlandi!</b>\n\n<b>🆔 Film kodi:</b> <code>$new_code</code>\n<b>🎬 Nomi:</b> " . htmlspecialchars($title), $movie_manager);
        
        // Kanalga post tayyorlash
        sms($cid, "<b>Kanalga post yuborishni xohlaysizmi?</b>", json_encode([
            'inline_keyboard' => [
                [['text' => "📢 Kanalga post chiqarish", 'callback_data' => "postChannel=$new_code"]]
            ]
        ]));

        step($cid, "none");
        @unlink($title_file);
    } else {
        sms($cid, "<b>Iltimos, video fayl yuboring!</b>");
    }
    exit();
}

// 2. Kino qo'shish (Maxsus kod bilan)
if (($text == "🔢 Kod bilan qo'shish" || $text == "➕ Kino qo'shish (Kod bilan)") && admin($cid)) {
    sms($cid, "<b>Kino uchun kerakli raqamli kodni kiriting (masalan: 860):</b>", $back_admin);
    step($cid, "custom_movie_code");
    exit();
}

if ($step == "custom_movie_code" && admin($cid)) {
    if (is_numeric($text)) {
        file_put_contents("step/custom_code_{$cid}.txt", $text);
        sms($cid, "<b>✅ Kod: $text</b>\n\nEndi kino nomini kiriting:", $back_admin);
        step($cid, "custom_movie_title");
    } else {
        sms($cid, "<b>Faqat raqamlardan iborat kod kiriting!</b>");
    }
    exit();
}

if ($step == "custom_movie_title" && admin($cid)) {
    if (!empty($text)) {
        file_put_contents("step/custom_title_{$cid}.txt", $text);
        sms($cid, "<b>✅ Nomi qabul qilindi!</b>\n\nEndi kinoni video formatida yuboring:", $back_admin);
        step($cid, "custom_movie_video");
    }
    exit();
}

if ($step == "custom_movie_video" && admin($cid)) {
    if (isset($message->video)) {
        $file_id = $message->video->file_id;
        $code = file_get_contents("step/custom_code_{$cid}.txt");
        $title = file_get_contents("step/custom_title_{$cid}.txt");
        $title = $connect->real_escape_string(str_replace(["‘","’","´","`"], "'", $title));
        $caption = base64_encode($message->caption ?? '');

        $connect->query("INSERT INTO movie_data (title, caption, movie_id, downloads, kino_ids, type) VALUES ('$title', '$caption', '$file_id', '0', '$code', 'usual')");

        sms($cid, "<b>✅ Kino yuklandi!</b>\n\n<b>🆔 Kodi:</b> <code>$code</code>\n<b>🎬 Nomi:</b> " . htmlspecialchars($title), $movie_manager);
        
        step($cid, "none");
        @unlink("step/custom_code_{$cid}.txt");
        @unlink("step/custom_title_{$cid}.txt");
    } else {
        sms($cid, "<b>Iltimos, video fayl yuboring!</b>");
    }
    exit();
}

// Kanalga post chiqarish
if (strpos($data, "postChannel=") === 0) {
    $code = explode("=", $data)[1];
    del($cid, $mid);
    sms($cid, "<b>Kanalga chiqarmoqchi bo'lgan postingizni (rasm/matn/video) forward qiling yoki shu yerga yozing:</b>", $back_admin);
    step($cid, "forwardToChannel_{$code}");
    exit();
}

if (strpos($step, "forwardToChannel_") === 0 && admin($cid)) {
    $code = explode("_", $step)[1];
    bot('copyMessage', [
        'chat_id' => $kanalcha,
        'from_chat_id' => $cid,
        'message_id' => $mid,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "📥 Kinoni tomosha qilish", 'url' => "https://t.me/$bot?start=$code"]]
            ]
        ])
    ]);
    sms($cid, "<b>✅ Post $kanalcha kanaliga muvaffaqiyatli yuborildi!</b>", $movie_manager);
    step($cid, "none");
    exit();
}

// 3. Kino o'chirish
if (($text == "🗑 Kino o‘chirish" || $text == "🗑️ Kino o‘chirish") && admin($cid)) {
    sms($cid, "<b>O'chirmoqchi bo'lgan kino kodini kiriting:</b>", $back_admin);
    step($cid, "delete_movie");
    exit();
}

if ($step == "delete_movie" && admin($cid)) {
    $code = $connect->real_escape_string($text);
    $check = $connect->query("SELECT title FROM movie_data WHERE kino_ids = '$code'");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $connect->query("DELETE FROM movie_data WHERE kino_ids = '$code'");
        sms($cid, "<b>✅ '{$row['title']}' kinosi o'chirildi!</b>", $movie_manager);
    } else {
        sms($cid, "<b>Bunday kodli kino topilmadi.</b>", $movie_manager);
    }
    step($cid, "none");
    exit();
}

// 4. Kino tahrirlash
if (($text == "✏️ Kino tahrirlash" || $text == "📝 Kino tahrirlash") && admin($cid)) {
    sms($cid, "<b>Tahrirlamoqchi bo'lgan kino kodini kiriting:</b>", $back_admin);
    step($cid, "edit_movie_search");
    exit();
}

if ($step == "edit_movie_search" && admin($cid)) {
    $code = $connect->real_escape_string($text);
    $res = $connect->query("SELECT * FROM movie_data WHERE kino_ids = '$code'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        file_put_contents("step/edit_movie_{$cid}.txt", $row['id']);
        sms($cid, "<b>🎬 Hozirgi nomi:</b> " . htmlspecialchars($row['title']) . "\n\n<b>Yangi nomini kiriting:</b>", $back_admin);
        step($cid, "edit_movie_title_save");
    } else {
        sms($cid, "<b>Bunday kodli kino topilmadi.</b>", $movie_manager);
        step($cid, "none");
    }
    exit();
}

if ($step == "edit_movie_title_save" && admin($cid)) {
    $movie_id = file_get_contents("step/edit_movie_{$cid}.txt");
    $new_title = $connect->real_escape_string(str_replace(["‘","’","´","`"], "'", $text));
    $connect->query("UPDATE movie_data SET title = '$new_title' WHERE id = '$movie_id'");
    sms($cid, "<b>✅ Kino nomi yangilandi!</b>", $movie_manager);
    @unlink("step/edit_movie_{$cid}.txt");
    step($cid, "none");
    exit();
}

// --- 📢 KANALLAR SOZLAMALARI ---
if ($text == "📢 Kanallar sozlamalari" && admin($cid)) {
    sms($cid, "<b>📢 Majburiy a'zolik kanallari sozlamasi:</b>", $channel_manager);
    step($cid, "none");
    exit();
}

if (($text == "📢 Asosiy kanal" || $text == "*️⃣ Asosiy kino kanali") && admin($cid)) {
    sms($cid, "<b>Hozirgi kanal:</b> $kanalcha\n\n<i>Yangi kanal username'ini kiriting (masalan: @kinolar_07):</i>", $back_admin);
    step($cid, "set_main_channel");
    exit();
}

if ($step == "set_main_channel" && admin($cid)) {
    $chan = trim($text);
    $connect->query("UPDATE settings SET movieChannel = '$chan' WHERE id = 1");
    sms($cid, "<b>✅ Asosiy kanal $chan ga o'zgartirildi!</b>", $channel_manager);
    step($cid, "none");
    exit();
}

if ($text == "➕ Kanal qo'shish" && admin($cid)) {
    sms($cid, "<b>Qo'shiladigan kanal turini tanlang:</b>", json_encode([
        'inline_keyboard' => [
            [['text' => "Oddiy ommaviy kanal", 'callback_data' => "addchan_lock"]],
            [['text' => "So'rov qabul qiluvchi kanal", 'callback_data' => "addchan_request"]],
            [['text' => "Ixtiyoriy link", 'callback_data' => "addchan_social"]]
        ]
    ]));
    exit();
}

if (strpos($data, "addchan_") === 0 && admin($cid)) {
    $type = explode("_", $data)[1];
    file_put_contents("step/chan_type_{$cid}.txt", $type);
    del($cid, $mid);

    if ($type == "social") {
        sms($cid, "<b>Tugma uchun nom kiriting (masalan: Instagram):</b>", $back_admin);
        step($cid, "addchan_social_title");
    } else {
        sms($cid, "<b>Kanal ID raqamini kiriting (masalan: -100123456789):</b>\n\n<i>Bot kanalga administrator qilingan bo'lishi kerak!</i>", $back_admin);
        step($cid, "addchan_id");
    }
    exit();
}

if ($step == "addchan_social_title" && admin($cid)) {
    file_put_contents("step/chan_title_{$cid}.txt", $text);
    sms($cid, "<b>Endi havolani (linkni) kiriting (masalan: https://t.me/...):</b>", $back_admin);
    step($cid, "addchan_social_link");
    exit();
}

if ($step == "addchan_social_link" && admin($cid)) {
    $nom = base64_encode(file_get_contents("step/chan_title_{$cid}.txt"));
    $connect->query("INSERT INTO kanallar (type, link, title, channelID) VALUES ('social', '$text', '$nom', '')");
    sms($cid, "<b>✅ Kanal havolasi qo'shildi!</b>", $channel_manager);
    @unlink("step/chan_title_{$cid}.txt");
    @unlink("step/chan_type_{$cid}.txt");
    step($cid, "none");
    exit();
}

if ($step == "addchan_id" && admin($cid)) {
    $chan_id = trim($text);
    $type = file_get_contents("step/chan_type_{$cid}.txt");
    
    // Link va nomini olish
    $chat_info = bot('getChat', ['chat_id' => $chan_id]);
    $title_val = $chat_info->result->title ?? "Kanal";

    if ($type == "request") {
        $link_res = bot('createChatInviteLink', [
            'chat_id' => $chan_id,
            'creates_join_request' => true
        ]);
        $link = $link_res->result->invite_link ?? '';
    } else {
        $uname = $chat_info->result->username ?? '';
        $link = $uname ? "https://t.me/$uname" : '';
    }

    if ($link) {
        $title_val = $connect->real_escape_string($title_val);
        $connect->query("INSERT INTO kanallar (channelID, link, type, title) VALUES ('$chan_id', '$link', '$type', '$title_val')");
        sms($cid, "<b>✅ Kanal muvaffaqiyatli qo'shildi!</b>", $channel_manager);
    } else {
        sms($cid, "<b>⚠️ Kanal linkini olib bo'lmadi. Botni kanalga admin qilganingizni tekshiring.</b>", $channel_manager);
    }
    @unlink("step/chan_type_{$cid}.txt");
    step($cid, "none");
    exit();
}

if (($text == "🗑 Kanal o'chirish" || $text == "🗑️ Kanal o'chirish") && admin($cid)) {
    $result = $connect->query("SELECT * FROM kanallar");
    if ($result && $result->num_rows > 0) {
        $buttons = [];
        while ($row = $result->fetch_assoc()) {
            $nom = ($row['type'] == "social") ? base64_decode($row['title']) : ($row['title'] ?: $row['channelID']);
            $buttons[] = [['text' => "🗑️ $nom", 'callback_data' => "delChan=" . $row['id']]];
        }
        sms($cid, "<b>O'chirmoqchi bo'lgan kanalni tanlang:</b>", json_encode(['inline_keyboard' => $buttons]));
    } else {
        sms($cid, "<b>Ulangan kanallar yo'q.</b>", $channel_manager);
    }
    exit();
}

if (strpos($data, "delChan=") === 0 && admin($cid)) {
    $id = explode("=", $data)[1];
    $connect->query("DELETE FROM kanallar WHERE id = '$id'");
    del($cid, $mid);
    sms($cid, "<b>✅ Kanal o'chirildi!</b>", $channel_manager);
    exit();
}

// --- 📊 STATISTIKA ---
if ($text == "📊 Statistika" && admin($cid)) {
    $total_users = $connect->query("SELECT users_id FROM users")->num_rows;
    $passive_users = $connect->query("SELECT users_id FROM users WHERE holat = '❌'")->num_rows;
    $total_movies = $connect->query("SELECT id FROM movie_data")->num_rows;
    $today = date('d.m.Y');
    $joined_today = $connect->query("SELECT users_id FROM users WHERE vaqt LIKE '%$today%'")->num_rows;
    $total_downloads = $connect->query("SELECT SUM(CAST(downloads AS UNSIGNED)) as total_dl FROM movie_data")->fetch_assoc()['total_dl'] ?? 0;

    sms($cid, "📊 <b>Tarjima Kinolar Boti Statistikasi:</b>\n
👥 <b>Jami foydalanuvchilar:</b> $total_users ta
🟢 <b>Faol foydalanuvchilar:</b> " . ($total_users - $passive_users) . " ta
🔴 <b>Bloklaganlar:</b> $passive_users ta
📅 <b>Bugun qo'shilganlar:</b> $joined_today ta

🎬 <b>Jami kinolar soni:</b> $total_movies ta
👁 <b>Jami yuklab olishlar:</b> $total_downloads marta

⏰ <b>Vaqt:</b> " . date('H:i:s') . " | <b>Sana:</b> " . date('d.m.Y'));
    exit();
}

// --- ⚙️ BOT HOLATI ---
if (($text == "⚙️ Bot holati" || $text == "🤖 Bot holati") && admin($cid)) {
    $btn = ($holat == "✅") ? "🔴 O'chirish" : "🟢 Yoqish";
    sms($cid, "<b>Hozirgi bot holati:</b> $holat", json_encode([
        'inline_keyboard' => [
            [['text' => $btn, 'callback_data' => "toggle_bot"]]
        ]
    ]));
    exit();
}

if ($data == "toggle_bot" && admin($cid)) {
    $new_status = ($holat == "✅") ? "❌" : "✅";
    $connect->query("UPDATE settings SET status = '$new_status' WHERE id = 1");
    del($cid, $mid);
    sms($cid, "<b>Bot holati o'zgartirildi:</b> $new_status", $panel);
    exit();
}

// --- 👥 ADMINLAR SOZLAMALARI ---
if (($text == "👥 Adminlar sozlamalari" || $text == "👨🏻‍⚖️ Adminlar sozlamalari") && admin($cid)) {
    sms($cid, "<b>Adminlar boshqaruvi:</b>", $admin_manager);
    step($cid, "none");
    exit();
}

if (($text == "📋 Adminlar ro'yxati" || $text == "📋 Administrator ro'yxati") && admin($cid)) {
    $res = $connect->query("SELECT * FROM admins");
    $msg = "<b>Barcha administratorlar:</b>\n\n1. Asosiy admin: <code>$admin_id</code>\n";
    $i = 2;
    if ($res) {
        while ($a = $res->fetch_assoc()) {
            if ($a['user_id'] != $admin_id) {
                $msg .= "$i. ID: <code>{$a['user_id']}</code>\n";
                $i++;
            }
        }
    }
    sms($cid, $msg, $admin_manager);
    exit();
}

if (($text == "➕ Admin qo'shish" || $text == "➕ Administrator qo'shish") && ($cid == $admin_id)) {
    sms($cid, "<b>Yangi adminning Telegram ID raqamini kiriting:</b>", $back_admin);
    step($cid, "add_admin_id");
    exit();
}

if ($step == "add_admin_id" && ($cid == $admin_id)) {
    if (is_numeric($text)) {
        $check = $connect->query("SELECT id FROM admins WHERE user_id = '$text'");
        if ($check && $check->num_rows == 0) {
            $connect->query("INSERT INTO admins (user_id) VALUES ('$text')");
            sms($cid, "<b>✅ <code>$text</code> adminlar ro'yxatiga qo'shildi!</b>", $admin_manager);
        } else {
            sms($cid, "<b>Bu foydalanuvchi allaqachon admin!</b>", $admin_manager);
        }
    } else {
        sms($cid, "<b>Faqat raqamli ID kiriting!</b>", $admin_manager);
    }
    step($cid, "none");
    exit();
}

if (($text == "🗑 Admin o'chirish" || $text == "🗑️ Administrator o‘chirish") && ($cid == $admin_id)) {
    $res = $connect->query("SELECT * FROM admins WHERE user_id != '$admin_id'");
    if ($res && $res->num_rows > 0) {
        $buttons = [];
        while ($r = $res->fetch_assoc()) {
            $buttons[] = [['text' => "🗑️ " . $r['user_id'], 'callback_data' => "delAdmin=" . $r['id']]];
        }
        sms($cid, "<b>O'chirmoqchi bo'lgan adminni tanlang:</b>", json_encode(['inline_keyboard' => $buttons]));
    } else {
        sms($cid, "<b>Qo'shimcha adminlar mavjud emas.</b>", $admin_manager);
    }
    exit();
}

if (strpos($data, "delAdmin=") === 0 && ($cid == $admin_id)) {
    $aid = explode("=", $data)[1];
    $connect->query("DELETE FROM admins WHERE id = '$aid'");
    del($cid, $mid);
    sms($cid, "<b>✅ Administrator o'chirildi!</b>", $admin_manager);
    exit();
}

// --- ✉️ XABAR YUBORISH ---
if (($text == "✉️ Xabar yuborish" || $text == "✉ Xabar Yuborish") && admin($cid)) {
    sms($cid, "<b>Barcha foydalanuvchilarga yuboriladigan xabarni (matn/rasm/video) shu yerga yuboring:</b>", $back_admin);
    step($cid, "broadcast_message");
    exit();
}

if ($step == "broadcast_message" && admin($cid)) {
    sms($cid, "<b>⏳ Xabar yuborilmoqda, kuting...</b>");
    $users = $connect->query("SELECT user_id FROM users");
    $sent = 0;
    $failed = 0;

    if ($users) {
        while ($u = $users->fetch_assoc()) {
            $uid = $u['user_id'];
            $res = bot('copyMessage', [
                'chat_id' => $uid,
                'from_chat_id' => $cid,
                'message_id' => $mid
            ]);
            if ($res && $res->ok) {
                $sent++;
            } else {
                $failed++;
            }
            usleep(30000); // 0.03s
        }
    }

    sms($cid, "<b>Xabar yuborish yakunlandi!</b>\n\n✅ <b>Yetkazildi:</b> $sent ta\n❌ <b>Yetib bormadi:</b> $failed ta", $panel);
    step($cid, "none");
    exit();
}