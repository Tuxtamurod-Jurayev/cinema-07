<?php
ob_start();
date_Default_timezone_set('Asia/Tashkent');

 

const API_KEY = "8708095966:AAFkZ8CoN_17QCHmqukGoSkJLnXGGNRMGLw";
$TokhtasinovUz = "365446274";
$admin_phone = "998930406364";
$owners = [$TokhtasinovUz];
$bot = "kinolar_07_bot";

function bot($method,$datas=[]){
$url = "https://api.telegram.org/bot".API_KEY."/".$method;
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
return json_decode(curl_exec($ch));
}

 

require_once 'sql.php';

function accl($qid,$text,$j=false){
return bot('answerCallbackQuery',[
'callback_query_id'=>$qid,
'text'=>$text,
'show_alert'=>$j,
]);
}

function del(){
global $cid,$mid,$cid2,$mid2;
return bot('deleteMessage',[
'chat_id'=>"$cid2$cid",
'message_id'=>"$mid2$mid",
]);
}


function edit($id,$mid,$tx,$m){
return bot('editMessageText',[
'chat_id'=>$id,
'message_id'=>$mid,
'text'=>$tx,
'parse_mode'=>"HTML",
'reply_markup'=>$m,
]);
}



function sms($id,$tx,$m){
return bot('sendMessage',[
'chat_id'=>$id,
'text'=>$tx,
'parse_mode'=>"HTML",
'reply_markup'=>$m,
]);
}


 

function step($id,$val){
    global $connect;
    mysqli_query($connect,"UPDATE users SET step = '$val' WHERE user_id=$id");
}

function admin($id){
    global $connect, $TokhtasinovUz;
    if($id == $TokhtasinovUz){
        return true;
    }
    $result = mysqli_query($connect,"SELECT * FROM admins WHERE user_id = '$id'");
    if($result && mysqli_num_rows($result) > 0){
        return true;
    }
    return false;
}

function joinchat($id){
global $connect;
$result = $connect->query("SELECT * FROM `kanallar`");
if($result->num_rows > 0 and admin($id)==false) {
$no_subs = 0;
$button = [];
while($row = $result->fetch_assoc()){
    $type = $row['type'];
    $link = $row['link'];
    $channelID = $row['channelID'];
    $title = $row['title'];
    $gettitle = bot('getchat',['chat_id'=>$channelID])->result->title;
    if($type == "lock" or $type == "request"){
        if($type == "request"){
            $check = $connect->query("SELECT * FROM `requests` WHERE id = '$id' AND chat_id = '$channelID'");
            if($check->num_rows > 0){
                $button[]=['text'=>"✅ $gettitle",'url'=>$link];
            }
            else{
                $button[]=['text'=>"❌ $gettitle",'url'=>$link];
                $no_subs++;
            }
        }
        elseif($type == "lock"){
            $check = bot('getChatMember',['chat_id'=>$channelID,'user_id'=>$id])->result->status;
            if($check == "left"){
                $button[]=['text'=>"❌ $gettitle",'url'=>$link];
                $no_subs++;
            }
            else{
            $button[]=['text'=>"✅ $gettitle",'url'=>$link];
            }
        }
    }
elseif($type == "social"){
    $button[]=['text'=>base64_decode($title),'url'=>$link];
}
}
if($no_subs > 0) {
    $button[]=['text'=>"✅ Tekshirish",'callback_data' => "checkSub"];
    $keyboard2=array_chunk($button,1);
    $keyboard=json_encode([
    'inline_keyboard'=>$keyboard2,
]);
bot('sendMessage',[
'chat_id'=>$id,
'text'=>"<b>❌ Kechirasiz botimizdan foydalanishdan oldin ushbu kanallarga a'zo bo'lishingiz kerak.</b>",
'parse_mode'=>'html',
'reply_markup'=>$keyboard
]);
exit();
}else return true;
}else return true;
}

 

 
 
 
 

 

 

 

 

 

$update = json_decode(file_get_contents('php://input'));
$message = $update->message ?? null;
$cid = $message->chat->id ?? null;
$name = $message->chat->first_name ?? '';
$tx = $message->text ?? '';
$mid = $message->message_id ?? null;
$type = $message->chat->type ?? '';
$text = $message->text ?? '';
$uid= $message->from->id ?? null;
$name = $message->from->first_name ?? '';
$familya = $message->from->last_name ?? '';
$bio = $message->from->about ?? '';
$username = $message->from->username ?? '';
$chat_id = $cid;
$message_id = $mid;
$reply = $message->reply_to_message->text ?? null;
$nameru = "<a href='tg://user?id=$uid'>$name $familya</a>";
$photo = $message->photo ?? null;
$file = !empty($photo) ? $photo[count($photo)-1]->file_id : null;

$botdel = $update->my_chat_member->new_chat_member ?? null; 
$botdelid = $update->my_chat_member->from->id ?? null; 
$userstatus = $botdel->status ?? null; 

$contact = $message->contact ?? null;
$contact_id = $contact->user_id ?? null;
$contact_user = $contact->username ?? null;
$contact_name = $contact->first_name ?? null;
$phone = $contact->phone_number ?? null;

$chat_join_request = $update->chat_join_request ?? null;
$join_chat_id = $chat_join_request->chat->id ?? null;
$join_user_id = $chat_join_request->from->id ?? null;

if(isset($chat_join_request)){
$connect->query("INSERT INTO requests (id, chat_id) VALUES ('$join_user_id', '$join_chat_id')");
}


//inline uchun metodlar
$callback_query = $update->callback_query ?? null;
$inline_query = $update->inline_query ?? null;
$data = $callback_query->data ?? null;
$qid = $callback_query->id ?? null;
$id = $inline_query->id ?? null;
$query = $inline_query->query ?? null;
$query_id = $inline_query->from->id ?? null;
$cid2 = $callback_query->message->chat->id ?? null;
$mid2 = $callback_query->message->message_id ?? null;
$callfrid = $callback_query->from->id ?? null;
$callname = $callback_query->from->first_name ?? '';
$calluser = $callback_query->from->username ?? '';
$surname = $callback_query->from->last_name ?? '';
$about = $callback_query->from->about ?? '';
$nameuz = "<a href='tg://user?id=$callfrid'>$callname $surname</a>";

// <--------- @obito_us --------->

if($botdel){ 
if($userstatus=="kicked"){ 
mysqli_query($connect,"UPDATE users SET holat = '❌' WHERE user_id = $botdelid");
$date = date('d.m.Y H:i');
mysqli_query($connect,"INSERT INTO `left_users` (`user_id`, `date`) VALUES ('$botdelid', '$date')");
}
}

if(isset($message)){
mysqli_query($connect,"UPDATE users SET holat = '✅' WHERE user_id = $cid");
$result = mysqli_query($connect,"SELECT * FROM left_users WHERE user_id = $cid");
$row = mysqli_fetch_assoc($result);
if($row){
mysqli_query($connect,"DELETE FROM left_users WHERE user_id = $cid");
}
}

 

 

 


 

 

 

 

if(isset($message)){
$result = mysqli_query($connect,"SELECT * FROM users WHERE user_id = '$cid'");
$row = mysqli_fetch_assoc($result);
if(!$row){
$registered_date = date("d.m.Y H:i");
mysqli_query($connect,"INSERT INTO users(`user_id`,`holat`,`vaqt`,`step`) VALUES ('$cid','✅','$registered_date','none')");
}
}

if(!is_dir("step")) {
    @mkdir("step", 0777, true);
}
$test1 = file_exists("step/test1.txt") ? file_get_contents("step/test1.txt") : "";
$settings = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM settings WHERE id = 1;"));
$kanalcha = $settings['movieChannel'] ?? '';
$sub_price = $settings['sub_price'] ?? 0;
$holat = $settings['status'] ?? '✅';

$user = mysqli_query($connect,"SELECT * FROM users WHERE user_id = '$cid'");
$step = "none";
if($user && mysqli_num_rows($user) > 0){
    $a = mysqli_fetch_assoc($user);
    $step = $a['step'] ?? 'none';
}

// <--------- @obito_us --------->

$menu = json_encode([
'inline_keyboard'=>[
[['text'=>"🔎 Kinolarni qidirish",'url'=>"https://t.me/".str_ireplace("@",'',$kanalcha)]],
]
]);

$panel = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"📢 Kanallar sozlamalari"]],
[['text'=>"📺 Kino sozlamalari"],['text'=>"👨🏻‍⚖️ Adminlar sozlamalari"]],
[['text'=>"📊 Statistika"],['text'=>"✉ Xabar Yuborish"]],
[['text'=>"🤖 Bot holati"],['text'=>"◀️ Orqaga"]],
]
]);

