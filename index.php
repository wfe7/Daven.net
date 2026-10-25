<?php
/* ===== پێشنیار و داواکاری: لێرە هەڵدەگیرێن (ڕاگرتن لە وێب بەتەواوی داخراوە) ===== */
$FEEDBACK_FILE = __DIR__.'/suggestions_data.php';
/* ===== نۆتفیکەیشن بۆ دیسکۆرد: لینکی Webhook لێرە دابنێ (ئەگەر بەتاڵ بێت، کار ناکات) ===== */
$DISCORD_WEBHOOK = 'https://discord.com/api/webhooks/1563955252388962445/OOvf4TxoISXLxa3MDMlY49cDUG9aZOdSO_xw64L6dkmwBKPUsNJdAhwppV_bmwuSPzd2';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fb_name'])) {
  header('Content-Type: application/json; charset=utf-8');
  $clean = function ($s, $max) {
    $s = is_string($s) ? str_replace("\0", '', $s) : '';
    if (!preg_match('//u', $s)) return '';
    return preg_match('/^.{0,'.$max.'}/su', trim($s), $m) ? $m[0] : '';
  };
  $len  = function ($s) { return (int)preg_match_all('/./su', $s); };
  $notify = function ($name, $msg) use ($DISCORD_WEBHOOK) {
    if ($DISCORD_WEBHOOK === '') return false;
    $payload = json_encode([
      'username' => 'Daven Site',
      'content'  => 'Suggestion',
      'allowed_mentions' => ['parse' => []],
      'embeds' => [[
        'color' => 0xFFFFFF,
        'fields' => [['name' => 'ناوی بەکارهێنەر', 'value' => $name]],
        'description' => $msg,
        'timestamp' => gmdate('c'),
      ]],
    ], JSON_UNESCAPED_UNICODE);
    if (function_exists('curl_init')) {
      $ch = curl_init($DISCORD_WEBHOOK);
      curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 4]);
      @curl_exec($ch);
      $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      return $code >= 200 && $code < 300;
    }
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload, 'timeout' => 4]]);
    return @file_get_contents($DISCORD_WEBHOOK, false, $ctx) !== false;
  };
  $name = trim((string)preg_replace('/\s+/u', ' ', $clean($_POST['fb_name'], 40)));
  $msg  = $clean($_POST['fb_msg'] ?? '', 1000);
  $lock = sys_get_temp_dir().'/dvfb_'.md5($_SERVER['REMOTE_ADDR'] ?? '');
  $out  = ['ok' => false, 'err' => 'هەڵەیەک ڕوویدا، دواتر هەوڵبدەرەوە.'];
  if (!empty($_POST['fb_site'])) {
    $out = ['ok' => true];
  } elseif ($len($name) < 2) {
    $out['err'] = 'ناوی بەکارهێنەر بنووسە.';
  } elseif ($len($msg) < 5) {
    $out['err'] = 'پێشنیار یان داواکارییەکەت بنووسە.';
  } elseif (is_file($lock) && time() - filemtime($lock) < 45) {
    $out['err'] = 'کەمێک چاوەڕێبە، پاشان دووبارە بنێرە.';
  } else {
    $line = json_encode(['t' => date('Y-m-d H:i:s'), 'name' => $name, 'msg' => $msg], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)."\n";
    $head = is_file($FEEDBACK_FILE) ? '' : "<?php http_response_code(404); exit; ?>\n";
    $saved = @file_put_contents($FEEDBACK_FILE, $head.$line, FILE_APPEND | LOCK_EX) !== false;
    $sent  = $notify($name, $msg);
    if ($saved || $sent) {
      @touch($lock);
      $out = ['ok' => true];
    }
  }
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}
/* ===== وێنەی پڕۆفایل: ناوی فایلەکە لێرە بگۆڕە ===== */
$PROFILE_IMAGE = 'png.jpeg';
/* ================================================== */
$types = ['jpeg'=>'image/jpeg','jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'];
$dirRel = dirname($PROFILE_IMAGE); $dirRel = ($dirRel === '.') ? '' : rtrim($dirRel,'/').'/';
$dirAbs = __DIR__.'/'.$dirRel;
$files  = is_dir($dirAbs) ? scandir($dirAbs) : [];
$cands  = [basename($PROFILE_IMAGE)];
foreach ([pathinfo($PROFILE_IMAGE, PATHINFO_FILENAME), 'pfp'] as $n) foreach ($types as $e => $_) $cands[] = "$n.$e";
$found = '';
foreach ($cands as $c) { foreach ($files as $f) { if (strcasecmp($f,$c) === 0 && is_file($dirAbs.$f)) { $found = $f; break 2; } } }
$avatarSrc = ''; $avatarInline = false;
if ($found !== '') {
  $path = $dirAbs.$found;
  $ext  = strtolower(pathinfo($found, PATHINFO_EXTENSION));
  if (filesize($path) <= 300*1024 && ($raw = file_get_contents($path)) !== false) {
    $avatarSrc = 'data:'.($types[$ext] ?? 'image/jpeg').';base64,'.base64_encode($raw); $avatarInline = true;
  } else {
    $avatarSrc = $dirRel.rawurlencode($found).'?v='.(filemtime($path) ?: 1);
  }
}
?>
<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#000000">
<title>Daven | @wfe7</title>
<?php if($avatarSrc && !$avatarInline): ?><link rel="preload" as="image" href="<?= htmlspecialchars($avatarSrc,ENT_QUOTES) ?>" fetchpriority="high"><?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;800&display=swap" rel="stylesheet">
<style>
:root{--s1:#0b0b0b;--s2:#171717;--line:#2a2a2a;--text:#f5f5f5;--muted:#9a9a9a;color-scheme:dark;box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}
html{background:#000;scroll-padding-top:env(safe-area-inset-top,0px)}
*{box-sizing:border-box}
body{margin:0;color:var(--text);font-family:'Vazirmatn',Tahoma,sans-serif;line-height:1.9;background:radial-gradient(circle,rgba(255,255,255,.08) 1px,transparent 1.6px) 0 0/28px 28px;overflow-x:hidden}
.wrap{max-width:1040px;margin:0 auto;padding:0 22px}
/* background fx */
.aurora{position:fixed;inset:0;z-index:-1;overflow:hidden;background:#000}
.aurora i{position:absolute;width:60vmax;height:60vmax;border-radius:50%;will-change:transform;background:radial-gradient(circle,rgba(255,255,255,.15),transparent 62%);animation:drift 22s ease-in-out infinite alternate}
.aurora i:nth-child(1){top:-30vmax;right:-20vmax}
.aurora i:nth-child(2){bottom:-35vmax;left:-25vmax;animation-delay:-8s;background:radial-gradient(circle,rgba(150,150,150,.18),transparent 62%)}
.aurora i:nth-child(3){top:35%;left:25%;width:32vmax;height:32vmax;opacity:.55;animation-duration:30s}
.glow{position:fixed;z-index:-1;left:-220px;top:-220px;width:440px;height:440px;border-radius:50%;pointer-events:none;will-change:transform;background:radial-gradient(circle,rgba(255,255,255,.10),transparent 65%)}
.prog{position:fixed;top:0;left:0;right:0;height:3px;background:#fff;transform:scaleX(0);transform-origin:left;z-index:30}
#fx{position:fixed;inset:0;width:100%;height:100%;z-index:-1;pointer-events:none}
.aurora i{animation-duration:14s}
/* hero */
.hero{padding:44px 0 4px;display:grid;gap:22px}
.hid{display:flex;flex-direction:column;align-items:flex-start}
.top{display:flex;flex-direction:row-reverse;justify-content:space-between;align-items:center;width:100%;gap:12px}
.avatar{position:relative;width:124px;height:124px;border-radius:50%;flex:none;animation:pop .9s cubic-bezier(.2,1.4,.4,1) backwards}
.avatar::before{content:"";position:absolute;inset:-6px;border-radius:50%;background:conic-gradient(#fff,#333,#fff 40%,#333 70%,#fff);will-change:transform;animation:spin 6s linear infinite;-webkit-mask:radial-gradient(circle,transparent 62%,#000 63%);mask:radial-gradient(circle,transparent 62%,#000 63%)}
.av{position:absolute;inset:0;border-radius:50%;background:#fff;color:#000;display:grid;place-items:center;font-size:52px;font-weight:800;overflow:hidden;isolation:isolate;transform:translateZ(0)}
.av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;border-radius:50%}
.js .av img{opacity:0;transition:opacity .35s}.js .av img.ok{opacity:1}
.btn{font:inherit;font-weight:600;padding:9px 26px;border-radius:99px;background:#fff;color:#000;text-decoration:none;transition:transform .25s,box-shadow .25s;animation:up .7s .5s ease backwards}
.btn:hover{transform:translateY(-3px);box-shadow:0 10px 30px rgba(255,255,255,.25)}
.name{direction:ltr;width:100%;text-align:left;margin:16px 0 0;font-size:32px;line-height:1.3;font-weight:800;letter-spacing:0;color:#fff}
.name span{display:inline-block;animation:letter .8s cubic-bezier(.2,.8,.2,1) backwards}
.user{direction:ltr;width:100%;text-align:left;color:var(--muted);font-size:15px;animation:up .7s .9s ease backwards}
.role{direction:ltr;align-self:flex-end;display:flex;align-items:center;gap:12px;margin-top:14px;padding:5px 22px 5px 16px;border:1px solid rgba(255,255,255,.16);background:linear-gradient(180deg,rgba(255,255,255,.07),rgba(255,255,255,.02));border-radius:99px;font-weight:600;font-size:16px;letter-spacing:.6px;min-height:42px;animation:up .8s 1s ease backwards}
.role::before{content:"";flex:none;width:8px;height:8px;border-radius:50%;background:#fff;animation:pulse 3.2s ease-out infinite}
#tw{display:inline-flex;align-items:center;gap:3px}
#type{display:inline-block;text-align:left;white-space:nowrap;background:linear-gradient(90deg,#fff 35%,#8d8d8d 50%,#fff 65%);background-size:200% 100%;-webkit-background-clip:text;background-clip:text;color:transparent;animation:shine 5s linear infinite}
.role u{flex:none;width:2px;height:20px;border-radius:2px;background:#fff;animation:caret 1.3s ease-in-out infinite}
.bio{margin:0;font-size:17px;animation:up .7s 1.1s ease backwards}
.chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.chips span{border:1px solid var(--line);border-radius:10px;padding:0 14px;font-size:13px;color:var(--muted);transition:.25s}
.chips span:hover{background:#fff;color:#000}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:18px}
.stat{background:linear-gradient(180deg,var(--s2),var(--s1));border:1px solid var(--line);border-radius:18px;padding:12px 6px;text-align:center;animation:up .7s backwards}
.stat:nth-child(1){animation-delay:1.2s}.stat:nth-child(2){animation-delay:1.3s}.stat:nth-child(3){animation-delay:1.4s}
.stat b{display:block;font-size:28px;line-height:1.4}.stat span{font-size:12px;color:var(--muted)}
.hbio{animation:up .7s 1.1s ease backwards}
/* nav */
nav{position:fixed;z-index:20;bottom:calc(10px + env(safe-area-inset-bottom,0px));left:12px;right:12px;display:flex;padding:5px;border:1px solid var(--line);border-radius:99px;background:rgba(10,10,10,.94)}
nav a{position:relative;z-index:1;flex:1;text-align:center;padding:9px 2px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none;transition:color .3s}
nav a.on{color:#000}
.ind{position:absolute;top:5px;bottom:5px;border-radius:99px;background:#fff;transition:left .45s cubic-bezier(.2,.9,.3,1),width .45s cubic-bezier(.2,.9,.3,1)}
.fbi{position:relative;z-index:1;flex:none;width:54px;height:54px;align-self:center;margin:-7px 4px;border-radius:50%;border:1px solid #444;padding:0;background:#161616;color:#fff;cursor:pointer;display:grid;place-items:center;transition:transform .25s,background .3s,color .3s}
.fbi svg{width:26px;height:26px}
.fbi:hover,.fbi:focus-visible{background:#fff;color:#000;transform:scale(1.08);outline:none}
/* pages */
.page{padding:56px 0 20px;scroll-margin-top:24px}
.page:last-of-type{padding-bottom:130px}
.js .page>*{opacity:0;transform:translateY(34px);transition:opacity .7s cubic-bezier(.2,.8,.2,1),transform .7s cubic-bezier(.2,.8,.2,1);transition-delay:calc(var(--i,0)*.08s)}
.js .page>*.in{opacity:1;transform:none}
.js .page>*.done{transition:transform .25s,border-color .3s;transition-delay:0s}
.eyebrow{direction:ltr;text-align:right;font-family:monospace;font-size:13px;color:var(--muted);letter-spacing:3px}
h2{font-size:30px;margin:0 0 18px;line-height:1.5}
.card{background:linear-gradient(180deg,var(--s2),var(--s1));border:1px solid var(--line);border-radius:22px;padding:20px 22px;margin-bottom:14px;position:relative;overflow:hidden;transition:transform .25s,border-color .3s}
.card:hover{border-color:#555}
.card::after{content:"";position:absolute;inset:0;pointer-events:none;opacity:0;transition:opacity .3s;background:radial-gradient(260px circle at var(--x,50%) var(--y,0),rgba(255,255,255,.10),transparent 70%)}
.card:hover::after{opacity:1}
.card p{margin:0 0 10px}.card p:last-child{margin:0}
.big{position:relative;border-radius:28px;padding:1.5px;margin-bottom:14px}
.big::before{content:"";position:absolute;inset:0;border-radius:28px;background:conic-gradient(from var(--a),#fff,#222 25%,#fff 50%,#222 75%,#fff);animation:rot 7s linear infinite}
.big-in{position:relative;border-radius:27px;padding:28px 24px;background:radial-gradient(90% 100% at 100% 0,#242424,#070707)}
.tag{display:inline-block;border:1px solid #444;border-radius:99px;padding:0 14px;font-size:13px;margin-bottom:12px;color:#ddd}
.big h3{font-size:clamp(26px,5vw,38px);line-height:1.6;margin:0 0 10px;font-weight:800;background:linear-gradient(90deg,#fff 30%,#777 50%,#fff 70%);background-size:200% 100%;-webkit-background-clip:text;background-clip:text;color:transparent;animation:shine 5s linear infinite}
.big p{color:#c8c8c8;margin:0}
.flow{display:grid;gap:12px}
.sub{font-size:21px;font-weight:800;line-height:1.6;margin:16px 0 12px}
.step{display:flex;gap:18px;align-items:center}
.step .n{font-family:monospace;font-size:46px;font-weight:800;line-height:1;direction:ltr;min-width:62px;color:transparent;-webkit-text-stroke:1.5px #888}
.step h3{margin:0;font-size:18px}.step p{color:var(--muted);font-size:14px}
.quote{text-align:center;font-size:20px;font-weight:600;padding:30px 20px}
.quote small{display:block;direction:ltr;color:var(--muted);font-size:14px;font-weight:400}
.code{direction:ltr;text-align:left;font-family:ui-monospace,Menlo,monospace;font-size:13px;line-height:1.85;color:#ddd;white-space:pre-wrap}
.code em{font-style:normal;color:#8c8c8c}
.tl{border-right:2px solid var(--line);padding-right:24px;margin:6px 8px 0 0}
.item{position:relative;margin-bottom:24px}
.item::before{content:"";position:absolute;right:-31px;top:9px;width:12px;height:12px;border-radius:50%;background:#fff;box-shadow:0 0 0 4px #000,0 0 0 5px #555;animation:pulse 2.6s infinite}
.item .y{color:var(--muted);font-size:13px}.item h3{margin:0;font-size:18px}.item p{margin:2px 0 0;color:var(--muted)}
.honest b{display:block;direction:ltr;text-align:left;font-size:clamp(36px,8vw,56px);line-height:1.3;background:linear-gradient(90deg,#fff 30%,#777 50%,#fff 70%);background-size:200% 100%;-webkit-background-clip:text;background-clip:text;color:transparent;animation:shine 5s linear infinite}
.honest span{color:var(--muted)}
.vers{display:flex;gap:8px;direction:ltr;margin-top:8px}.vers i{font-style:normal;font-family:monospace;border:1px solid #555;border-radius:8px;padding:0 12px;font-size:13px}
.list{direction:ltr}
.list a{direction:ltr;display:flex;justify-content:space-between;align-items:center;padding:18px 20px;margin-bottom:10px;border:1px solid var(--line);border-radius:18px;background:var(--s1);color:var(--text);text-decoration:none;font-weight:600;transition:transform .3s,background .3s,color .3s}
.list a:hover{background:#fff;color:#000;transform:translateX(8px)}
.foot{text-align:center;color:#666;font-size:13px;margin-top:28px}
/* feedback */
.fbwrap{display:flex;justify-content:center;margin:20px 0 0}
.fbtn{border:0;cursor:pointer;font-size:16px;padding:13px 34px;animation:none}
dialog#fb{padding:0;border:1px solid var(--line);border-radius:24px;background:linear-gradient(180deg,var(--s2),var(--s1));color:var(--text);width:min(92vw,460px);max-height:92vh;max-height:92dvh;overflow:auto;box-shadow:0 30px 80px rgba(0,0,0,.6)}
dialog#fb::backdrop{background:rgba(0,0,0,.72);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}
dialog#fb[open]{animation:fbin .3s cubic-bezier(.2,.9,.3,1)}
@keyframes fbin{from{opacity:0;transform:translateY(18px) scale(.97)}}
.fbin{position:relative;overflow:hidden;padding:24px 24px 22px}
.fbin h3{margin:0 0 6px;font-size:22px}
.fbin label{display:block;margin:16px 0 6px;font-size:14px;color:var(--muted)}
.fbin input,.fbin textarea{width:100%;font:inherit;font-size:16px;color:var(--text);background:#0b0b0b;border:1px solid var(--line);border-radius:14px;padding:12px 14px;transition:border-color .25s}
.fbin textarea{min-height:130px;resize:vertical}
.fbin input:focus,.fbin textarea:focus{outline:none;border-color:#fff}
.fbin .hp{position:absolute;left:-9999px;width:1px;height:1px;opacity:0}
.fbs{min-height:26px;margin-top:12px;font-size:14px;color:var(--muted)}.fbs.err{color:#ff9b9b}
.fbrow{display:flex;gap:10px;margin-top:6px}
.fbrow .btn{border:0;cursor:pointer;font-size:16px;flex:1;animation:none}
.fbrow .btn:disabled{opacity:.6;cursor:default;transform:none;box-shadow:none}
.ghost{font:inherit;font-weight:600;cursor:pointer;background:transparent;color:var(--text);border:1px solid var(--line);border-radius:99px;padding:9px 22px}
.ghost:hover{border-color:#777}
.paused,.paused::before,.paused *{animation-play-state:paused!important}
.moving .aurora i,.moving .big::before,.moving .avatar::before,.moving .role::before,.moving .item::before{animation-play-state:paused!important}
/* keyframes */
@property --a{syntax:'<angle>';initial-value:0deg;inherits:false}
@keyframes rot{to{--a:360deg}}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes drift{0%{transform:translate(0,0) scale(1)}50%{transform:translate(-10vmax,8vmax) scale(1.25)}100%{transform:translate(8vmax,-6vmax) scale(1.1)}}
@keyframes shine{to{background-position:-200% 0}}
@keyframes up{from{opacity:0;transform:translateY(22px)}}
@keyframes rise{from{opacity:0;transform:translateY(34px) scale(.97);filter:blur(10px)}}
@keyframes pop{from{opacity:0;transform:scale(.4) rotate(-40deg)}}
@keyframes letter{from{opacity:0;transform:translateY(24px);filter:blur(8px)}}
@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(255,255,255,.6)}100%{box-shadow:0 0 0 9px rgba(255,255,255,0)}}
@keyframes caret{0%,100%{opacity:1}50%{opacity:.08}}
@media (hover:none){.glow{display:none}}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}}
/* desktop */
@media (min-width:900px){
.wrap{max-width:1120px;padding:0 36px}
nav{bottom:auto;top:calc(18px + env(safe-area-inset-top,0px));left:50%;right:auto;transform:translateX(-50%);width:540px}
nav a{font-size:15px;padding:10px 4px}
/* hero = profile card + bio */
.hero{grid-template-columns:1fr 1.1fr;align-items:center;padding:120px 0 24px;gap:56px}
.hid{grid-column:2;position:relative;overflow:hidden;padding:34px 32px 32px;border:1px solid var(--line);border-radius:30px;background:linear-gradient(180deg,#1a1a1a,#0a0a0a);box-shadow:0 30px 80px rgba(0,0,0,.5)}
.avatar{width:128px;height:128px}
.btn{padding:11px 30px}
.name{margin-top:20px}
.hbio{grid-column:1;grid-row:1}
.bio{font-size:20px;line-height:2.1}
.chips span{font-size:14px;padding:2px 16px}
.stats{gap:14px;margin-top:26px}
.stat{padding:18px 8px;border-radius:22px}
.stat b{font-size:38px}.stat span{font-size:13px}
/* sections */
.page{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-content:start;padding:80px 0 10px;scroll-margin-top:10px}
#p-skills{padding-top:40px;scroll-margin-top:50px}
.page:last-of-type{padding-bottom:90px}
.page>.eyebrow,.page>h2,.page>.big,.page>.flow,.page>.sub,.page>.fbwrap,.page>.quote,.page>.list,.page>.foot{grid-column:1/-1}
.page>.eyebrow{margin-bottom:-6px}
h2{font-size:42px;margin:0 0 10px}
.card,.big{margin-bottom:0}
.card{padding:26px 30px}
.card p{font-size:17px}
.big-in{padding:48px 52px}
.big p{font-size:18px;line-height:2.1}
.flow{grid-template-columns:repeat(3,1fr);gap:16px}
.flow.f4{grid-template-columns:repeat(4,1fr)}
#p-skills .honest{grid-column:1;grid-row:3}
#p-skills .desc{grid-column:2;grid-row:3;display:flex;flex-direction:column;justify-content:center}
#p-skills .desc p{font-size:19px;line-height:2.1}
#p-skills .code{grid-column:1/-1;grid-row:4}
.sub{font-size:28px;margin:14px 0 -2px}
.step{flex-direction:column;align-items:flex-start;gap:8px;padding:28px}
.quote{font-size:24px;padding:48px 30px}
#p-path{grid-template-columns:1.25fr 1fr}
#p-path>.card{display:flex;flex-direction:column;justify-content:center}
#p-path>.card p{font-size:21px;line-height:2.15;margin:0 0 14px}
#p-path>.card p:last-child{margin:0}
.tl{margin:0;padding-top:4px;padding-right:32px}
.item::before{right:-39px}
.item h3{font-size:20px}
.honest{display:flex;flex-direction:column;justify-content:center}
.list{display:grid;grid-template-columns:repeat(2,minmax(0,260px));justify-content:start;gap:16px}
.list a{flex-direction:column;align-items:flex-start;gap:10px;margin:0;padding:30px 26px;font-size:20px}
.list a:hover{transform:translateY(-6px)}
}
</style>
</head>
<body>
<script>document.documentElement.classList.add("js")</script>
<div class="aurora"><i></i><i></i><i></i></div><canvas id="fx"></canvas><div class="glow" id="glow"></div><div class="prog" id="prog"></div>
<div class="wrap">
  <header class="hero">
    <div class="hid">
      <div class="top"><div class="avatar"><div class="av" id="avatar"><span id="ini">D</span><?php if($avatarSrc): ?><img id="pfp" src="<?= htmlspecialchars($avatarSrc,ENT_QUOTES) ?>" alt="Daven" width="124" height="124" decoding="async" fetchpriority="high" draggable="false"><?php endif; ?></div></div><a class="btn" href="#path">چیرۆکەکەم</a></div>
      <h1 class="name" id="name" aria-label="Daven">Daven</h1>
      <div class="user">@wfe7</div>
      <div class="role"><span class="tw" id="tw"><span id="type"></span><u></u></span></div>
    </div>
    <div class="hbio">
      <p class="bio">دیزاینەر و گەشەپێدەر.</p>
      <div class="chips"><span>کوردستان</span><span>JavaScript</span><span>Discord</span></div>
      <div class="stats"><div class="stat"><b data-n="6">٦</b><span>ساڵ ئەزموون</span></div><div class="stat"><b data-n="3">٣</b><span>قۆناغ</span></div><div class="stat"><b>v13</b><span>JavaScript</span></div></div>
    </div>
  </header>

  <nav id="nav"><b class="ind" id="ind"></b><a href="#skills">شارەزایی</a><a href="#system">پێشەکی</a><button class="fbi fbopen" id="fbOpen" type="button" aria-haspopup="dialog" aria-label="پێشنیار یان داواکاری"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7v6M9 10h6"/></svg></button><a href="#path">ڕێچکە</a><a href="#contact">پەیوەندی</a></nav>

  <main>
  <section class="page" id="p-skills">
    <div class="eyebrow">01 / SKILLS</div>
    <h2>شارەزایی و ئەزموون</h2>
    <div class="card honest"><b>JavaScript</b><span>سەرنجی سەرەکی بۆ ژینگەی دیسکۆرد</span><div class="vers"><i>v14</i><i>v13</i></div></div>
    <div class="card code">const <b>daven</b> = {
  username: <em>"wfe7"</em>,
  role: <em>"Creative Developer"</em>,
  language: <em>"JavaScript (Discord)"</em>,
  motto: <em>"If it doesn't exist, build it."</em>,
  social: <em>"left. this site is home."</em>
};</div>
    <div class="card desc">
      <p>شارەزایم هەیە لە پڕۆژەبەڕێوەبردن، بەستنەوەی ئایدیاکان، دروستکردنی چیرۆک و جیهانێکی نوێ لە ڕێگای پڕۆجێکتەوە، شانبەشانی گەشەپێدان و چارەسەرکردنی کێشەکانی کۆد.</p>
    </div>
    <h3 class="sub">ئێمە لە چیدا شارەزاین؟</h3>
    <div class="flow f4">
      <div class="card step"><div class="n">01</div><div><h3>ئەپلکەیشنی دیسکۆرد</h3></div></div>
      <div class="card step"><div class="n">02</div><div><h3>سیستەمی پارێزەر</h3></div></div>
      <div class="card step"><div class="n">03</div><div><h3>سیستەمی خزمەتگوزاری</h3></div></div>
      <div class="card step"><div class="n">04</div><div><h3>دیزاین</h3></div></div>
    </div>
  </section>

  <section class="page" id="p-system">
    <div class="eyebrow">02 / INTRO</div>
    <h2>پێشەکی و ناساندن</h2>
    <div class="big"><div class="big-in">
      <span class="tag">ماڵی نوێ</span>
      <h3>هەندێک شت لە پێوانە بەرزترن و لە سنووری مامەڵە دەردەچن.</h3>
      <p>ساڵانێک لە قووڵایی جیهانی دیجیتالدا ژیام؛ تێیدا فێربووم، گۆڕام و ڕێگەی تایبەتی خۆم هەڵبژارد. هەرچییەکم لەو ڕێگایەدا دروستکرد، ڕەنگدانەوەی بیر و مۆری دەستی خۆمە. ماندووبوونی ڕاستەقینە بە دەست هەرزانی نادرێت، چونکە بەهای لە هەر پێوانەیەک گەورەترە. ئێستا بە ئیرادەی خۆم لە ژاوەژاو دوور دەکەومەوە؛ ئەم ماڵپەڕە کەنارێکی هێمن و ماڵی نوێی منە.</p>
    </div></div>
    <h3 class="sub">ئێمە لە چیدا جیاوازین؟</h3>
    <div class="flow">
      <div class="card step"><div class="n">01</div><div><h3>ڕەسەنایەتی</h3><p>هەرچییەک دروستی دەکەم، وێنەی ڕەسەنی بیری خۆمە.</p></div></div>
      <div class="card step"><div class="n">02</div><div><h3>پاراستن</h3><p>ماندووبوونی ڕاستەقینە بەهایەکی نەگۆڕی هەیە و پارێزراوە.</p></div></div>
      <div class="card step"><div class="n">03</div><div><h3>سەربەخۆیی</h3><p>ڕێگای خۆم هەڵبژارد؛ بە ئارامی و بە شێوازی خودم پێیدا دەڕۆم.</p></div></div>
    </div>
    <div class="card quote">ئەو شتەی لە شوێنی خۆیدا نییە، لە قاڵبی کاری مندا زیندوو دەبێتەوە.<small>Daven</small></div>
  </section>

  <section class="page" id="p-path">
    <div class="eyebrow">03 / FROM THE BEGINNING</div>
    <h2>لە سەرەتاوە..</h2>
    <div class="card">
  <p>حەوت ساڵ لەمەوبەر، هەموو شتێک لە فەزای دیسکۆرددا دەستی پێکرد. لەوێ هەستم بە نامۆیی دەکرد و لەگەڵ ژینگەکە نەگونجام، بۆیە بڕیارم دا ڕێگەی خۆم بە تەنیا هەڵبژێرم. سەرەتا هیچم نەبوو و لە جیهانی کۆد و زمانە نامۆکاندا دەستم بەتاڵ بوو، بەڵام ئاگرێک لەناو دڵمدا بوو. بە تاقیکردنەوە، شکان و هەستانەوەی بەردەوام فێر بووم بەربەستەکان بکەمە پەیژەی سەرکەوتن.</p>
  <p>ئەو ساڵانە تەنها کات نەبوون، بەڵکو گیانیان بە من بەخشی. ئێستا بە ئیرادەی خۆم دەرگای سۆشیال میدیا دادەخەم و ماڵئاوایی لە ژاوەژاوە بێکارەکەی دەکەم. تەنها لەم ماڵپەڕەدا، لەسەر کار و ماندووبوونی دەستی خۆم، بەردەوام دەبم.</p>
</div>
    <div class="tl">
      <div class="item"><div class="y">قۆناغی سێیەم (ئێستا)</div><h3>جێهێشتنی سۆشیالمیدیا</h3><p>تەرخانکردنی ئەم ماڵپەڕە بۆ کارەکانم.</p></div>
      <div class="item"><div class="y">قۆناغی دووەم</div><h3>تێکەڵکردنی هونەری دیزاین و لۆژیکی کۆد</h3><p>لە یەک سیستەمی یەکگرتوودا.</p></div>
      <div class="item"><div class="y">قۆناغی یەکەم</div><h3>فێربوونی قووڵی کۆدنووسین</h3><p>داهێنانی بناغەی پڕۆژەکان.</p></div>
      <div class="item"><div class="y">سەرەتاکان</div><h3>یەکەمین هەنگاوەکان</h3><p>لە دیزاین و تێگەیشتن لە ژینگەی دیسکۆرد.</p></div>
    </div>
  </section>

  <section class="page" id="p-contact">
    <div class="eyebrow">04 / CONTACT</div>
    <h2>پەیوەندی</h2>
    <div class="list">
      <a href="mailto:its.davennn@gmail.com">Email</a>
      <a href="https://discord.com/users/537005595866300448" target="_blank" rel="noopener noreferrer">Discord</a>
    </div>
    <div class="fbwrap"><button class="btn fbtn fbopen" type="button" aria-haspopup="dialog">پێشنیار یان داواکاری</button></div>
    <div class="foot">© 2026 Daven</div>
  </section>
  </main>
</div>
<dialog id="fb" aria-labelledby="fbT">
  <form class="fbin" id="fbForm" method="post" novalidate>
    <h3 id="fbT">پێشنیار یان داواکاری</h3>
    <label for="fbName">ناوی بەکارهێنەر:</label>
    <input id="fbName" name="fb_name" type="text" dir="auto" maxlength="40" autocomplete="username" required>
    <label for="fbMsg">پێشنیار یان داواکاری:</label>
    <textarea id="fbMsg" name="fb_msg" dir="auto" rows="5" maxlength="1000" required></textarea>
    <input class="hp" type="text" name="fb_site" tabindex="-1" autocomplete="off" aria-hidden="true">
    <div class="fbs" id="fbStatus" role="status" aria-live="polite"></div>
    <div class="fbrow"><button type="submit" class="btn">ناردن</button><button type="button" class="ghost" id="fbClose">داخستن</button></div>
  </form>
</dialog>
<script>
var tabs=['skills','system','path','contact'];
function $(i){return document.getElementById(i)}
function place(){var a=document.querySelector('#nav a.on'),n=$('ind');if(a){n.style.left=a.offsetLeft+'px';n.style.width=a.offsetWidth+'px'}}
var cur0='',navLock=false,anim=0;
function setOn(h){if(h===cur0)return;cur0=h;document.querySelectorAll('#nav a').forEach(function(a){a.classList.toggle('on',a.getAttribute('href')==='#'+h)});place()}
function spy(){var cur=tabs[0],y=innerHeight*.4;
  tabs.forEach(function(t){if($('p-'+t).getBoundingClientRect().top<=y)cur=t});
  if(innerHeight+scrollY>=document.documentElement.scrollHeight-4)cur=tabs[tabs.length-1];
  setOn(cur)}
addEventListener('resize',place);addEventListener('load',function(){place();spy()});
document.querySelectorAll('.page').forEach(function(p){[].forEach.call(p.children,function(c,i){c.style.setProperty('--i',Math.min(i,4))})});
/* reveal on scroll */
var io=new IntersectionObserver(function(es){if(navLock)return;es.forEach(function(e){if(e.isIntersecting){var t=e.target;t.classList.add('in');setTimeout(function(){t.classList.add('done')},1300);io.unobserve(t)}})},{threshold:.08});
document.querySelectorAll('.page>*').forEach(function(c){io.observe(c)});
/* smooth nav scroll */
function ease(t){return t<.5?4*t*t*t:1-Math.pow(-2*t+2,3)/2}
function endNav(){navLock=false;document.documentElement.classList.remove('moving');
  document.querySelectorAll('.page>*:not(.in)').forEach(function(c){io.unobserve(c);io.observe(c)});spy()}
function stopNav(){if(navLock){cancelAnimationFrame(anim);endNav()}}
function goTo(id){stopNav();
  var el=$('p-'+id),sm=parseFloat(getComputedStyle(el).scrollMarginTop)||0,
  max=document.documentElement.scrollHeight-innerHeight,
  to=Math.max(0,Math.min(max,el.getBoundingClientRect().top+scrollY-sm)),from=scrollY,dist=to-from;
  setOn(id);if(Math.abs(dist)<2)return;
  var dur=Math.min(950,Math.max(500,Math.abs(dist)*.4)),t0=null;
  navLock=true;document.documentElement.classList.add('moving');
  function step(ts){t0=t0||ts;var p=Math.min((ts-t0)/dur,1);scrollTo(0,from+dist*ease(p));
    if(p<1)anim=requestAnimationFrame(step);else endNav()}
  anim=requestAnimationFrame(step)}
['wheel','touchstart','keydown','mousedown'].forEach(function(n){addEventListener(n,stopNav,{passive:true})});
document.querySelectorAll('a[href^="#"]').forEach(function(a){a.addEventListener('click',function(e){
  var id=a.getAttribute('href').slice(1);if(tabs.indexOf(id)<0)return;
  e.preventDefault();goTo(id)})});
spy();
/* animated background */
(function(){var cv=$('fx'),x=cv.getContext('2d');if(matchMedia('(prefers-reduced-motion:reduce)').matches)return;
var W,H,P=[],m={x:-999,y:-999},dpr=1,D=120,D2=D*D;
function size(){W=innerWidth;H=innerHeight;cv.width=W*dpr;cv.height=H*dpr;
  var n=Math.round(Math.min(50,W*H/26000));P=[];for(var i=0;i<n;i++)P.push({x:Math.random()*W,y:Math.random()*H,vx:(Math.random()-.5)*.45,vy:(Math.random()-.5)*.45,r:Math.random()*1.4+.6})}
size();var rt;addEventListener('resize',function(){clearTimeout(rt);rt=setTimeout(function(){if(innerWidth!==W||Math.abs(innerHeight-H)>150)size()},200)});
addEventListener('pointermove',function(e){m.x=e.clientX;m.y=e.clientY},{passive:true});
function f(){requestAnimationFrame(f);if(document.hidden||navLock)return;
  x.clearRect(0,0,W,H);x.fillStyle='rgba(255,255,255,.55)';x.beginPath();
  for(var i=0;i<P.length;i++){var p=P[i];p.x+=p.vx;p.y+=p.vy;
    if(p.x<0||p.x>W)p.vx*=-1;if(p.y<0||p.y>H)p.vy*=-1;
    var dx=p.x-m.x,dy=p.y-m.y,d2=dx*dx+dy*dy;if(d2<16900&&d2>1){var d=Math.sqrt(d2);p.x+=dx/d*1.2;p.y+=dy/d*1.2}
    x.moveTo(p.x+p.r,p.y);x.arc(p.x,p.y,p.r,0,6.283)}
  x.fill();
  x.lineWidth=1;
  for(var k=0;k<2;k++){x.strokeStyle=k?'rgba(255,255,255,.07)':'rgba(255,255,255,.14)';x.beginPath();
    var lo=k?D2*.5:0,hi=k?D2:D2*.5;
    for(var i=0;i<P.length;i++)for(var j=i+1;j<P.length;j++){var ddx=P[i].x-P[j].x,ddy=P[i].y-P[j].y,l=ddx*ddx+ddy*ddy;
      if(l>=lo&&l<hi){x.moveTo(P[i].x,P[i].y);x.lineTo(P[j].x,P[j].y)}}
    x.stroke()}}
f()})();
/* name letters */
$('name').innerHTML=[].map.call('Daven',function(c,i){return '<span style="animation-delay:'+(.3+i*.09)+'s">'+c+'</span>'}).join('');
/* role typewriter */
(function(){var r=['Creative Developer','Discord Developer','Designer'],i=0,j=0,d=0,t=$('type');
var tw=$('tw');function fit(){tw.style.minWidth='';var o=t.textContent,w=0;r.forEach(function(x){t.textContent=x;w=Math.max(w,tw.offsetWidth)});t.textContent=o;tw.style.minWidth=w+'px'}
fit();if(document.fonts&&document.fonts.ready)document.fonts.ready.then(fit);
function k(){var s=r[i];t.textContent=s.slice(0,j);
if(!d&&j<s.length){j++;setTimeout(k,120+Math.random()*70)}
else if(!d){d=1;setTimeout(k,2800)}
else if(j>0){j--;setTimeout(k,55)}
else{d=0;i=(i+1)%r.length;setTimeout(k,650)}}
setTimeout(k,1500)})();
/* count up */
document.querySelectorAll('[data-n]').forEach(function(e){var n=+e.dataset.n,s=null;
function f(ts){s=s||ts;var p=Math.min((ts-s)/1500,1);e.textContent=Math.round(n*p).toLocaleString('ar-EG');if(p<1)requestAnimationFrame(f)}setTimeout(function(){requestAnimationFrame(f)},1200)});
/* avatar: نیشاندان + هەوڵدانەوە ئەگەر تۆڕ کێشەی بوو */
(function(){var im=$('pfp'),ini=$('ini'),n=0;if(!im)return;
  var base=im.src;
  function ok(){ini.style.display='none';im.classList.add('ok')}
  function bad(){if(n<3&&base.indexOf('data:')!==0){n++;setTimeout(function(){im.src=base+(base.indexOf('?')<0?'?':'&')+'r='+Date.now()},700*n)}else{im.style.display='none'}}
  im.addEventListener('load',ok);im.addEventListener('error',bad);
  if(im.complete){im.naturalWidth?ok():bad()}})();
/* پێشنیار و داواکاری */
(function(){var dlg=$('fb'),form=$('fbForm'),st=$('fbStatus'),ops=document.querySelectorAll('.fbopen'),sb=form&&form.querySelector('[type=submit]');if(!dlg||!ops.length)return;
  function show(){st.textContent='';st.className='fbs';dlg.showModal?dlg.showModal():dlg.setAttribute('open','');document.documentElement.style.overflow='hidden';setTimeout(function(){$('fbName').focus()},60)}
  function hide(){dlg.close?dlg.close():dlg.removeAttribute('open');document.documentElement.style.overflow=''}
  function say(t,e){st.textContent=t;st.className='fbs'+(e?' err':'')}
  [].forEach.call(ops,function(b){b.addEventListener('click',show)});$('fbClose').addEventListener('click',hide);
  dlg.addEventListener('close',function(){document.documentElement.style.overflow=''});
  dlg.addEventListener('click',function(e){if(e.target===dlg)hide()});
  form.addEventListener('submit',function(e){e.preventDefault();
    var n=form.elements.fb_name.value.trim(),m=form.elements.fb_msg.value.trim();
    if(n.length<2){say('ناوی بەکارهێنەر بنووسە.',1);form.elements.fb_name.focus();return}
    if(m.length<5){say('پێشنیار یان داواکارییەکەت بنووسە.',1);form.elements.fb_msg.focus();return}
    sb.disabled=true;say('دەنێردرێت...');
    fetch(location.pathname+location.search,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json'}})
      .then(function(r){return r.json()})
      .then(function(d){if(d.ok){say('سوپاس، نێردرا ✓');form.reset();setTimeout(hide,1500)}else say(d.err||'هەڵەیەک ڕوویدا.',1)})
      .catch(function(){say('پەیوەندی نەبوو، دواتر هەوڵبدەرەوە.',1)})
      .then(function(){sb.disabled=false})})})();
/* progress + glow + tilt */
var tk=0,last='';
function frame(){tk=0;var h=document.documentElement;$('prog').style.transform='scaleX('+(scrollY/Math.max(h.scrollHeight-innerHeight,1))+')';if(!navLock)spy()}
addEventListener('scroll',function(){if(!tk)tk=requestAnimationFrame(frame)},{passive:true});
var gp=0,gx=0,gy=0;
addEventListener('pointermove',function(e){gx=e.clientX;gy=e.clientY;if(!gp)gp=requestAnimationFrame(function(){gp=0;$('glow').style.transform='translate3d('+gx+'px,'+gy+'px,0)'})},{passive:true});
/* pause heavy animations when offscreen */
var po=new IntersectionObserver(function(es){es.forEach(function(e){e.target.classList.toggle('paused',!e.isIntersecting)})});
document.querySelectorAll('.hid,.big,.honest').forEach(function(e){po.observe(e)});
document.querySelectorAll('.card').forEach(function(c){
  c.addEventListener('pointermove',function(e){var r=c.getBoundingClientRect(),x=e.clientX-r.left,y=e.clientY-r.top;c.style.setProperty('--x',x+'px');c.style.setProperty('--y',y+'px');
    if(e.pointerType==='mouse')c.style.transform='perspective(800px) rotateX('+((y/r.height-.5)*-6)+'deg) rotateY('+((x/r.width-.5)*6)+'deg)'});
  c.addEventListener('pointerleave',function(){c.style.transform=''})});
</script>
</body>
</html>
