<?php
/* ========== SOZLAMALAR ========== */
$token = "BOT_TOKENNI_BU_YERGA_QOY";
$api   = "https://api.telegram.org/bot".$token;

/* ========== FILELAR ========== */
foreach([
    "videos.json"=>"{}",
    "steps.json"=>"{}",
    "users.json"=>"[]",
    "admins.json"=>"[7602739916]"
] as $f=>$d){
    if(!file_exists($f)) file_put_contents($f,$d);
}

$videos=json_decode(file_get_contents("videos.json"),true);
$steps =json_decode(file_get_contents("steps.json"),true);
$users =json_decode(file_get_contents("users.json"),true);
$admins=json_decode(file_get_contents("admins.json"),true);

/* ========== UPDATE ========== */
$u=json_decode(file_get_contents("php://input"),true);
$msg=$u["message"]??null;
$cb =$u["callback_query"]??null;
$inline=$u["inline_query"]??null;

$chat_id=$msg["chat"]["id"]??$cb["message"]["chat"]["id"]??null;
$text=trim($msg["text"]??"");
$data=$cb["data"]??null;
$mid =$msg["message_id"]??null;
$uid =$msg["from"]["id"]??$cb["from"]["id"]??null;

/* ========== BOT FUNKSIYA ========== */
function bot($m,$p=[]){
    global $api;
    $ch=curl_init($api."/".$m);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_POSTFIELDS=>$p]);
    return json_decode(curl_exec($ch),true);
}

/* ========== USER SAQLASH ========== */
if($chat_id && !in_array($chat_id,$users)){
    $users[]=$chat_id;
    file_put_contents("users.json",json_encode($users));
}

/* ========== USER XABARINI O‘CHIRISH ========== */
if($msg && !$msg["from"]["is_bot"]){
    @bot("deleteMessage",["chat_id"=>$chat_id,"message_id"=>$mid]);
}

/* ========== INLINE QIDIRUV ========== */
if($inline){
    $q=mb_strtolower(trim($inline["query"]));
    $res=[];$i=0;

    foreach($videos as $v){
        if($q=="" || mb_stripos(mb_strtolower($v["title"]),$q)!==false){
            $res[]=[
                "type"=>"article",
                "id"=>(string)$i++,
                "title"=>$v["title"],
                "description"=>$v["parts"]." | ".$v["status"],
                "input_message_content"=>[
                    "message_text"=>"🎬 <b>{$v['title']}</b>\n🎞 {$v['parts']}\n📌 {$v['status']}\n▶️ {$v['link']}",
                    "parse_mode"=>"HTML"
                ]
            ];
        }
        if($i>=20) break;
    }

    bot("answerInlineQuery",[
        "inline_query_id"=>$inline["id"],
        "results"=>json_encode($res),
        "cache_time"=>5
    ]);
    exit;
}

/* ========== START ========== */
if($text=="/start"){
    $steps[$chat_id]="";
    file_put_contents("steps.json",json_encode($steps));

    bot("sendMessage",[
        "chat_id"=>$chat_id,
        "text"=>"🎌 <b>Anime izlash</b>\nQidirish turini tanlang:",
        "parse_mode"=>"HTML",
        "reply_markup"=>json_encode([
            "inline_keyboard"=>[
                [["text"=>"🔢 Kod orqali","callback_data"=>"code"]],
                [["text"=>"🔤 Nomi orqali","callback_data"=>"name"]]
            ]
        ])
    ]);
    exit;
}

/* ========== CALLBACK QIDIRUV ========== */
if($data=="code" || $data=="name"){
    $steps[$chat_id]=$data;
    file_put_contents("steps.json",json_encode($steps));

    bot("sendMessage",[
        "chat_id"=>$chat_id,
        "text"=>"🔍 Qidiruvni kiriting:",
        "reply_markup"=>json_encode([
            "keyboard"=>[[["🔙 Back"]]],
            "resize_keyboard"=>true
        ])
    ]);
    exit;
}