$channel_manager = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"➕ Kanal qo'shish"],['text'=>"🗑️ Kanal o'chirish"]],
[['text'=>"*️⃣ Qo'shimcha kanallar"]],
[['text'=>"🗄 Boshqarish"]],
]
]);

$movie_manager = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"📥 Kino yuklash"],['text'=>"🗑️ Kino o‘chirish"]],
[['text'=>"➕ Kino qo'shish"],['text'=>"📝 Kino tahrirlash"]],
[['text'=>"🗄 Boshqarish"]],
]
]);

$admin_manager = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"➕ Administrator qo'shish"],['text'=>"🗑️ Administrator o‘chirish"]],
[['text'=>"📋 Administrator ro'yxati"]],
[['text'=>"🗄 Boshqarish"]],
]
]);

$back = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"◀️ Orqaga"]],
]
]);

$aort = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqarish"]],
]
]);

 

 

 

 

 

 

 

 

if($text){
if($holat == "❌" and !admin($cid)==1){
sms($cid,"⛔️ <b>Bot vaqtinchalik o'chirilgan!</b>

<i>Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!</i>",json_encode(['remove_keyboard'=>true]));
exit();
}
}

if($data){
if($holat == "❌" and !admin($cid2)==1){
accl($qid,"⛔️ Bot vaqtinchalik o'chirilgan!

Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!",1);
exit();
}
}

// <--------- @obito_us --------->

if((strtolower($text) == "/start") and joinchat($cid)==1){	
sms($cid,"<b>👋 Assalomu alaykum $name  botimizga xush kelibsiz.</b>

<i>✍🏻 Kino kodini yuboring.</i>",$menu);
@unlink("step/test1.txt");
step($cid,"none");
exit();
}

if($text == "◀️ Orqaga"){
$elid=sms($cid,"<i>⏱ Kuting...</i>",json_encode(['remove_keyboard'=>true]))->result->message_id;
bot('deleteMessage',['chat_id'=>$cid,'message_id'=>$elid]);
sms($cid,"<b>👋 Assalomu alaykum $name  botimizga xush kelibsiz.</b>

<i>✍🏻 Kino kodini yuboring.</i>",$menu);
step($cid,"none");
exit;
}

// <--------- @obito_us --------->

 

 


 

 

 

 

 

 

if($text == "🗄 Boshqarish" or $text=="/panel"){
if(admin($cid)==1){
sms($cid,"<b>Admin paneliga xush kelibsiz!</b>",$panel);
@unlink("step/test1.txt");
step($cid,"none");
exit();
}else{
sms($cid,"<b>⚠️ Kechirasiz, bu bo'limga kirish faqat administratorlar uchun ochiq!</b>",null);
sms($TokhtasinovUz,"$cid kirishga urindi.",null);
}
}

if($data == "boshqarish"){
del();
sms($cid2,"<b>Admin paneliga xush kelibsiz!</b>",$panel);
step($cid2,"none");
exit();
}

if($text == "✉ Xabar Yuborish" and admin($cid)==1){
$result = mysqli_query($connect, "SELECT * FROM `send`");
$row = mysqli_fetch_assoc($result);
$status = $row['status'];
$sends_count = $row['sends_count'];
$statistics = $row['statistics'];
$receive_count = $row['receive_count'];
if(!$row){
sms($cid,"<b>📬 Foydalanuvchilarga yuboriladigan xabarni kiriting:</b>",$aort);
step($cid,"send");
}else{
if($status == "resume"){
$kb = json_encode([
'inline_keyboard'=>[
[['text'=>"To'xtatish ⏸",'callback_data'=>"sendstatus=stopped"]],
[['text'=>"🗑 O'chirish",'callback_data'=>"bekorqilish_send"]]
]]);
}elseif($status == "stopped"){
$kb = json_encode([
'inline_keyboard'=>[
[['text'=>"Davom ettirish ▶️",'callback_data'=>"sendstatus=resume"]],
[['text'=>"🗑 O'chirish",'callback_data'=>"bekorqilish_send"]]
]]);
}
sms($cid,"<b>✅ Yuborildi:</b> <code>$sends_count/$statistics</code>
<b>📥 Qabul qilindi:</b> <code>$receive_count</code>
<b>🔰 Status</b>: <code>$status</code>",$kb);
}
}

if($data=="bekorqilish_send"){
mysqli_query($connect,"DELETE FROM `send`");
del();
accl($qid,"Xabar yuborish bekor qilindi!",1);
sms($cid2,"<b>Admin paneliga xush kelibsiz!</b>",$panel);
step($cid2,"none");
exit();
}

 

 

if($step== "send"){
$res = mysqli_query($connect,"SELECT * FROM `users` ORDER BY `users`.`users_id` DESC LIMIT 1;");
$row = mysqli_fetch_assoc($res);
$user_id = $row['user_id'];
$time1 = date('H:i', strtotime('+1 minutes'));
$time2 = date('H:i', strtotime('+2 minutes'));
$tugma = json_encode($update->message->reply_markup);
$reply_markup = base64_encode($tugma);
$stat = $connect->query("SELECT * FROM users")->num_rows;
$edit_mess_id = sms($cid,"<b>✅ Yuborildi:</b> <code>0/$stat</code>
<b>📥 Qabul qilindi:</b> <code>0</code>
<b>🔰 Status</b>: <code>resume</code>",json_encode([
'inline_keyboard'=>[
[['text'=>"To'xtatish ⏸",'callback_data'=>"sendstatus=stopped"]],
[['text'=>"🗑 O'chirish",'callback_data'=>"bekorqilish_send"]]
]]))->result->message_id;
mysqli_query($connect, "INSERT INTO `send` (`time1`,`time2`,`start_id`,`stop_id`,`admin_id`,`message_id`,`reply_markup`,`step`,`edit_mess_id`,`status`,`statistics`,`sends_count`,`receive_count`) VALUES ('$time1','$time2','0','$user_id','$cid','$mid','$reply_markup','send','$edit_mess_id','resume','$stat',0,0)");
sms($cid,"<b>🔄️ Qabul qilindi, bir necha daqiqadan keyin yuborish boshlanadi!</b>",$panel);
step($cid,"none");
}

if(mb_stripos($data,"sendstatus=")!==false){
$up_stat = explode("=",$data)[1];
$result = mysqli_query($connect, "SELECT * FROM `send`");
$row = mysqli_fetch_assoc($result);
if($row['status'] == $up_stat){
accl($qid,"Xabar yuborish xolati $up_stat ga o'zgartirolmaysiz.",1);
}else{
if($up_stat == "resume"){
$time1 = date("H:i",time()+60);
$time2 = date("H:i",time()+120);
mysqli_query($connect,"UPDATE `send` SET time1 = '$time1', `time2` = '$time2'");
}
if($up_stat == "resume"){
$kb = json_encode([
'inline_keyboard'=>[
[['text'=>"To'xtatish ⏸",'callback_data'=>"sendstatus=stopped"]],
[['text'=>"🗑 O'chirish",'callback_data'=>"bekorqilish_send"]]
]]);
}elseif($up_stat == "stopped"){
$kb = json_encode([
'inline_keyboard'=>[
[['text'=>"Davom ettirish ▶️",'callback_data'=>"sendstatus=resume"]],
[['text'=>"🗑 O'chirish",'callback_data'=>"bekorqilish_send"]]
]]);
}
$edit_mess_id = edit($cid2,$mid2,"<b>✅ Yuborildi:</b> <code>".$row['sends_count']."/".$row['statistics']."</code>
<b>📥 Qabul qilindi:</b> <code>".$row['receive_count']."</code>
<b>🔰 Status</b>: <code>$up_stat</code>",$kb)->result->message_id;
mysqli_query($connect,"UPDATE `send` SET edit_mess_id = '$edit_mess_id', `status` = '$up_stat'");
}
}

if($text == "📢 Kanallar sozlamalari" and admin($cid)==1){
sms($cid,"<b>$text bo'limi!
⤵️ Kerakli menyuni tanlang:</b>",$channel_manager);
step($cid,"none");
}

if($text == "➕ Kanal qo'shish" and admin($cid)==1){
sms($cid,"<b>👉 Qo‘shmoqchi bo‘lgan kanal turini tanlang:</b>",json_encode([
'inline_keyboard'=>[
[['text'=>"Ommaviy",'callback_data'=>"request-false"]],
[['text'=>"So‘rov qabul qiluvchi",'callback_data'=>"request-true"]],
[['text'=>"Ixtiyoriy havola",'callback_data'=>"socialnetwork"]]
]]));
}


 

 

if($data == "socialnetwork"){
del();
sms($cid2,"<b>Havola uchun nom yuboring:</b>",$aort);
step($cid2,"socialnetwork_step1");
}

if($step == "socialnetwork_step1" and admin($cid)==1){
if(isset($text)){
file_put_contents("step/trash_social.txt",$text);
sms($cid,"<b>✅ $text qabul qilindi!</b>

<i>ixtiyorit havolani kiriting:</i>",null);
step($cid,"socialnetwork_step2");
}else{
sms($cid,"Faqat matnlardan foydalaning",null);
}
}

if($step == "socialnetwork_step2" and admin($cid)==1){
if(isset($text)){
$nom = file_get_contents("step/trash_social.txt");
if($nom !== false){
$nom = base64_encode($nom);
$sql = "INSERT INTO `kanallar` (`type`, `link`, `title`, `channelID`) VALUES ('social', '$text', '$nom', '')";
if($connect->query($sql)){
sms($cid,"<b>✅ Kanal muvoffaqiyatli qo‘shildi</b>",$panel);
step($cid,"none");
}else{
sms($cid,"<b>⚠️ Kanal qo‘shishda xatolik!</b>\n\n<code>{$connect->error}</code>",$panel);
step($cid,"none");
}
}else{
sms($cid,"<b>Havola uchun nom yuboring:</b>",$aort);
step($cid,"socialnetwork_step1");
}
}


 

 

  
 

 
}

if(mb_stripos($data,"request-")!==false){
$type = explode("-",$data)[1];
file_put_contents("step/$cid2.type",$type);
sms($cid2,"<b>Endi kanalizdan \"forward\" xabar yuboring:</b>",$aort);
step($cid2,"qosh");
}

if($step == "qosh" and isset($message->forward_origin)){
$kanal_id = $message->forward_origin->chat->id;
$type = file_get_contents("step/$cid.type");
if($type=="true"){
$link = bot('createChatInviteLink',[
'chat_id'=>$kanal_id,
'creates_join_request'=>true
])->result->invite_link;
$sql = "INSERT INTO `kanallar` (`channelID`, `link`, `type`) VALUES ('$kanal_id', '$link', 'request')";
}elseif($type=="false"){
$link = "https://t.me/" . $message->forward_origin->chat->username;
$sql = "INSERT INTO `kanallar` (`channelID`, `link`, `type`) VALUES ('$kanal_id', '$link', 'lock')";
}
if($connect->query($sql)){
sms($cid,"<b>✅ Kanal muvoffaqiyatli qo‘shildi</b>",$panel);
}else{
sms($cid,"<b>⚠️ Kanal qo‘shishda xatolik!</b>\n\n<code>{$connect->error}</code>",$panel);
}
step($cid,"none");  
@unlink("step/$cid.type");
}

 

 

if($text == "🗑️ Kanal o'chirish" and admin($cid)==1){
$result = $connect->query("SELECT * FROM `kanallar`");
if($result->num_rows > 0) {
$button = [];
while($row = $result->fetch_assoc()){
$type = $row['type'];
$channelID = $row['channelID'];
if($type == "lock" or $type == "request"){
$gettitle = bot('getchat',['chat_id'=>$channelID])->result->title;
$button[]=['text'=>"🗑️ ".$gettitle,'callback_data'=>"delchan=".$channelID];
}else{
$gettitle = $row['title'];
$button[]=['text'=>"🗑️ ".$gettitle,'callback_data'=>"delchan=".$channelID];
}
}
$keyboard2=array_chunk($button,1);
$keyboard2[]=[['text'=>"◀️ Orqaga",'callback_data'=>"setchannels"]];
$keyboard=json_encode([
'inline_keyboard'=>$keyboard2,
]);
sms($cid,"<b>Kerakli kanalni tanlang va u o‘chiriladi:</b>",$keyboard);
}else{
sms($cid,"<b>Hech qanday kanal ulanmagan!</b>",null);
}
}

if(stripos($data,"delchan=")!==false){
$ex=explode("=",$data)[1];
$result = $connect->query("SELECT * FROM `kanallar` WHERE channelID = '$ex'");
$row = $result->fetch_assoc();
if($row['requestchannel']=="true"){
$connect->query("DELETE FROM requests WHERE chat_id = '$ex'");
}
$connect->query("DELETE FROM kanallar WHERE channelID = '$ex'");
bot('editMessageText',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
'text'=>"<b>✅ Kanal o'chirildi!</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"kochir"]]
]
])
]);
}

 

 

if($text == "*️⃣ Qo'shimcha kanallar" and admin($cid)==1){
sms($cid,"<b>*️⃣ Qo'shimcha kanallar</b> - kerakli kanalni tanlang:",json_encode([
'inline_keyboard'=>[
[['text'=>"🍿 Kino yuklanadigan kanal",'callback_data'=>"movieChannel"]]
]]));
}

if($data == "movieChannel"){
del();
sms($cid2,"<b>🔗 Kino yuboriladigan kanal havolasini kiriting:</b>

<i>Na'muna: @she_about_pg</i>",$aort);
step($cid2,"movieChannel");
}

if($step == "movieChannel" and admin($cid)==1){
$check = bot('getChatMember',['chat_id'=>$text,'user_id'=>bot('getMe')->result->id])->result->status;
if($check == "administrator"){
mysqli_query($connect,"UPDATE `settings` SET `movieChannel`='$text'");
sms($cid,"<b>🍿 Kino yuklanadigan kanal $text'ga o'zgartirildi!</b>",$panel);
step($cid,"none");
}else{
sms($cid,"<b>Botni kanalga admin qilib keyin qayta urinib ko'ring.</b>",null);
}
}

 


 


 


 

// <--------- @obito_us --------->

if($text == "📊 Statistika"){
if(admin($cid)==1){
$res = mysqli_query($connect, "SELECT * FROM `users`");
$stat = mysqli_num_rows($res);
$passive = $connect->query("SELECT * FROM users WHERE holat = '❌'")->num_rows;
$movie_count = $connect->query("SELECT * FROM movie_data")->num_rows;
$loadtime = function_exists('sys_getloadavg') ? sys_getloadavg() : [0.05, 0.05, 0.05];
$today_date = date('d.m.Y');
$month_date = date('m.Y');
$joined_today = $connect->query("SELECT * FROM `users` WHERE `vaqt` LIKE '%$today_date%';")->num_rows;
$joinedThisMonth = $connect->query("SELECT * FROM `users` WHERE `vaqt` LIKE '%$month_date%';")->num_rows;
$left_today = $connect->query("SELECT * FROM `left_users` WHERE `date` LIKE '%$today_date%';")->num_rows;
$leftThisMonth = $connect->query("SELECT * FROM `left_users` WHERE `date` LIKE '%$month_date%';")->num_rows;
sms($cid,"💡 <b>O'rtacha yuklanish:</b> <code>$loadtime[0]</code>

• <b>Barcha odamlar:</b> $stat ta 
• <b>Bugun qo'shilganlar:</b> $joined_today ta
• <b>Shu oy qo'shilganlar:</b> $joinedThisMonth ta

• <b>Tark etgan:</b> $passive ta
• <b>Bugun tark etgan:</b> $left_today ta
• <b>Shu oy tark etgan:</b> $leftThisMonth ta

• <b>Yuklangan kinolar:</b> $movie_count ta

<b>⏰ Soat:</b> ".date('H:i:s')." | <b>📆 Sana:</b> ".date('d.m.Y')."",json_encode([
'inline_keyboard'=>[
[['text'=>"🔄 Yangilash",'callback_data'=>"upstat"]]
]]));
exit();
}
}

if($data == "upstat"){
$res = mysqli_query($connect, "SELECT * FROM `users`");
$stat = mysqli_num_rows($res);
$passive = $connect->query("SELECT * FROM users WHERE holat = '❌'")->num_rows;
$movie_count = $connect->query("SELECT * FROM movie_data")->num_rows;
$loadtime = function_exists('sys_getloadavg') ? sys_getloadavg() : [0.05, 0.05, 0.05];
edit($cid2,$mid2,"💡 <b>O'rtacha yuklanish:</b> <code>$loadtime[0]</code>

• <b>Barcha odamlar:</b> $stat ta 
• <b>Tark etgan:</b> $passive ta
• <b>Yuklangan kinolar:</b> $movie_count ta

⏰ Soat: ".date('H:i:s')." | 📆 Sana: ".date('d.m.Y')."",json_encode([
'inline_keyboard'=>[
[['text'=>"🔄 Yangilash",'callback_data'=>"upstat"]]
]]));
}

