<?php
error_reporting(0);

// decode token
$token = base64_decode($_GET['t'] ?? '');
if (!$token) {
    http_response_code(404);
    exit('404 Not Found');
}

// ambil m3u8
$api = "https://horse.harlequin.workers.dev/?id=".$token;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_REFERER => 'https://gdriveplayer.to/',
    CURLOPT_HTTPHEADER => [
        "accept: */*",
        "accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7"
    ]
]);

$m3u8 = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 🔥 VALIDASI WAJIB
if (
    !$m3u8 ||                 // kosong
    $http !== 200 ||         // bukan sukses
    strpos($m3u8, '#EXTM3U') === false // bukan playlist valid
) {
    http_response_code(404);
    exit('404 Not Found');
}

// proses m3u8
$lines = explode("\n", $m3u8);

foreach ($lines as &$line) {
    $line = trim($line);

    if (!$line || $line[0] === '#') continue;

    $finalUrl = null;

    if (strpos($line, '@@') !== false) {

        $parts = explode('@@', $line);

        $tokenPart  = urlencode($parts[0]);
        $domainPart = $parts[1];

        $finalUrl = 'https://' . $tokenPart . '%40@' . $domainPart;

    }
    elseif (strpos($line, '//') === 0) {

        $finalUrl = 'https:' . $line;

    }
    else {
        continue;
    }

    // double base64
    $line = "segment.php?u=" . base64_encode(base64_encode($finalUrl));
}

$m3u8 = implode("\n", $lines);

header("Content-Type: application/vnd.apple.mpegurl");
echo $m3u8;