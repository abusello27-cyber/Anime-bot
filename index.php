<?php
/* ========= SOZLAMALAR ========= */
$token = "8502164048:AAH3k8_4BkC048JMI6p5ZXP_c7fYl4CqXC8";
$api = "https://api.telegram.org/bot".$token;

/* ========= BAZALAR ========= */
if(!file_exists("videos.json")) file_put_contents("videos.json","{}");
if(!file_exists("steps.json")) file_put_contents("steps.json","{}");
if(!file_exists("users.json")) file_put_contents("users.json","[]");
if(!file_exists("admins.json")) file_put_contents("admins.json","[7602739916]");
if(!file_exists("lastmsg.json")) file_put_contents("lastmsg.json","{}");

$videos = json_decode(file_get_contents("videos.json"), true);
$steps  = json_decode(file_get_contents("steps.json"), true);
$users  = json_decode(file_get_contents("users.json"), true);
$admins = json_decode(file_get_contents("admins.json"), true);
$last   = json_decode(file_get_contents("lastmsg.json"), true);

/* ========= UPDATE ========= */
$update = json_decode(file_get_contents("php://input"), true);
$msg = $update["message"] ?? null;
$chat_id = $msg["chat"]["id"] ?? null;
$text = $msg["text"] ?? null;

/* ========= FUNKSIYA ========= */
function bot($m,$p=[]){
    global $api;
    $ch = curl_init($api."/".$m);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_POSTFIELDS=>$p
    ]);
    return json_decode(curl_exec($ch),true);
}

/* ========= OLD MESSAGE DELETE ========= */
if($chat_id && isset($last[$chat_id])){
    @bot("deleteMessage",[
        "chat_id"=>$chat_id,
        "message_id"=>$last[$chat_id]
    ]);
}

/* ========= USER SAQLASH ========= */
if($chat_id && !in_array($chat_id,$users)){
    $users[]=$chat_id;
    file_put_contents("users.json",json_encode($users));
}

/* ========= START ========= */
if($text=="/start"){
    $steps[$chat_id]="";
    file_put_contents("steps.json",json_encode($steps));

    $r = bot("sendMessage",[
        "chat_id"=>$chat_id,
        "text"=>"🎌 <b>Anime qidiruv bot</b>\n\nQidirish turini tanlang:",
        "parse_mode"=>"HTML",
        "reply_markup"=>json_encode([
            "keyboard"=>[
                [["text"=>"🔢 Kod orqali"],["text"=>"🔤 Nomi orqali"]]
            ],
            "resize_keyboard"=>true
        ])
    ]);
    $last[$chat_id]=$r["result"]["message_id"];
}

/* ========= ADMIN PANEL ========= */
elseif($text=="/admin" && in_array($chat_id,$admins)){
    $r = bot("sendMessage",[
        "chat_id"=>$chat_id,
        "text"=>"👮‍♂️ <b>Admin panel</b>",
        "parse_mode"=>"HTML",
        "reply_markup"=>json_encode([
            "keyboard"=>[
                [["text"=>"➕ Anime qo‘shish"]],
                [["text"=>"📣 Reklama yuborish"]],
                [["text"=>"➕ Admin qo‘shish"]],
                [["text"=>"📊 Statistika"]]
            ],
            "resize_keyboard"=>true
        ])
    ]);
    $last[$chat_id]=$r["result"]["message_id"];
}

/* ========= ANIME QO‘SHISH ========= */
elseif($text=="➕ Anime qo‘shish" && in_array($chat_id,$admins)){
    $steps[$chat_id]="add_info";
    file_put_contents("steps.json",json_encode($steps));
    $r = bot("sendMessage",[
        "chat_id"=>$chat_id,
        "text"=>"Format:\n<code>Kod|Nomi|Qismlar|Link|Holat</code>",
        "parse_mode"=>"HTML"
    ]);
    $last[$chat_id]=$r["result"]["message_id"];
}