//<---- @Obito_us ---->//

if($text == "👨🏻‍⚖️ Adminlar sozlamalari"){
if(admin($cid)==1){
sms($cid,"<b>👨🏻‍⚖️ Adminlar sozlamalari bo'limi!
⤵️ Kerakli menyuni tanlang:</b>",$admin_manager);
}
}

if($data == "admins"){
if(admin($cid2)==1){
del();
sms($cid2,"<b>👨🏻‍⚖️ Adminlar sozlamalari bo'limi!
⤵️ Kerakli menyuni tanlang:</b>",$admin_manager);
}
}


 


 


 

if($text == "📋 Administrator ro'yxati" and admin($cid)==1){
$res = mysqli_query($connect,"SELECT * FROM admins");
if($res->num_rows > 0){
while($a = mysqli_fetch_assoc($res)){
$user = $a['user_id'];
$get=bot('getchat',['chat_id'=>$user])->result->first_name;
$name=strip_tags($get);
$key[]=['text'=>"$name",'url'=>"tg://user?id=$user"];
}
$keyboard2=array_chunk($key,1);
$kb=json_encode([
'inline_keyboard'=>$keyboard2,
]);
sms($cid,"<b>👉 Barcha adminlar ro'yxati:</b>",$kb);
}else{
sms($cid,"<b>Administratorlar mavjud emas</b>",null);
}
}

if($text == "➕ Administrator qo'shish"){
if(in_array($cid,$owners)){
sms($cid,"<b>Kerakli foydalanuvchi ID raqamini yuboring:</b>",$aort);
step($cid,"add-admin");
}else{
sms($cid,"<b>Ushbu bo'limdan foydalanish siz uchun taqiqlangan!</b>",null);
}
}
if($step == "add-admin" and in_array($cid,$owners)){
$result = mysqli_query($connect,"SELECT * FROM users WHERE user_id = '$text'");
$row = mysqli_fetch_assoc($result);
if(!$row){
sms($cid,"<b>Ushbu foydalanuvchi botdan foydalanmaydi!</b>

Boshqa ID raqamni kiriting:",null);
}else{
    $check_adm = mysqli_query($connect,"SELECT * FROM admins WHERE user_id = '$text'");
    if(mysqli_num_rows($check_adm) == 0){
        $connect->query("INSERT INTO admins (user_id) VALUES ('$text')");
        sms($cid,"<code>$text</code> <b>adminlar ro'yxatiga qo'shildi!</b>",$admin_manager);
        step($cid,"none");
    }else{
        sms($cid,"<b>Ushbu foydalanuvchi adminlar ro'yxatida mavjud!</b>

Boshqa ID raqamni kiriting:",null);
    }
}
}

 


 

 

if($text == "🗑️ Administrator o‘chirish"){
if(in_array($cid,$owners)){
$result = $connect->query("SELECT * FROM admins");
if($result->num_rows > 0){
$i = 1;
$response = "";
while($row = $result->fetch_assoc()){
$get = bot('getchat',['chat_id'=>$row['user_id']])->result->first_name;
$response .= "<b>$i)</b> <a href='tg://user?id=".$row['user_id']."'>$get</a>\n";
$uz[]=['text'=>$i,'callback_data'=>"remove-admin=".$row['user_id']];
$i++;
}
$keyboard2=array_chunk($uz,3);
$kb=json_encode(['inline_keyboard'=>$keyboard2]);
sms($cid,"<b>👉 O'chirmoqchi bo'lgan administratorni tanlang:</b>\n\n$response",$kb);
}else{
sms($cid,"<b>Administratorlar mavjud emas</b>",null);
}
}else{
sms($cid,"<b>Ushbu bo'limdan foydalanish siz uchun taqiqlangan!</b>",null);
}
}

if(mb_stripos($data,"remove-admin=")!==false and in_array($cid2,$owners)){
$user_od = explode("=",$data)[1];
$result = mysqli_query($connect,"SELECT * FROM admins WHERE user_id = $user_od");
$row = mysqli_fetch_assoc($result);
if($row){
$connect->query("DELETE FROM admins WHERE user_id = $user_od");
del();
sms($cid2,"<code>$user_od</code> <b>adminlar ro'yxatidan olib tashlandi!</b>",$admin_manager);
}else{
accl($qid,"Ushbu foydanuvchi administratorlar ro'yxatida mavjud emas!",1);
}
}

 


 


 

 

 

 

//<---- @Obito_us ---->//

if($text == "🤖 Bot holati" and admin($cid)==1){
if($holat == "✅")$xolat = "O'chirish";
if($holat == "❌")$xolat = "Yoqish";
sms($cid,"<b>*️⃣ Hozirgi holat:</b> $holat",json_encode([
'inline_keyboard'=>[
[['text'=>$xolat,'callback_data'=>"bot"]]
]]));
}

if($data == "xolat"){
if($holat == "✅")$xolat = "O'chirish";
if($holat == "❌")$xolat = "Yoqish";
del();
sms($cid,"<b>️*️⃣ Hozirgi holat:</b> $holat",json_encode([
'inline_keyboard'=>[
[['text'=>$xolat,'callback_data'=>"bot"]]
]]));
}

if($data == "bot"){
if($holat == "✅"){
$connect->query("UPDATE settings SET `status` = '❌'");
$px="❌";
$xolat = "Yoqish";
}else{
$connect->query("UPDATE settings SET `status` = '✅'");
$px="✅";
$xolat = "O'chirish";
}
edit($cid2,$mid2,"<b>️*️⃣ Hozirgi holat:</b> $px",json_encode([
'inline_keyboard'=>[
[['text'=>$xolat,'callback_data'=>"bot"]]
]]));
}

// >>> @obito_us <<<

if($text == "📺 Kino sozlamalari" and admin($cid)==1){
sms($cid,"<b>📺 Kino sozlamalari bo'limi!
⤵️ Kerakli menyuni tanlang:</b>",$movie_manager);
step($cid,"none");
}

if($text == "📥 Kino yuklash" and admin($cid)==1){
sms($cid,"<b>🍿 Kino nomini kiriting:</b>",$aort);
step($cid,"kino_nomi");
}

if($step == "kino_nomi"){
if(isset($text)){
sms($cid,"<b>✅ $text qabul qilindi!</b>

<i>Ushbu kinoni ko'rish uchun obuna talab etilsinmi?</i>",json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"Ha"],['text'=>"Yo'q"]],
[['text'=>"🗄 Boshqarish"]]
]]));
file_put_contents("step/test1.txt",$text);
step($cid,"movie_stepping");
}
}

if($step == "movie_stepping"){
if(isset($text)){
sms($cid,"<b>✅ $text qabul qilindi!</b>

<i>Kinoni shu yerga yuboring</i>",$aort);
file_put_contents("step/test2.txt",$text);
step($cid,"movie");
}
}

if(file_exists('step/counter.txt') == FALSE){
file_put_contents('step/counter.txt', "0");
}

 


 

 

 

if($step=="movie" and isset($message->video)){
$video = $message->video;
$file_id = $message->video->file_id;
$type_movie = file_get_contents("step/test2.txt") == "Ha" ? 'premium' : 'usual';
$title = file_get_contents("step/test1.txt");
$title = str_replace(["‘","’","´","`"],["'","'","'","'"],$title);
$title = $connect->real_escape_string($title);
$caption = base64_encode($message->caption);
$kino_ids = file_get_contents('step/counter.txt') + 1;
file_put_contents('step/counter.txt',$kino_ids);
$sql = "INSERT INTO movie_data (title, caption, movie_id, downloads, kino_ids, `type`) VALUES ('$title', '$caption', '$file_id', '0', '$kino_ids','$type_movie');";
if($connect->query($sql)==true){
$s = $connect->insert_id;
sms($cid,"✅ <b>Kino kanal va botga joylandi!</b>

<b>🆔 Film IDsi:</b> <code>$kino_ids</code>",$movie_manager);
sms($cid,"<b>🔗 Ushbu kinoni kanalga tashlamoqchimisiz?</b>

<i>Agar, «✅ Yuborish» tugmasini bossangiz $kanalcha'ga yuboradi.</i>",json_encode([
'inline_keyboard'=>[
[['text'=>"✅ Yuborish",'callback_data'=>"sms_{$kino_ids}"]]
]]));
}else{
sms($cid,"⚠️ <b>Xatolik!</b>

<code>$connect->error</code>",$aort);
}
step($cid,"none");
@unlink("step/test1.txt");
}

if(mb_stripos($data,"sms_")!==false){
$s = str_ireplace('sms_','',$data);
del();
sms($cid2,"<b>Kanalga yubormoqchi bo'lgan postingizni yuboring</b>",$aort);
step($cid2,"sms_{$s}");
}

if(mb_stripos($step,"sms_")!==false){
$s = str_ireplace('sms_','',$step);
$res = bot('copyMessage',[
'chat_id'=>$kanalcha,
'from_chat_id'=>$cid,
'message_id'=>$message->message_id,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"📥 Yuklab olish",'url'=>"https://t.me/$bot?start=$s"]]
]
])
]);
sms($cid,"<b>✅ $kanalcha kanaliga post yuborildi!</b>",$movie_manager);
// sms(6252419804,json_encode($res,256|128|64),null);
step($cid,"none");
}

