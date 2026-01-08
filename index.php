<?php
/* ============ SOZLAMALAR ============ */
$token = "8487583708:AAFI9Pl4g2sYqJDjfg2Xof1Lu274kpJPRD0"; 
$admin = [7602739916]; 
$admin_username = "@admin_profil"; 
$instagram = "https://www.instagram.com/gojosufx?igsh=MWlyM2c4cHpjNm5zZA==";

$api = "https://api.telegram.org/bot".$token;
$update = json_decode(file_get_contents("php://input"), true);

$message = $update["message"] ?? null;
$chat_id = $message["chat"]["id"] ?? $update["callback_query"]["message"]["chat"]["id"];
$text = $message["text"] ?? null;
$mid = $message["message_id"] ?? $update["callback_query"]["message"]["message_id"];
$data = $update["callback_query"]["data"] ?? null;

/* ============ BAZALAR ============ */
if(!file_exists("videos.json")) file_put_contents("videos.json", "{}");
if(!file_exists("steps.json")) file_put_contents("steps.json", "{}");
if(!file_exists("all_users.json")) file_put_contents("all_users.json", "[]");

$videos = json_decode(file_get_contents("videos.json"), true);
$steps = json_decode(file_get_contents("steps.json"), true);
$all_users = json_decode(file_get_contents("all_users.json"), true);

if($chat_id && !in_array($chat_id, $all_users)){
    $all_users[] = $chat_id;
    file_put_contents("all_users.json", json_encode($all_users));
}

function bot($method, $params = []){
    global $api;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api."/".$method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    return json_decode(curl_exec($ch), true);
}

/* ============ 1. ESKI XABARLARNI TOZALASH ============ */
if($text || $data){
    bot("deleteMessage", ["chat_id" => $chat_id, "message_id" => $mid - 1]);
}

/* ============ 2. START VA ADMIN PANEL ============ */
if($text == "/start"){
    $steps[$chat_id] = ""; 
    file_put_contents("steps.json", json_encode($steps));
    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "<b>Assalomu alaykum!</b>\n\nBotdan foydalanish uchun Instagram sahifamizga obuna bo'ling 👇",
        "parse_mode" => "HTML",
        "reply_markup" => json_encode([
            "inline_keyboard" => [
                [["text" => "📸 Instagram", "url" => $instagram]],
                [["text" => "✅ Tasdiqlash", "callback_data" => "verify"]]
            ]
        ])
    ]);
    exit;
}

if($data == "verify"){
    bot("editMessageText", [
        "chat_id" => $chat_id,
        "message_id" => $mid,
        "text" => "✨ <b>Xush kelibsiz!</b>\n\nQidirish usulini tanlang:",
        "parse_mode" => "HTML",
        "reply_markup" => json_encode([
            "keyboard" => [[["text" => "🔢 Kod orqali"], ["text" => "🔤 Nomi orqali"]]],
            "resize_keyboard" => true
        ])
    ]);
}

if($text == "/admin" && in_array($chat_id, $admin)){
    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "👮‍♂️ <b>Admin Panel</b>",
        "reply_markup" => json_encode([
            "keyboard" => [
                [["text" => "➕ Anime qo'shish"], ["text" => "➖ Anime o'chirish"]],
                [["text" => "📊 Statistika"], ["text" => "📣 Reklama"]]
            ],
            "resize_keyboard" => true
        ])
    ]);
}

/* ============ 3. ANIME QO'SHISH JARAYONI ============ */
if($text == "➕ Anime qo'shish" && in_array($chat_id, $admin)){
    $steps[$chat_id] = "add_info";
    file_put_contents("steps.json", json_encode($steps));
    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "Anime ma'lumotlarini quyidagi formatda yuboring:\n\n<code>Kod|Nomi|Qism|KanalLink|Holati</code>\n\n<i>Misol: 1|Naruto|220|https://t.me/kanal|Tugallangan</i>",
        "parse_mode" => "HTML"
    ]);
    exit;
}

if(($steps[$chat_id] ?? "") == "add_info" && in_array($chat_id, $admin)){
    $steps[$chat_id] = "add_photo|" . $text;
    file_put_contents("steps.json", json_encode($steps));
    bot("sendMessage", ["chat_id" => $chat_id, "text" => "Endi ushbu anime uchun rasm yuboring:"]);
    exit;
}
if(strpos(($steps[$chat_id] ?? ""), "add_photo|") === 0 && isset($message['photo'])){
    $ex = explode("|", $steps[$chat_id]);
    $photo_id = $message['photo'][count($message['photo'])-1]['file_id'];
    
    // Bazaga saqlash: [1]Kod, [2]Nomi, [3]Qism, [4]Link, [5]Holati
    $videos[$ex[1]] = [
        "title" => $ex[2],
        "parts" => $ex[3],
        "link" => $ex[4],
        "status" => $ex[5],
        "photo" => $photo_id
    ];
    
    file_put_contents("videos.json", json_encode($videos));
    $steps[$chat_id] = "";
    file_put_contents("steps.json", json_encode($steps));
    bot("sendMessage", ["chat_id" => $chat_id, "text" => "✅ Anime muvaffaqiyatli qo'shildi!"]);
    exit;
}

