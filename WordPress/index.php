<?php

$conn = new mysqli("localhost", "juraganfilm", "Paddy-041200", "juragan");
if ($conn->connect_error) die("DB Error");

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
// CONFIG
// =====================
$limit  = 20;
$offset = ($page - 1) * $limit;

// =====================
// DETAIL
// =====================
if ($id) {

    $q = $conn->query("SELECT post_title, post_date FROM wp_posts WHERE ID=$id");

    if ($row = $q->fetch_assoc()) {
        echo "<h1>{$row['post_title']}</h1>";
        echo "Tanggal: {$row['post_date']}<br><br>";
    }

    echo "<a href='?'>Kembali</a>";
    exit;
}

// =====================
// BASE QUERY
// =====================
$where = "WHERE p.post_status='publish' AND p.post_type IN ('post','tv')";

// search
if ($search) {
    $where .= " AND p.post_title LIKE '%$search%'";
}

// =====================
// JOIN (CATEGORY / GENRE)
// =====================
$join = "";

// category filter
if ($category) {
    $join .= "
    JOIN wp_term_relationships tr ON p.ID = tr.object_id
    JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    ";
    $where .= " AND tt.term_id = $category";
}

// genre filter (multi)
if ($genre) {

    $genre_ids = [];

    foreach ($genre as $g) {
        $g = $conn->real_escape_string($g);

        $res = $conn->query("SELECT term_id FROM wp_terms WHERE slug='$g'");
        if ($r = $res->fetch_assoc()) {
            $genre_ids[] = $r['term_id'];
        }
    }

    if ($genre_ids) {
        $ids = implode(',', $genre_ids);

        $join .= "
        JOIN wp_term_relationships tr2 ON p.ID = tr2.object_id
        JOIN wp_term_taxonomy tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
        ";

        $where .= " AND tt2.term_id IN ($ids)";
    }
}

// =====================
// YEAR (META)
// =====================
if ($year) {
    $join .= "
    JOIN wp_postmeta pm ON p.ID = pm.post_id
    ";
    $where .= " AND pm.meta_key='year' AND pm.meta_value='$year'";
}

// =====================
// SORT
// =====================
$order = "ORDER BY p.post_date DESC";

if ($sort === 'views') {
    $join .= "
    LEFT JOIN wp_postmeta pmv ON p.ID = pmv.post_id AND pmv.meta_key='views'
    ";
    $order = "ORDER BY CAST(pmv.meta_value AS UNSIGNED) DESC";
}

// =====================
// TOTAL COUNT
// =====================
$total = $conn->query("
    SELECT COUNT(DISTINCT p.ID) as total
    FROM wp_posts p
    $join
    $where
")->fetch_assoc()['total'];

$total_pages = max(1, ceil($total / $limit));

// =====================
// DATA QUERY
// =====================
$result = $conn->query("
    SELECT DISTINCT p.ID, p.post_title
    FROM wp_posts p
    $join
    $where
    $order
    LIMIT $limit OFFSET $offset
");

// =====================
// OUTPUT
// =====================
echo "<h2>Film</h2>";

echo "Total: $total<br>";
echo "Page: $page / $total_pages<br><br>";

while ($row = $result->fetch_assoc()) {

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
        if ($i == $page) {
            echo "<b>$i</b> ";
        } else {
            echo "<a href='?page=$i'>$i</a> ";
        }
    }

    if ($page < $total_pages) {
        echo "<a href='?page=".($page+1)."'>Next</a>";
    }

    echo "</div>";
}