if($text == "🗑️ Kino o‘chirish" and admin($cid)==1){
sms($cid,"<b>Kino kodini kiriting:</b>",$aort);
step($cid,"deleteMov");
}
if($step=="deleteMov"){
$sql="DELETE FROM movie_data WHERE kino_ids = '$text'";
if($connect->query($sql)==true){
sms($cid,"<b>Kino o'chirib tashlandi.</b>",$channel_manager);
}else{
sms($cid,"<b>Kino o'chirib tashlashda xatolik!</b>\n\n<code>{$connect->error}</code>",$aort);
}
step($cid,"none");
}

if($text == "➕ Kino qo'shish" and admin($cid)==1){
sms($cid,"<b>➡️ Kino qo'shish uchun kino kodini kiriting:</b>",$aort);
step($cid,'movie_download');
}

if($step == 'movie_download' and admin($cid)==1){
if(is_numeric($text)==1){
file_put_contents("step/movie_id.txt",$text);
sms($cid,"<b>🍿 Kino nomini kiriting:</b>",$aort);
step($cid,"movieDownload_step1");
}else{
sms($cid,"<b>🔕 Faqat raqamlardan foydalish ruxsat etilgan!</b>",null);
}
}

 

 

 

if($step == "movieDownload_step1" and admin($cid)==1){
if(isset($text)){
sms($cid,"<b>✅ $text qabul qilindi!</b>

<i>Kinoni shu yerga yuboring</i>",$aort);
file_put_contents("step/test1.txt",$text);
step($cid,"movieDownload_step2");
}
}

if($step == "movieDownload_step2"){
$video = $message->video;
$file_id = $message->video->file_id;
$title = file_get_contents("step/test1.txt");
$title = str_replace(["‘","’","´","`"],["'","'","'","'"],$title);
$title = $connect->real_escape_string($title);
$kino_ids = file_get_contents("step/movie_id.txt");
$caption = base64_encode($message->caption);
file_put_contents('step/counter.txt',$kino_ids);
$sql = "INSERT INTO movie_data (title, caption, movie_id, downloads, kino_ids) VALUES ('$title', '$caption', '$file_id', '0', '$kino_ids');";
if($connect->query($sql)==true){
$s = $connect->insert_id;
sms($cid,"✅ <b>Kino yuklandi!</b>

<i>Kinoni $kino_ids kodi orqali ko'rishingiz mumkin.</i>",$movie_manager);
}else{
sms($cid,"⚠️ <b>Xatolik!</b>

<code>$connect->error</code>",$aort);
}
@unlink("step/test1.txt");
@unlink("step/movie_id.txt");
step($cid,"none");
exit;
}


       


 
if($text == "📝 Kino tahrirlash" and admin($cid)==1){
sms($cid,"<b>➡️ Tahrirlamoqchi bo'lgan filmingizni kodini kiriting:</b>",$aort);
step($cid,"movieEdit");
}

if(($step == "movieEdit" or mb_stripos($data,"movieEdit=")!==false) and admin("$cid$cid2")==1){
if(isset($data)) $text = explode("=",$data)[1];
if(is_numeric($text)==1){
$result = $connect->query("SELECT * FROM movie_data WHERE kino_ids = '$text'");
if($result->num_rows > 1){
$txts = "";
$i = 1;
while($a = $result->fetch_assoc()){
$txts .= "<b>$i.</b> ".$a['title']."\n";
$uz[] = ['text'=>$i,'callback_data'=>"editMovie=".$a['id']];
$i++;
}
$keyboard2=array_chunk($uz,5);
$kb = json_encode(['inline_keyboard'=>$keyboard2]);
if(isset($data)){
edit($cid2,$mid2,"<b>Kerakli filmni tanlang(x{$result->num_rows}):</b>\n\n$txts",$kb);
}else{
sms($cid,"<b>Kerakli filmni tanlang(x{$result->num_rows}):</b>\n\n$txts",$kb);
}
}else{
$a = $result->fetch_assoc();
if(isset($data)){
edit($cid2,$mid2,"<b>HOZIRGI HOLATI:</b>

<i>1. Nom: ".$a['title']."</i>",json_encode([
'inline_keyboard'=>[
[['text'=>"📝 Nom",'callback_data'=>"editMovieById=".$a['id']."=title"]]
]]));
}else{
sms($cid,"<b>HOZIRGI HOLATI:</b>

<i>1. Nom: ".$a['title']."</i>",json_encode([
'inline_keyboard'=>[
[['text'=>"📝 Nom",'callback_data'=>"editMovieById=".$a['id']."=title"]]
]]));
}
}
}
}

 

 

 

 

