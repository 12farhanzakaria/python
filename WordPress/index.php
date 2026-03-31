<?php

$conn = new mysqli("localhost", "juraganfilm", "Paddy-041200", "juragan");

// cek koneksi
if ($conn->connect_error) {
    die("❌ Gagal konek: " . $conn->connect_error);
}

echo "✅ Koneksi database berhasil!<br><br>";

// test query
$result = $conn->query("SELECT ID, post_title FROM wp_posts LIMIT 5");

if (!$result) {
    die("❌ Query error: " . $conn->error);
}

echo "Data:<br><br>";

while ($row = $result->fetch_assoc()) {
    echo $row['ID'] . " - " . $row['post_title'] . "<br>";
}