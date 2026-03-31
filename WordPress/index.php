<?php

// =====================
// DB CONNECT (DIRECT)
// =====================
$conn = new mysqli("localhost", "juraganfilm", "Paddy-041200", "juragan");

if ($conn->connect_error) {
    die("DB ERROR");
}

// =====================
// CONFIG
// =====================
$prefix = "wp_"; // ganti kalau prefix beda

// =====================
// PARAM
// =====================
$id       = (int)($_GET['id'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));
$search   = $conn->real_escape_string($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$year     = preg_replace('/[^0-9]/', '', $_GET['year'] ?? '');
$sort     = $_GET['sort'] ?? 'latest';

$genre = $_GET['genre'] ?? [];
if (!is_array($genre)) $genre = [];

// =====================
// PAGINATION
// =====================
$limit  = 20;
$offset = ($page - 1) * $limit;

// =====================
// DETAIL
// =====================
if ($id) {

    $q = $conn->query("
        SELECT post_title, post_date 
        FROM {$prefix}posts 
        WHERE ID=$id
    ");

    if (!$q) die($conn->error);

    if ($row = $q->fetch_assoc()) {
        echo "<h1>{$row['post_title']}</h1>";
        echo "Tanggal: {$row['post_date']}<br><br>";
    }

    echo "<a href='?'>Kembali</a>";
    exit;
}

// =====================
// BASE
// =====================
$where = "WHERE p.post_status='publish' AND p.post_type IN ('post','tv')";

// search
if ($search) {
    $where .= " AND p.post_title LIKE '%$search%'";
}

// =====================
// JOIN
// =====================
$join = "";

// category / genre
if ($category || $genre) {

    $join .= "
    JOIN {$prefix}term_relationships tr ON p.ID = tr.object_id
    JOIN {$prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    JOIN {$prefix}terms t ON tt.term_id = t.term_id
    ";
}

// category
if ($category) {
    $where .= " AND tt.term_id = $category";
}

// genre (slug)
if ($genre) {

    $slugs = array_map(function($g) use ($conn){
        return "'" . $conn->real_escape_string($g) . "'";
    }, $genre);

    $where .= " AND t.slug IN (" . implode(',', $slugs) . ")";
}

// =====================
// YEAR
// =====================
if ($year) {
    $join .= "
    JOIN {$prefix}postmeta pm ON p.ID = pm.post_id
    ";
    $where .= " AND pm.meta_key='year' AND pm.meta_value='$year'";
}

// =====================
// SORT
// =====================
$order = "ORDER BY p.post_date DESC";

if ($sort === 'views') {
    $join .= "
    LEFT JOIN {$prefix}postmeta pmv 
    ON p.ID = pmv.post_id AND pmv.meta_key='views'
    ";
    $order = "ORDER BY CAST(pmv.meta_value AS UNSIGNED) DESC";
}

// =====================
// COUNT
// =====================
$count_sql = "
SELECT COUNT(DISTINCT p.ID) as total
FROM {$prefix}posts p
$join
$where
";

$count_res = $conn->query($count_sql);
if (!$count_res) die("COUNT ERROR: ".$conn->error);

$total = $count_res->fetch_assoc()['total'];
$total_pages = max(1, ceil($total / $limit));

// =====================
// DATA
// =====================
$data_sql = "
SELECT DISTINCT p.ID, p.post_title
FROM {$prefix}posts p
$join
$where
$order
LIMIT $limit OFFSET $offset
";

$res = $conn->query($data_sql);
if (!$res) die("DATA ERROR: ".$conn->error);

// =====================
// OUTPUT
// =====================
echo "<h2>Film</h2>";
echo "Total: $total<br>";
echo "Page: $page / $total_pages<br><br>";

while ($row = $res->fetch_assoc()) {
    echo "<div>";
    echo "<a href='?id={$row['ID']}'>";
    echo $row['post_title'];
    echo "</a>";
    echo "</div><br>";
}

// =====================
// PAGINATION
// =====================
if ($total_pages > 1) {

    echo "<div>";

    if ($page > 1) {
        echo "<a href='?page=".($page-1)."'>Prev</a> ";
    }

    for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++) {
        echo $i == $page
            ? "<b>$i</b> "
            : "<a href='?page=$i'>$i</a> ";
    }

    if ($page < $total_pages) {
        echo "<a href='?page=".($page+1)."'>Next</a>";
    }

    echo "</div>";
}