if(mb_stripos($data,"editMovie=")!==false){
$text = explode("=",$data)[1];
$result = $connect->query("SELECT * FROM movie_data WHERE id = '$text'");
if($result->num_rows > 0){
$a = $result->fetch_assoc();
edit($cid2,$mid2,"<b>HOZIRGI HOLATI:</b>

<i>1. Nom: ".$a['title']."</i>",json_encode([
'inline_keyboard'=>[
[['text'=>"📝 Nom",'callback_data'=>"editMovieById=".$a['id']."=title"]],
[['text'=>"➡️ Orqaga",'callback_data'=>"movieEdit=".$a['kino_ids']]]
]]));
}else{
accl($qid,"Texnik xatolik!",1);
}
}

if(mb_stripos($data,"editMovieById=")!==false){
step($cid2,$data);
del();
sms($cid2,"<b>Yangi qiymatini kiriting:</b>",$aort);
}

if(mb_stripos($step,"editMovieById=")!==false){
$exp = explode("=",$step);
$kino_id = $exp[1];
$tip = $exp[2];
if($tip == "title"){
$text = str_replace(["‘","’","´","`"],["'","'","'","'"],$text);
$text = $connect->real_escape_string($text);
$connect->query("UPDATE movie_data SET title = '$text' WHERE id = '$kino_id'");
sms($cid,"<b>Qiymat qabul qilindi va saqlandi!</b>",$panel);
step($cid,"none");
}else{
sms($cid,"<b>Texnik xatolik yuz berdi!</b>

Qayta urinib ko'ring",$aort);
step($cid,"none");
}
}


 

 

 

 

 

 

// <--------- @obito_us --------->

if($text == "/premium" or $text == "/vip"){
$result = mysqli_query($connect,"SELECT * FROM obunalar WHERE user_id = '$cid'");
$row = mysqli_fetch_assoc($result);
if($row){
bot('sendPhoto',[
'chat_id'=>$cid,
'photo'=>'https://t.me/mylogschannel/479',
'caption'=>"<b>SIZ PREMIUM OBUNAGA OBUNA BO'LGANSIZ!</b>

<blockquote>⏳ Amal qilish muddati: ".$row['subcribe_enddate']." gacha</blockquote>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"➡️ Obuna uzaytirish",'callback_data'=>"renewal_obuna"]]
]
])
]);
}else{
bot('sendPhoto',[
'chat_id'=>$cid,
'photo'=>'https://t.me/mylogschannel/479',
'caption'=>"<b>❌ Siz PREMIUM obuna sotib olmagansiz!</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🛒 Xarid qilish",'callback_data'=>"subcribe1"]]
]
])
]);
}
}

if($data == "subcribe1"){
$sub_price_3 = $sub_price * 3;
$sub_price_6 = $sub_price * 6;
$sub_price_12 = $sub_price * 12;
bot('editMessageCaption',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
'caption'=>"<b>💎 PREMIUM ta'riflardan birini tanlang:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"1 oylik obuna - $sub_price so‘m",'callback_data'=>"toSubcribe_1"]],
[['text'=>"3 oylik obuna - $sub_price_3 so‘m",'callback_data'=>"toSubcribe_3"]],
[['text'=>"6 oylik obuna - $sub_price_6 so‘m",'callback_data'=>"toSubcribe_6"]],
[['text'=>"1 yillik obuna - $sub_price_12 so‘m",'callback_data'=>"toSubcribe_12"]],
]
])
]);
}