/* ========== BACK ========== */
if($text=="🔙 Back"){
    $steps[$chat_id]="";
    file_put_contents("steps.json",json_encode($steps));
    bot("sendMessage",[
        "chat_id"=>$chat_id,
        "text"=>"Qidirish turini tanlang:",
        "reply_markup"=>json_encode([
            "inline_keyboard"=>[
                [["text"=>"🔢 Kod orqali","callback_data"=>"code"]],
                [["text"=>"🔤 Nomi orqali","callback_data"=>"name"]]
            ]
        ])
    ]);
    exit;
}

/* ========== QIDIRUV NATIJA ========== */
if(isset($steps[$chat_id]) && $text){
    $found=[];

foreach($videos as $code=>$v){
        if(
            ($steps[$chat_id]=="code" && $code==$text) ||
            ($steps[$chat_id]=="name" && mb_stripos(mb_strtolower($v["title"]),mb_strtolower($text))!==false)
        ){
            $found[]=$v;
        }
    }

    if($found){
        foreach($found as $f){
            bot("sendPhoto",[
                "chat_id"=>$chat_id,
                "photo"=>$f["photo"],
                "caption"=>"🎬 <b>{$f['title']}</b>\n🎞 {$f['parts']}\n📌 {$f['status']}",
                "parse_mode"=>"HTML",
                "reply_markup"=>json_encode([
                    "inline_keyboard"=>[
                        [["text"=>"▶️ Tomosha qilish","url"=>$f["link"]]]
                    ]
                ])
            ]);
        }
    }else{
        bot("sendMessage",[
            "chat_id"=>$chat_id,
            "text"=>"😔 Topilmadi"
        ]);
    }
    exit;
}

/* ========== ADMIN PANEL ========== */
if($text=="/admin" && in_array($uid,$admins)){
    bot("sendMessage",[
        "chat_id"=>$chat_id,
        "text"=>"👮‍♂️ Admin panel",
        "reply_markup"=>json_encode([
            "keyboard"=>[
                [["➕ Anime qo‘shish"],["❌ Anime o‘chirish"]],
                [["📣 Reklama"],["📊 Statistika"]],
                [["👑 Admin qo‘shish"]]
            ],
            "resize_keyboard"=>true
        ])
    ]);
    exit;
}

/* ========== ANIME QO‘SHISH ========== */
if($text=="➕ Anime qo‘shish" && in_array($uid,$admins)){
    $steps[$chat_id]="add_info";
    file_put_contents("steps.json",json_encode($steps));
    bot("sendMessage",[
        "chat_id"=>$chat_id,
        "text"=>"Kod|Nomi|Qismlar|Link|Holati"
    ]);
    exit;
}

if(($steps[$chat_id]??"")=="add_info" && in_array($uid,$admins)){
    $ex=explode("|",$text);
    if(count($ex)!=5){ bot("sendMessage",["chat_id"=>$chat_id,"text"=>"❌ Format xato"]); exit; }
    $steps[$chat_id]="add_photo|".$text;
    file_put_contents("steps.json",json_encode($steps));
    bot("sendMessage",["chat_id"=>$chat_id,"text"=>"📸 Rasm yuboring"]);
    exit;
}

if(strpos(($steps[$chat_id]??""),"add_photo|")===0 && isset($msg["photo"]) && in_array($uid,$admins)){
    $d=explode("|",str_replace("add_photo|","",$steps[$chat_id]));
    $videos[$d[0]]=[
        "title"=>$d[1],
        "parts"=>$d[2],
        "link"=>$d[3],
        "status"=>$d[4],
        "photo"=>end($msg["photo"])["file_id"]
    ];
    file_put_contents("videos.json",json_encode($videos));
    $steps[$chat_id]="";
    file_put_contents("steps.json",json_encode($steps));
    bot("sendMessage",["chat_id"=>$chat_id,"text"=>"✅ Anime qo‘shildi"]);
    exit;
}

/* ========== MAVZUDAN TASHQARI ========== */
bot("sendMessage",[
    "chat_id"=>$chat_id,
    "text"=>"⚠️ Bu bot faqat anime izlash uchun.\nAdmin bilan bog‘laning."
]);