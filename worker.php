<?php
// worker.php - Telegram Polling Runner

const API_KEY = "8708095966:AAFkZ8CoN_17QCHmqukGoSkJLnXGGNRMGLw";

function tg_request($method, $data = []) {
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res);
}

echo "Telegram Webhook o'chirilmoqda...\n";
$del = tg_request('deleteWebhook', ['drop_pending_updates' => false]);
if ($del && $del->ok) {
    echo "Webhook muvaffaqiyatli tozalandi!\n";
}

$offset = 0;
echo "Bot ishga tushdi va xabarlarni qabul qilmoqda (Polling faol)...\n";

while (true) {
    $updates = tg_request('getUpdates', [
        'offset' => $offset,
        'limit' => 10,
        'timeout' => 5
    ]);

    if (!empty($updates->result)) {
        foreach ($updates->result as $up) {
            $offset = $up->update_id + 1;
            
            $ch = curl_init('http://127.0.0.1:8000/bot.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($up));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $sender = $up->message->from->first_name ?? ($up->callback_query->from->first_name ?? 'User');
            $text = $up->message->text ?? ($up->callback_query->data ?? '');
            echo "[" . date('H:i:s') . "] Xabar keldi: {$sender} - '{$text}' -> bot.php ga yuborildi.\n";
        }
    }
    usleep(250000); // 0.25 soniya tanaffus
}