if(mb_stripos($data,"toSubcribe_")!==false){
$obuna = str_ireplace('toSubcribe_','',$data);
$sub_cost = $sub_price * $obuna;
del();
sms($cid2,"<b>Karta raqamlari

Chekda aniq sana va vaqt, summa hamda o'tkazilgan karta raqam bo'lishi kerak!
Ps: bittadan ortiq rasm jo'natishga to'g'ri kelsa, donalab yuboring! Guruhlab yuborsangiz bot javob qaytarmaydi!</b>",json_encode([
'inline_keyboard'=>[
[['text'=>"✅ To'lov qildim",'callback_data'=>"iPaid_$sub_cost"]]
]]));
}


 

 

 

 


if($data == "renewal_obuna"){
$result = mysqli_query($connect,"SELECT * FROM obunalar WHERE user_id = '$cid2'");
$row = mysqli_fetch_assoc($result);
del();
if($row){
$sub_price_3 = $sub_price * 3;
$sub_price_6 = $sub_price * 6;
$sub_price_12 = $sub_price * 12;
sms($cid2,"<b>⏳ PREMIUM obunani qanchaga uzaytirmoqchisiz?</b>",json_encode([
'inline_keyboard'=>[
[['text'=>"1 oylik obuna - $sub_price so‘m",'callback_data'=>"toSubcribe_1"]],
[['text'=>"3 oylik obuna - $sub_price_3 so‘m",'callback_data'=>"toSubcribe_3"]],
[['text'=>"6 oylik obuna - $sub_price_6 so‘m",'callback_data'=>"toSubcribe_6"]],
[['text'=>"1 yillik obuna - $sub_price_12 so‘m",'callback_data'=>"toSubcribe_12"]],
]]));
}else{
sms($cid2,"<b>❌ Siz PREMIUM obuna sotib olmagansiz!</b>",json_encode([
'inline_keyboard'=>[
[['text'=>"🛒 Xarid qilish",'callback_data'=>"subcribe1"]]
]]));
}
}

if(mb_stripos($data,"iPaid_")!==false){
del();
sms($cid2,"<b>🧾 To'lov haqidagi chekni yuboring:</b>",$back);
step($cid2,$data);
}

if(mb_stripos($step,"iPaid_")!==false){
$cost = explode("_",$step)[1];
$photo = $message->photo;
$file = $photo[count($photo)-1]->file_id;
sms($cid,"<b>⏱ So'rovingiz adminga yuborildi!</b>",json_encode([
'remove_keyboard'=>true
]));
bot('copyMessage',[
'chat_id'=>$TokhtasinovUz,
'from_chat_id'=>$cid,
'message_id'=>$mid,
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"✅ Tasdiqlash",'callback_data'=>"on=$cid=$cost"]],
[['text'=>"❌ Bekor qilish",'callback_data'=>"off=$cid"]],
]])
]);
step($cid,"none");
}

if(mb_stripos($data,"on=")!==false and admin($cid2)==1){
$odam=explode("=",$data)[1];
$narx=explode("=",$data)[2];
del();
$month_date=$narx/$sub_price;
$today=date('d.m.Y H:i');
$result = mysqli_query($connect,"SELECT * FROM obunalar WHERE user_id = $odam");
$row = mysqli_fetch_assoc($result);
if($row){
$subcribe_enddate = strtotime($row['subcribe_enddate']) + ((60 * 60) * 24) * ($month_date * 30);
$subcribe_enddate = date('d.m.Y H:i', $subcribe_enddate);
mysqli_query($connect,"UPDATE obunalar SET subcribe_enddate = '$subcribe_enddate' WHERE user_id = $odam");
sms($odam,"<b>✅ So'rovingiz qabul qilindi!</b>

<i>Siz sotib olgan PREMIUM obunangiz uzaytirildi.</i>",null);
sms($cid2,"<b>✅ Foydalanuvchi PREMIUM obunasi uzaytirildi!</b>",null);
}else{
$subcribe_end=date('d.m.Y H:i',strtotime("+$month_date months"));
$connect->query("INSERT INTO obunalar (user_id, subcribe_date, subcribe_enddate) VALUES ('$odam','$today','$subcribe_end')");
sms($odam,"<b>✅ So'rovingiz qabul qilindi!</b>

<i>Endilikda siz PREMIUM obuna sotib olgansiz.</i>",null);
sms($cid2,"<b>✅ Foydalanuvchiga $month_date oy uchun PREMIUM obuna topshirildi!</b>",null);
}
}



 

 

 

 

 

 

 

if(mb_stripos($data,"off=")!==false and admin($cid2)==1){
$odam=explode("=",$data)[1];
del();
sms($odam,"<b>⚠️ So'rovingiz qabul qilinmadi!</b>",null);
sms($cid2,"<b>⚠️ Foydalanuvchi cheki o'chirildi!</b>",null);
}

if($data=="checkSub"){
del();
if(joinchat($cid2) == true){
sms($cid2,"<b>👋 Assalomu alaykum $callname  botimizga xush kelibsiz.</b>

<i>✍🏻 Kino kodini yuboring.</i>",$menu);
}
}

if($text == "/top" or $data == "topRating"){
if(isset($data)) del();
$result = mysqli_query($connect,"SELECT * FROM `movie_data` ORDER BY `downloads` DESC LIMIT 10;");
$response = "";
$i = 1;
while($row = mysqli_fetch_assoc($result)){
$response .= "<b>$i)</b> ".$row['title']."\n";
$uz[] = ['text'=>$i,'callback_data'=>"topDown=".$row['id']];
$i++;
}
$keyboard2=array_chunk($uz,5);
$kb=json_encode(['inline_keyboard'=>$keyboard2]);
if(isset($data)){
sms($cid2,"<b>Eng ko'p ko'rilgan 10 filmlar:</b>\n\n$response",$kb);
}else{
sms($cid,"<b>Eng ko'p ko'rilgan 10 filmlar:</b>\n\n$response",$kb);
}
}

if(mb_stripos($data,"topDown=")!==false){
$kino_id=explode("=",$data)[1];
del();
$obunalar = $connect->query("SELECT * FROM obunalar WHERE user_id = '$cid2'");
$obuna = $obunalar->fetch_assoc();
$result = $connect->query("SELECT * FROM movie_data WHERE id = '$kino_id'");
$row = $result->fetch_assoc();
$title = $row['title'];
$movie_id = $row['movie_id'];
$downloads = $row['downloads'] + 1;
$kino_ids = $row['kino_ids'];
$caption = $row['caption'] == "" ? "\n\n<b>🗂 Yuklash:</b> $downloads" : "\n\n".base64_decode($row['caption'])."\n<b>🗂 Yuklash:</b> $downloads";
$connect->query("UPDATE movie_data SET downloads = '$downloads' WHERE id = '$kino_id'");
if($row['type']=="usual" or ($row['type']=="premium" and isset($obuna))){
bot('sendVideo',[
'chat_id'=>$cid2,
'video'=>$movie_id,
'caption'=>"<b>🎬{$title}{$caption}

<b>🤖 Bizning bot:</b> @$bot</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"♻️ Do'stlarga ulashish",'url'=>"https://t.me/share/url?url=https://t.me/$bot?start=$kino_ids"]],
[['text'=>"❌",'callback_data'=>"close"]]
]
])
]);
}else{
sms($cid2,"<b>Ushbu filmni ko'rish uchun PREIUM obuna sotib olishingiz zarur!</b>

<i>PREMIUM obuna sotib olish uchun /premium buyrug'ini yuboring.</i>",null);
}
}

if($data == "close") del();



 

 

 

 

 

 

 

 

 