/* ============ QIDIRUV VA NATIJA JARAYONI ============ */

// 1. Qidiruv turini tanlash
if($text == "🔢 Kod orqali" || $text == "🔤 Nomi orqali"){
    $steps[$chat_id] = ($text == "🔢 Kod orqali") ? "search_code" : "search_name";
    file_put_contents("steps.json", json_encode($steps));
    bot("sendMessage", [
        "chat_id" => $chat_id, 
        "text" => "🔍 ".($text == "🔢 Kod orqali" ? "Iltimos, anime kodini kiriting:" : "Iltimos, anime nomini yozing:"),
        "reply_markup" => json_encode(["remove_keyboard" => true])
    ]);
    exit;
}

// 2. Qidiruv natijasini qayta ishlash
if($text && !in_array($text, ["/start", "/admin", "📊 Statistika", "📣 Reklama", "➕ Anime qo'shish"])){
    $st = $steps[$chat_id] ?? "";
    
    // KOD BO'YICHA QIDIRUV
    if($st == "search_code"){
        if(isset($videos[$text])){
            $found = $videos[$text];
            showAnime($chat_id, $found);
        } else {
            bot("sendMessage", ["chat_id" => $chat_id, "text" => "😔 Kechirasiz, ushbu kod bilan anime topilmadi."]);
        }
        backToMenu($chat_id);
    } 
    
    // NOM BO'YICHA QIDIRUV (Kreativ qidiruv)
    elseif($st == "search_name"){
        $matches = [];
        $search_query = mb_strtolower($text); // Qidiruv matnini kichik harfga o'tkazamiz

        foreach($videos as $code => $v){
            $anime_title = mb_strtolower($v['title']); // Bazadagi nomni kichik harfga o'tkazamiz
            if(mb_strpos($anime_title, $search_query) !== false){
                $matches[] = ["text" => $v['title'], "callback_data" => "get_anime|".$code];
            }
        }

        if(count($matches) == 1){
            // Agar bitta o'xshashlik bo'lsa, to'g'ridan-to'g'ri chiqarish
            $code = explode("|", $matches[0]['callback_data'])[1];
            showAnime($chat_id, $videos[$code]);
            backToMenu($chat_id);
        } 
        elseif(count($matches) > 1){
            // Agar bir nechta bo'lsa, tugma qilib chiqarish
            $buttons = array_chunk($matches, 1); // Har bir tugmani alohida qatorda qilish
            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "🔍 Bir nechta natijalar topildi, tanlang:",
                "reply_markup" => json_encode(["inline_keyboard" => $buttons])
            ]);
        } 
        else {
            bot("sendMessage", ["chat_id" => $chat_id, "text" => "😔 Bunday nomli anime topilmadi."]);
            backToMenu($chat_id);
        }
    }
}

// 3. Tugmani bosganda animeni ko'rsatish (Callback)
if(strpos($data, "get_anime|") === 0){
    $code = explode("|", $data)[1];
    if(isset($videos[$code])){
        showAnime($chat_id, $videos[$code]);
    }
}

/* ============ YORDAMCHI FUNKSIYALAR ============ */

function showAnime($chat_id, $anime){
    bot("sendPhoto", [
        "chat_id" => $chat_id,
        "photo" => $anime['photo'],
        "caption" => "🎬 <b>Nomi:</b> {$anime['title']}\n🎞 <b>Qismlar:</b> {$anime['parts']}\n🌟 <b>Holati:</b> {$anime['status']}",
        "parse_mode" => "HTML",
        "reply_markup" => json_encode([
            "inline_keyboard" => [[["text" => "💻 Tomosha qilish", "url" => $anime['link']]]]
        ])
    ]);
}

function backToMenu($chat_id){
    global $steps;
    $steps[$chat_id] = "";
    file_put_contents("steps.json", json_encode($steps));
    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "Yana qidirishni xohlaysizmi?",
        "reply_markup" => json_encode([
            "keyboard" => [[["text" => "🔢 Kod orqali"], ["text" => "🔤 Nomi orqali"]]],
            "resize_keyboard" => true
        ])
    ]);
}