elseif(($steps[$chat_id] ?? "")=="add_info" && in_array($chat_id,$admins)){
    $ex = explode("|",$text);
    if(count($ex)!=5){
        bot("sendMessage",["chat_id"=>$chat_id,"text"=>"❌ Format noto‘g‘ri"]);
        exit;
    }
    $steps[$chat_id]="add_photo|".$text;
    file_put_contents("steps.json",json_encode($steps));
    $r = bot("sendMessage",["chat_id"=>$chat_id,"text"=>"📸 Rasm yuboring"]);
    $last[$chat_id]=$r["result"]["message_id"];
}

elseif(strpos(($steps[$chat_id] ?? ""),"add_photo|")===0 && isset($msg["photo"])){
    $d = explode("|",str_replace("add_photo|","",$steps[$chat_id]));
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
    $r = bot("sendMessage",["chat_id"=>$chat_id,"text"=>"✅ Anime qo‘shildi"]);
    $last[$chat_id]=$r["result"]["message_id"];
}

/* ========= REKLAMA ========= */
elseif($text=="📣 Reklama yuborish" && in_array($chat_id,$admins)){
    $steps[$chat_id]="reklama";
    file_put_contents("steps.json",json_encode($steps));
    $r = bot("sendMessage",["chat_id"=>$chat_id,"text"=>"📣 Reklama matnini yuboring"]);
    $last[$chat_id]=$r["result"]["message_id"];
}

elseif(($steps[$chat_id] ?? "")=="reklama" && in_array($chat_id,$admins)){
    foreach($users as $u){
        bot("sendMessage",["chat_id"=>$u,"text"=>$text]);
    }
    $steps[$chat_id]="";
    file_put_contents("steps.json",json_encode($steps));
    $r = bot("sendMessage",["chat_id"=>$chat_id,"text"=>"✅ Reklama yuborildi"]);
    $last[$chat_id]=$r["result"]["message_id"];
}

/* ========= ADMIN QO‘SHISH ========= */
elseif($text=="➕ Admin qo‘shish" && in_array($chat_id,$admins)){
    $steps[$chat_id]="add_admin";
    file_put_contents("steps.json",json_encode($steps));
    $r = bot("sendMessage",["chat_id"=>$chat_id,"text"=>"🆔 Admin ID yuboring"]);
    $last[$chat_id]=$r["result"]["message_id"];
}

elseif(($steps[$chat_id] ?? "")=="add_admin" && in_array($chat_id,$admins)){
    if(is_numeric($text) && !in_array((int)$text,$admins)){
        $admins[]=(int)$text;
        file_put_contents("admins.json",json_encode($admins));
        $r = bot("sendMessage",["chat_id"=>$chat_id,"text"=>"✅ Admin qo‘shildi"]);
        $last[$chat_id]=$r["result"]["message_id"];
    }
    $steps[$chat_id]="";
    file_put_contents("steps.json",json_encode($steps));
}

/* ========= QIDIRUV ========= */
elseif($text=="🔢 Kod orqali" || $text=="🔤 Nomi orqali"){
    $steps[$chat_id]=($text=="🔢 Kod orqali")?"code":"name";
    file_put_contents("steps.json",json_encode($steps));
    $r = bot("sendMessage",["chat_id"=>$chat_id,"text"=>"🔍 Qidiruvni kiriting"]);
    $last[$chat_id]=$r["result"]["message_id"];
}

elseif($text && in_array(($steps[$chat_id] ?? ""),["code","name"])){
    $f=null;
    if($steps[$chat_id]=="code" && isset($videos[$text])) $f=$videos[$text];
    if($steps[$chat_id]=="name"){
        foreach($videos as $v){
            if(mb_stripos($v["title"],$text)!==false){$f=$v;break;}
        }
    }

    if($f){
        $r = bot("sendPhoto",[
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
        $last[$chat_id]=$r["result"]["message_id"];
    }else{
        $r = bot("sendMessage",["chat_id"=>$chat_id,"text"=>"😔 Topilmadi"]);
        $last[$chat_id]=$r["result"]["message_id"];
    }

    $steps[$chat_id]="";
    file_put_contents("steps.json",json_encode($steps));
}

/* ========= SAQLASH ========= */
file_put_contents("lastmsg.json",json_encode($last));
?>