if(is_numeric($text)==true and (empty($step) or $step == "none") and joinchat($cid)==1){
$result = $connect->query("SELECT * FROM movie_data WHERE kino_ids = '$text'");
$row = $result->fetch_assoc();
$obunalar = $connect->query("SELECT * FROM obunalar WHERE user_id = '$cid'");
$obuna = $obunalar->fetch_assoc();
if(isset($row)){
$result = $connect->query("SELECT * FROM movie_data WHERE kino_ids = '$text'");
if($result->num_rows > 1){
$texts = "🔍 Natijalar {$result->num_rows}\n\n";
$counter = 1;
while($rows = $result->fetch_assoc()){
$title = $rows['title'];
$texts .= "<b>$counter.</b> {$title}\n";
$uz[] = ['text'=>$counter,'callback_data'=>"downloadMovie=".$rows['id']];
$counter++;
}
$keyboard2 = array_chunk($uz,5);
$keyboard2[] = [['text'=>"🏠 Menu",'callback_data'=>"checkSub"]];
$kb = json_encode(['inline_keyboard'=>$keyboard2]);
if($row['type']=="usual" or ($row['type']=="premium" and isset($obuna))){
sms($cid,$texts,$kb);
exit;
}else{
sms($cid,"<b>Ushbu filmni ko'rish uchun PREIUM obuna sotib olishingiz zarur!</b>

<i>PREMIUM obuna sotib olish uchun /premium buyrug'ini yuboring.</i>",null);
exit;
}
}else{
$row = $result->fetch_assoc();
$title = $row['title'];
$downloads = $row['downloads'] + 1;
$caption = $row['caption'] == "" ? "\n\n<b>🗂 Yuklash:</b> $downloads" : "\n\n".base64_decode($row['caption'])."\n<b>🗂 Yuklash:</b> $downloads";
$movie_id = $row['movie_id'];
$connect->query("UPDATE movie_data SET downloads = '$downloads' WHERE kino_ids = '$text'");
if($row['type']=="usual" or ($row['type']=="premium" and isset($obuna))){
bot('sendVideo',[
'chat_id'=>$cid,
'video'=>$movie_id,
'caption'=>"<b>🎬{$title}{$caption}

<b>🤖 Bizning bot:</b> @$bot</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"♻️ Do'stlarga ulashish",'url'=>"https://t.me/share/url?url=https://t.me/$bot?start=$text"]],
[['text'=>"❌",'callback_data'=>"close"]]
]
])
]);
exit;
}else{
sms($cid,"<b>Ushbu filmni ko'rish uchun PREIUM obuna sotib olishingiz zarur!</b>

<i>PREMIUM obuna sotib olish uchun /premium buyrug'ini yuboring.</i>",null);
exit;
}
}
}else{
sms($cid,"<b>❌ Bunday kodli kino mavjud emas yoki olib tashlangan!</b>",null);
exit;
}
}


if(mb_stripos($data,"searchMovieByCode=")!==false){
$text = explode("=",$data)[1];
del();
if(joinchat($cid2)==1){
$result = $connect->query("SELECT * FROM movie_data WHERE kino_ids = '$text'");
$row = $result->fetch_assoc();
$obunalar = $connect->query("SELECT * FROM obunalar WHERE user_id = '$cid2'");
$obuna = $obunalar->fetch_assoc();
if(isset($row)){
$result = $connect->query("SELECT * FROM movie_data WHERE kino_ids = '$text'");
if($result->num_rows > 1){
$texts = "🔍 Natijalar {$result->num_rows}\n\n";
$counter = 1;
while($rows = $result->fetch_assoc()){
$title = $rows['title'];
$texts .= "<b>$counter.</b> {$title}\n";
$uz[] = ['text'=>$counter,'callback_data'=>"downloadMovie=".$rows['id']];
$counter++;
}
$keyboard2 = array_chunk($uz,5);
$keyboard2[] = [['text'=>"🏠 Menu",'callback_data'=>"checkSub"]];
$kb = json_encode(['inline_keyboard'=>$keyboard2]);
if($row['type']=="usual" or ($row['type']=="premium" and isset($obuna))){
sms($cid2,$texts,$kb);
}else{
sms($cid2,"<b>Ushbu filmni ko'rish uchun PREIUM obuna sotib olishingiz zarur!</b>

<i>PREMIUM obuna sotib olish uchun /premium buyrug'ini yuboring.</i>",null);
}
}else{
$row = $result->fetch_assoc();
$title = $row['title'];
$downloads = $row['downloads'] + 1;
$caption = $row['caption'] == "" ? "\n\n<b>🗂 Yuklash:</b> $downloads" : "\n\n".base64_decode($row['caption'])."\n<b>🗂 Yuklash:</b> $downloads";
$movie_id = $row['movie_id'];
$connect->query("UPDATE movie_data SET downloads = '$downloads' WHERE kino_ids = '$text'");
if($row['type']=="usual" or ($row['type']=="premium" and isset($obuna))){
bot('sendVideo',[
'chat_id'=>$cid2,
'video'=>$movie_id,
'caption'=>"<b>🎬{$title}{$caption}

<b>🤖 Bizning bot:</b> @$bot</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"♻️ Do'stlarga ulashish",'url'=>"https://t.me/share/url?url=https://t.me/$bot?start=$text"]],
[['text'=>"❌",'callback_data'=>"close"]]
]
])
]);
}else{
sms($cid2,"<b>Ushbu filmni ko'rish uchun PREIUM obuna sotib olishingiz zarur!</b>

<i>PREMIUM obuna sotib olish uchun /premium buyrug'ini yuboring.</i>",null);
}
}
}else{
sms($cid2,"<b>❌ Bunday kodli kino mavjud emas yoki olib tashlangan!</b>",null);
}
}
}



 

 

 

 

 

 

 

 

if(mb_stripos($text,"/start ")!==false){
$text = explode(" ",$text)[1];
if(joinchat($cid)==1){
$result = $connect->query("SELECT * FROM movie_data WHERE kino_ids = '$text'");
$row = $result->fetch_assoc();
$obunalar = $connect->query("SELECT * FROM obunalar WHERE user_id = '$cid2'");
$obuna = $obunalar->fetch_assoc();
if(isset($row)){
$result = $connect->query("SELECT * FROM movie_data WHERE kino_ids = '$text'");
if($result->num_rows > 1){
$texts = "🔍 Natijalar {$result->num_rows}\n\n";
$counter = 1;
while($rows = $result->fetch_assoc()){
$title = $rows['title'];
$texts .= "<b>$counter.</b> {$title}\n";
$uz[] = ['text'=>$counter,'callback_data'=>"downloadMovie=".$rows['id']];
$counter++;
}
$keyboard2 = array_chunk($uz,5);
$keyboard2[] = [['text'=>"🏠 Menu",'callback_data'=>"checkSub"]];
$kb = json_encode(['inline_keyboard'=>$keyboard2]);
if($row['type']=="usual" or ($row['type']=="premium" and isset($obuna))){
sms($cid,$texts,$kb);
exit;
}else{
sms($cid,"<b>Ushbu filmni ko'rish uchun PREIUM obuna sotib olishingiz zarur!</b>

<i>PREMIUM obuna sotib olish uchun /premium buyrug'ini yuboring.</i>",null);
exit;
}
}else{
$row = $result->fetch_assoc();
$title = $row['title'];
$downloads = $row['downloads'] + 1;
$caption = $row['caption'] == "" ? "\n\n<b>🗂 Yuklash:</b> $downloads" : "\n\n".base64_decode($row['caption'])."\n<b>🗂 Yuklash:</b> $downloads";
$movie_id = $row['movie_id'];
$connect->query("UPDATE movie_data SET downloads = '$downloads' WHERE kino_ids = '$text'");
if($row['type']=="usual" or ($row['type']=="premium" and isset($obuna))){
bot('sendVideo',[
'chat_id'=>$cid,
'video'=>$movie_id,
'caption'=>"<b>🎬{$title}{$caption}

<b>🤖 Bizning bot:</b> @$bot</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"♻️ Do'stlarga ulashish",'url'=>"https://t.me/share/url?url=https://t.me/$bot?start=$text"]],
[['text'=>"❌",'callback_data'=>"close"]]
]
])
]);
exit;
}else{
sms($cid,"<b>Ushbu filmni ko'rish uchun PREIUM obuna sotib olishingiz zarur!</b>

<i>PREMIUM obuna sotib olish uchun /premium buyrug'ini yuboring.</i>",null);
exit;
}
}
}else{
sms($cid,"<b>❌ Bunday kodli kino mavjud emas yoki olib tashlangan!</b>",null);
exit;
}
}
}

