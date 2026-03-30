<?php
// =====================
// 🔥 TEST INDEX LANGSUNG (TANPA QUERY)
// =====================

// cookie bypass
if (!isset($_COOKIE['nocache_index'])) {
    setcookie("nocache_index", time(), time()+3600, "/");
}

// header anti cache
header("Content-Type: text/plain");

header("Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0, s-maxage=0");
header("Pragma: no-cache");
header("Expires: 0");
header("X-LiteSpeed-Cache-Control: no-cache");
header("X-Accel-Expires: 0");
header("Vary: Cookie");

// output unik
echo "=== INDEX DIRECT TEST ===\n\n";

echo "TIME: " . time() . "\n";
echo "RAND: " . rand(1000,9999) . "\n\n";

echo "URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";