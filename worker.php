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
    return curl_exec($ch_bot);
}

echo "Telegram Webhook tekshirilmoqda...\n";
tg_request('deleteWebhook', ['drop_pending_updates' => false]);
echo "✅ Tezkor Polling boshlandi! Xabarlar bir zumda qayta ishlanadi...\n";

$offset = 0;

while (true) {
    $updates = tg_request('getUpdates', [
        'offset' => $offset,
        'limit' => 20,
        'timeout' => 5
    ]);

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