if(mb_stripos($data,"downloadMovie=")!==false){
$kino_id=explode("=",$data)[1];
del();
$result = $connect->query("SELECT * FROM movie_data WHERE id = '$kino_id'");
$row = $result->fetch_assoc();
$obunalar = $connect->query("SELECT * FROM obunalar WHERE user_id = '$cid2'");
$obuna = $obunalar->fetch_assoc();
$title = $row['title'];
$movie_id = $row['movie_id'];
$downloads = $row['downloads'] + 1;
$kino_ids = $row['kino_ids'];
$caption = $row['caption'] == "" ? "<b>🗂 Yuklash:</b> $downloads\n" : "".base64_decode($row['caption'])."\n<b>🗂 Yuklash:</b> $downloads\n";
$connect->query("UPDATE movie_data SET downloads = '$downloads' WHERE id = '$kino_id'");
if($row['type']=="usual" or ($row['type']=="premium" and isset($obuna))){
bot('sendVideo',[
'chat_id'=>$cid2,
'video'=>$movie_id,
'caption'=>"<b>🎬{$title}{$caption}

<b>🤖 Bizning bot:</b> @$bot</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"♻️ Do'stlarga ulashish",'url'=>"https://t.me/share/url?url=https://t.me/$bot?start=$kino_ids"]],
[['text'=>"❌",'callback_data'=>"close"]]
]
])
]);
}else{
sms($cid2,"<b>Ushbu filmni ko'rish uchun PREIUM obuna sotib olishingiz zarur!</b>

<i>PREMIUM obuna sotib olish uchun /premium buyrug'ini yuboring.</i>",null);
}
}



 

 

 

 

 

 

 

 

 

if(isset($text) and (empty($step) or $step == "none") and joinchat($cid)==1){
if($text != "/panel" and $text != "◀️ Orqaga" and $text != "/start" and $text != "/top" and $text != "/vip" and $text != "/premium" and $text != "🤖 Bot holati" and $text != "📊 Statistika" and $text != "✉ Xabar Yuborish" and $text != "🗄 Boshqarish" and $text != "👨🏻‍⚖️ Adminlar sozlamalari" and $text !="➕ Administrator qo'shish" and $text != "🗑️ Administrator o‘chirish" and $text != "📋 Administrator ro'yxati" and $text != "📺 Kino sozlamalari" and $text != "📥 Kino yuklash" and $text != "🗑️ Kino o‘chirish" and $text != "➕ Kino qo'shish" and $text != "📝 Kino tahrirlash" and $text != "📢 Kanallar sozlamalari" and $text != "➕ Kanal qo'shish" and $text != "🗑️ Kanal o'chirish" and $text != "*️⃣ Qo'shimcha kanallar"){
$text = str_replace(["‘","’","´","`"],["'","'","'","'"],$text);
$text = $connect->real_escape_string($text);
$stat = mysqli_num_rows(mysqli_query($connect,"SELECT * FROM `movie_data` WHERE title LIKE '%$text%'"));
if($stat == 0){
    sms($cid,"<b>😔 Hech narsa topilmadi

Nomni boshqacharoq yozib koʻring yoki kino kodini yuboring.</b>",null);
    exit;
}else{
    $result = mysqli_query($connect,"SELECT * FROM `movie_data` WHERE title LIKE '%$text%' LIMIT 0,10");
    $i = 1;
    $ttt = "";
    $uz = [];
    while($a = mysqli_fetch_assoc($result)){
        $ttt .= "$i. <b>$a[title]</b>\n";
        $uz[]=['text'=>$i,'callback_data'=>"downloadMovie=".$a['id']];
        $i++;
    }
    $keyboard2=array_chunk($uz,5);
    $keyboard2[]=[['text'=>"⬅️",'callback_data'=>"pagenationMovie_{$text}_0_back"],['text'=>"❌",'callback_data'=>"close"],['text'=>"➡️",'callback_data'=>"pagenationMovie_{$text}_0_next"]];
    $kb=json_encode(['inline_keyboard'=>$keyboard2]);
    $stat2 = $stat < 10 ? $stat : 10;
    sms($cid,"<b>Natijalar 1-$stat2 ($stat dan)</b>\n\n$ttt",$kb);
    exit;
}
}
}

if(stripos($data,"pagenationMovie_")!==false){
$exp = explode("_",$data);
$text = $exp[1];
$nid = $exp[2];
$tp = $exp[3];
if($tp=="next")$nn = $nid+10;
if($tp=="back")$nn = $nid-10;

$result = mysqli_query($connect,"SELECT * FROM `movie_data` WHERE title LIKE '%$text%' LIMIT $nn,10");
$c = mysqli_num_rows($result);
$i = 1;
$ttt = "";
while($a = mysqli_fetch_assoc($result)){
$ttt .= "$i. <b>$a[title]</b>\n";
$uz[]=['text'=>$i,'callback_data'=>"downloadMovie=".$a['id']];
$i++;
}
$ara = array_chunk($uz,5);
$ara[]=[['text'=>"⬅️",'callback_data'=>"pagenationMovie_{$text}_{$nn}_back"],['text'=>"❌",'callback_data'=>"close"],['text'=>"➡️",'callback_data'=>"pagenationMovie_{$text}_{$nn}_next"]];
$json = json_encode(['inline_keyboard'=>$ara]);
if($c == "0" or $nn < "0"){
bot('answerCallbackQuery',[
'callback_query_id'=>$qid,
'text'=>"😔 Hech narsa topilmadi!",
'show_alert'=>true
]);
exit;
}else{
$stat = mysqli_num_rows(mysqli_query($connect,"SELECT * FROM `movie_data` WHERE title LIKE '%$text%'"));
$allstat = $nn+10;
$stat2 = $stat < $allstat ? $stat : $allstat;
edit($cid2,$mid2,"<b>Natijalar ".($nn+1)."-$stat2 $stat dan</b>\n\n$ttt",$json);
}
}


// <--------- @obito_us --------->


if($_GET['update']=="send"){
$result = mysqli_query($connect, "SELECT * FROM `send`"); 
$row = mysqli_fetch_assoc($result);
$time = date('H:i');
if($row['status']=="resume"){
$row1 = $row['time1'];
$row2 = $row['time2'];
$start_id = $row['start_id'];
$stop_id = $row['stop_id'];
$admin_id = $row['admin_id'];
$mied = $row['message_id'];
$edit_mess_id = $row['edit_mess_id'];
$sends_count = $row['sends_count'] ?? 0;
$receive_count = $row['receive_count'] ?? 0;
$statistics = $row['statistics'];
$time1 = date('H:i', strtotime('+1 minutes'));
$time2 = date('H:i', strtotime('+2 minutes'));
$limit = 100;

if($time == $row1 or $time == $row2){
$sql = "SELECT * FROM `users` LIMIT $start_id,$limit";
$res = mysqli_query($connect,$sql);
while($a = mysqli_fetch_assoc($res)){
$id = $a['user_id'];
$receive_check = bot('forwardMessage',[
'chat_id'=>$id,
'from_chat_id'=>$admin_id,
'message_id'=>$mied
]);
$sends_count++;
if($receive_check->ok){
$receive_count++;
}
if($id == $stop_id){
bot('sendMessage',[
'chat_id'=>$admin_id,
'text'=>"<b>✅ ️Xabar barcha bot foydalanuvchilariga yuborildi!</b>",
'parse_mode'=>'html'
]);
mysqli_query($connect, "DELETE FROM `send`");
break;
}
}
mysqli_query($connect, "UPDATE `send` SET `time1` = '$time1'");
mysqli_query($connect, "UPDATE `send` SET `time2` = '$time2'");
$get_id = $start_id + $limit;
mysqli_query($connect, "UPDATE `send` SET `start_id` = '$get_id'");
mysqli_query($connect, "UPDATE `send` SET `sends_count` = '$sends_count'");
mysqli_query($connect, "UPDATE `send` SET `receive_count` = '$receive_count'");
$edit = bot('editMessageText',[
'chat_id'=>$admin_id,
'message_id'=>$edit_mess_id,
'text'=>"<b>✅ Yuborildi:</b> <code>$sends_count/$statistics</code>
<b>📥 Qabul qilindi:</b> <code>$receive_count</code>
<b>🔰 Status</b>: <code>resume</code>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"To'xtatish ⏸",'callback_data'=>"sendstatus=stopped"]],
[['text'=>"🗑 O'chirish",'callback_data'=>"bekorqilish_send"]]
]
])
]);
if($edit->ok){
$edit_mess_id = $edit->result->message_id;
mysqli_query($connect, "UPDATE `send` SET `edit_mess_id` = '$edit_mess_id'");
}
}
echo json_encode(["status"=>true,"cron"=>"Sending message"]);
}
}

if($_GET['update'] == "subcribe"){
$query = $connect->query("SELECT * FROM obunalar");
$date = date("d.m.Y H:i");
while($row = $query->fetch_assoc()){
$id = $row['id'];
if($row['subcribe_enddate'] == $date){
$connect->query("DELETE FROM obunalar WHERE id = $id");
sms($row['user_id'],"<b>💎 Premium obunanangiz bugun tugadi!</b>",null);
}
}
}



 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 

 