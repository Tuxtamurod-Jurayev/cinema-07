<?php
// worker.php - Yuqori tezlikdagi Telegram Polling Runner
set_time_limit(0);

const API_KEY = "8708095966:AAFkZ8CoN_17QCHmqukGoSkJLnXGGNRMGLw";

function tg_request($method, $data = []) {
    static $ch = null;
    if ($ch === null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TCP_NODELAY, 1);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . API_KEY . "/" . $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    return json_decode($res);
}

function send_to_bot($json_payload) {
    static $ch_bot = null;
    static $server_failed = false;

    // 1. Agar web server ishlayotgan bo'lsa, curl orqali jo'natish
    if (!$server_failed) {
        if ($ch_bot === null) {
            $ch_bot = curl_init('http://127.0.0.1:8000/bot.php');
            curl_setopt($ch_bot, CURLOPT_POST, true);
            curl_setopt($ch_bot, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Connection: keep-alive']);
            curl_setopt($ch_bot, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_bot, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch_bot, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch_bot, CURLOPT_TCP_NODELAY, 1);
        }
        curl_setopt($ch_bot, CURLOPT_POSTFIELDS, $json_payload);
        $res = curl_exec($ch_bot);
        $err = curl_errno($ch_bot);
        
        if ($err === 0 && $res !== false) {
            return $res;
        }
        
        // Agar web server topilmasa (CURLE_COULDNT_CONNECT = 7)
        if ($err === 7) {
            $server_failed = true;
            echo "[" . date('H:i:s') . "] ℹ️ 127.0.0.1:8000 web server topilmadi. To'g'ridan-to'g'ri CLI rejimiga o'tildi.\n";
        }
    }

    // 2. Web server bo'lmasa, to'g'ridan-to'g'ri bot.php ga uzatish (CLI orqali)
    $descriptors = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"]  // stderr
    ];
    $cmd = 'php ' . escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . 'bot.php');
    $process = proc_open($cmd, $descriptors, $pipes, __DIR__);
    if (is_resource($process)) {
        fwrite($pipes[0], $json_payload);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($process);
        if (!empty($stderr)) {
            echo "[" . date('H:i:s') . "] ⚠️ bot.php xabari: " . trim($stderr) . "\n";
        }
        return $stdout;
    }
    return false;
}

echo "Telegram Webhook tekshirilmoqda...\n";
$del_hook = tg_request('deleteWebhook', ['drop_pending_updates' => false]);
if ($del_hook && isset($del_hook->ok) && $del_hook->ok) {
    echo "✅ Telegram Webhook olib tashlandi (Polling faollashdi).\n";
}

echo "✅ Tezkor Polling boshlandi! Xabarlar bir zumda qayta ishlanadi...\n";

$offset = 0;

while (true) {
    $updates = tg_request('getUpdates', [
        'offset' => $offset,
        'limit' => 20,
        'timeout' => 5
    ]);

    if ($updates && isset($updates->ok) && !$updates->ok) {
        echo "[" . date('H:i:s') . "] ❌ Telegram API xatosi: {$updates->description}\n";
        sleep(2);
        continue;
    }

    if (!empty($updates->result)) {
        foreach ($updates->result as $up) {
            $offset = $up->update_id + 1;
            
            // Xabarni bot.php ga darhol jo'natish
            send_to_bot(json_encode($up));
            
            $sender = $up->message->from->first_name ?? ($up->callback_query->from->first_name ?? 'User');
            $txt = $up->message->text ?? ($up->callback_query->data ?? '');
            echo "[" . date('H:i:s') . "] ⚡️ {$sender}: '{$txt}' -> bot.php (bajarildi)\n";
        }
        // Yangi xabarlar bo'lsa kutmasdan darhol davom etadi
        continue;
    }
    
    usleep(50000); // 0.05 soniya
}

