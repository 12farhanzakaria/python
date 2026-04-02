<?php
error_reporting(0);
require_once 'cloudbeta-db-config.php';

$scheme = 'http';
if (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    ($_SERVER['SERVER_PORT'] == 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
) {
    $scheme = 'https';
}

$host = $_SERVER['HTTP_HOST'];
$id = $_GET['movie'] ?? '';
$debug = isset($_GET['debug']); // aktifkan dengan ?debug=1

// ambil data
$sources = getPlayerSources($id);

if (!$sources && file_exists("cache/$id.json")) {
    $sources = json_decode(file_get_contents("cache/$id.json"), true);
}

// ambil google drive
$google = $sources['google'] ?? '';

// ambil drive id
preg_match('/(?:\/d\/|id=)([a-zA-Z0-9_-]+)/', $google, $m);
$driveid = $m[1] ?? '';

// curl GET
function curlGet($url){
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// ambil token
$html = curlGet("https://gdriveplayer.to/embed2.php?link=https://drive.google.com/uc?id=".$driveid);
preg_match('/id="token".*?>(.*?)</', $html, $match);
$token = $match[1] ?? '';

// encode token
$t = base64_encode($token);
$proxyUrl = $scheme . '://' . $host . '/proxy.php?t=' . $t;

// cek status
function curlStatus($url){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 10
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $status;
}

$status = curlStatus($proxyUrl);

// debug (hanya muncul jika ?debug=1)
if ($debug) {
    echo "<!-- 
STATUS: $status
PROXY: $proxyUrl
TOKEN: $token
ENCODED: $t
DRIVEID: $driveid
GOOGLE: $google
-->";
}

// kalau gagal redirect
if (!in_array($status, [200, 206])) {
    header("Location: https://juragan.info/stream/getbk.php?movie=$id");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<style>
body{margin:0;background:#000}
video{width:100%;height:100vh}
</style>
</head>
<body>

<video id="video" controls autoplay></video>

<script>
var video = document.getElementById('video');

// kirim token
var url = "proxy.php?t=<?= $t ?>";

if (Hls.isSupported()) {
    var hls = new Hls();
    hls.loadSource(url);
    hls.attachMedia(video);
} else {
    video.src = url;
}
</script>

</body>
</html>