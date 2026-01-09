<?php
error_reporting(0);

/* ====== SOZLAMALAR ====== */
$token = "8502164048:AAH3k8_4BkC048JMI6p5ZXP_c7fYl4CqXC8"; // <-- TOKEN
$admin_ids = [7602739916]; // <-- ADMIN ID lar

$api = "https://api.telegram.org/bot$token";

/* ====== FAYLLAR ====== */
foreach([
  "data.json"=>"{}",
  "users.json"=>"[]",
  "steps.json"=>"{}"
] as $f=>$d){
  if(!file_exists($f)) file_put_contents($f,$d);
}

$data = json_decode(file_get_contents("data.json"),true);
$users = json_decode(file_get_contents("users.json"),true);
$steps = json_decode(file_get_contents("steps.json"),true);

/* ====== UPDATE ====== */
$u = json_decode(file_get_contents("php://input"),true);
$m = $u["message"] ?? null;
$cb = $u["callback_query"] ?? null;
$in = $u["inline_query"] ?? null;

$cid = $m["chat"]["id"] ?? $cb["message"]["chat"]["id"] ?? null;
$uid = $m["from"]["id"] ?? $cb["from"]["id"] ?? null;
$text = trim($m["text"] ?? "");
$data_cb = $cb["data"] ?? "";
$mid = $m["message_id"] ?? null;

/* ====== FUNKSIYA ====== */
function bot($m,$p=[]){
  global $api;
  $ch = curl_init("$api/$m");
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_POSTFIELDS=>$p]);
  return curl_exec($ch);
}

/* ====== USER SAQLASH ====== */
if($cid && !in_array($cid,$users)){
  $users[]=$cid;
  file_put_contents("users.json",json_encode($users));
}

/* ====== USER XABARINI O‘CHIRISH ====== */
if($m && !$m["from"]["is_bot"]){
  bot("deleteMessage",["chat_id"=>$cid,"message_id"=>$mid]);
}

/* ====== INLINE QIDIRUV ====== */
if($in){
  $q = mb_strtolower(trim($in["query"]));
  $res = []; $i=0;
  foreach($data as $v){
    if($q=="" || mb_stripos(mb_strtolower($v["name"]),$q)!==false){
      $res[]=[
        "type"=>"article",
        "id"=>"$i",
        "title"=>$v["name"],
        "description"=>$v["part"]." | ".$v["status"],
        "input_message_content"=>[
          "message_text"=>"🎬 <b>{$v['name']}</b>\n🎞 {$v['part']}\n📌 {$v['status']}\n▶️ {$v['link']}",
          "parse_mode"=>"HTML"
        ]
      ];
      $i++;
    }
    if($i>=20) break;
  }
  bot("answerInlineQuery",[
    "inline_query_id"=>$in["id"],
    "results"=>json_encode($res),
    "cache_time"=>1
  ]);
  exit;
}

/* ====== START ====== */
if($text=="/start"){
  $steps[$cid]="";
  file_put_contents("steps.json",json_encode($steps));
  bot("sendMessage",[
    "chat_id"=>$cid,
    "text"=>"🎌 <b>Anime izlash</b>",
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

/* ====== CALLBACK ====== */
if($data_cb=="code" || $data_cb=="name"){
  $steps[$cid]=$data_cb;
  file_put_contents("steps.json",json_encode($steps));
  bot("sendMessage",[
    "chat_id"=>$cid,
    "text"=>"🔍 Qidiruvni yozing",
    "reply_markup"=>json_encode([
      "keyboard"=>[[["🔙 Back"]]],
      "resize_keyboard"=>true
    ])
  ]);
  exit;
}

/* ====== BACK ====== */
if($text=="🔙 Back"){
  $steps[$cid]="";
  file_put_contents("steps.json",json_encode($steps));
  bot("sendMessage",[
    "chat_id"=>$cid,
    "text"=>"Qidirish turini tanlang",
    "reply_markup"=>json_encode([
      "inline_keyboard"=>[
        [["text"=>"🔢 Kod orqali","callback_data"=>"code"]],
        [["text"=>"🔤 Nomi orqali","callback_data"=>"name"]]
      ]
    ])
  ]);
  exit;
}

/* ====== QIDIRUV ====== */
if(isset($steps[$cid]) && $text){
  $f=false;
  foreach($data as $k=>$v){
    if(
      ($steps[$cid]=="code" && $k==$text) ||
      ($steps[$cid]=="name" && mb_stripos(mb_strtolower($v["name"]),mb_strtolower($text))!==false)
    ){
      bot("sendPhoto",[
        "chat_id"=>$cid,
        "photo"=>$v["photo"],
        "caption"=>"🎬 <b>{$v['name']}</b>\n🎞 {$v['part']}\n📌 {$v['status']}",
        "parse_mode"=>"HTML",
        "reply_markup"=>json_encode([
          "inline_keyboard"=>[
            [["text"=>"▶️ Tomosha qilish","url"=>$v["link"]]]
          ]
        ])
      ]);
      $f=true;
    }
  }
  if(!$f) bot("sendMessage",["chat_id"=>$cid,"text"=>"😔 Topilmadi"]);
  exit;
}

/* ====== ADMIN PANEL ====== */
if($text=="/admin" && in_array($uid,$admin_ids)){
  bot("sendMessage",[
    "chat_id"=>$cid,
    "text"=>"👮 Admin panel",
    "reply_markup"=>json_encode([
      "keyboard"=>[
        [["➕ Anime qo‘shish"],["❌ Anime o‘chirish"]],
        [["📣 Reklama"],["📊 Statistika"]]
      ],
      "resize_keyboard"=>true
    ])
  ]);
  exit;
}

/* ====== ANIME QO‘SHISH ====== */
if($text=="➕ Anime qo‘shish" && in_array($uid,$admin_ids)){
  $steps[$cid]="add";
  file_put_contents("steps.json",json_encode($steps));
  bot("sendMessage",["chat_id"=>$cid,"text"=>"Kod|Nomi|Qism|Link|Holat"]);
  exit;
}

if(($steps[$cid]??"")=="add" && in_array($uid,$admin_ids)){
  $e=explode("|",$text);
  if(count($e)!=5) exit;
  $steps[$cid]="photo|".$text;
  file_put_contents("steps.json",json_encode($steps));
  bot("sendMessage",["chat_id"=>$cid,"text"=>"📸 Rasm yuboring"]);
  exit;
}

if(strpos(($steps[$cid]??""),"photo|")===0 && isset($m["photo"]) && in_array($uid,$admin_ids)){
  $d=explode("|",str_replace("photo|","",$steps[$cid]));
  $data[$d[0]]=[
    "name"=>$d[1],
    "part"=>$d[2],
    "link"=>$d[3],
    "status"=>$d[4],
    "photo"=>end($m["photo"])["file_id"]
  ];
  file_put_contents("data.json",json_encode($data));
  $steps[$cid]="";
  file_put_contents("steps.json",json_encode($steps));
  bot("sendMessage",["chat_id"=>$cid,"text"=>"✅ Qo‘shildi"]);
  exit;
}

/* ====== MAVZUDAN TASHQARI ====== */
bot("sendMessage",[
  "chat_id"=>$cid,
  "text"=>"⚠️ Bot faqat anime qidirish uchun"
]);