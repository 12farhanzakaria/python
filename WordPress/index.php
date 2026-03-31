<?php

$conn = new mysqli("localhost", "juraganfilm", "Paddy-041200", "juragan");
if ($conn->connect_error) die("DB ERROR: ".$conn->connect_error);

$prefix = "drama_";

// =====================
// PARAM
// =====================
$page     = max(1, (int)($_GET['page'] ?? 1));
$search   = $conn->real_escape_string($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$year     = preg_replace('/[^0-9]/', '', $_GET['year'] ?? '');
$sort     = $_GET['sort'] ?? 'latest';

$genre = $_GET['genre'] ?? [];
if (!is_array($genre)) $genre = [];

$limit  = 20;
$offset = ($page - 1) * $limit;

// =====================
// BASE QUERY PART
// =====================
$where = "WHERE p.post_status='publish' AND p.post_type IN ('post','tv')";
$join  = "";

// search
if ($search) {
    $where .= " AND p.post_title LIKE '%$search%'";
}

// taxonomy (filter)
if ($category || $genre) {
    $join .= "
    INNER JOIN {$prefix}term_relationships tr ON p.ID = tr.object_id
    INNER JOIN {$prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN {$prefix}terms t ON tt.term_id = t.term_id
    ";
}

if ($category) {
    $where .= " AND tt.term_id = $category";
}

if ($genre) {
    $slugs = array_map(fn($g)=>"'".$conn->real_escape_string($g)."'", $genre);
    $where .= " AND t.slug IN (".implode(',', $slugs).")";
}

// year
if ($year) {
    $join .= "
    INNER JOIN {$prefix}postmeta pm_year 
    ON p.ID = pm_year.post_id AND pm_year.meta_key='year'
    ";
    $where .= " AND pm_year.meta_value='$year'";
}

// sort
$order = "ORDER BY p.post_date DESC";

if ($sort === 'views') {
    $join .= "
    LEFT JOIN {$prefix}postmeta pmv 
    ON p.ID = pmv.post_id AND pmv.meta_key='views'
    ";
    $order = "ORDER BY CAST(pmv.meta_value AS UNSIGNED) DESC";
}

// =====================
// COUNT QUERY
// =====================
$count_sql = "
SELECT COUNT(DISTINCT p.ID) as total
FROM {$prefix}posts p
$join
$where
";

$count_res = $conn->query($count_sql);
if (!$count_res) die("COUNT ERROR: ".$conn->error."<br>$count_sql");

$total = $count_res->fetch_assoc()['total'];
$total_pages = max(1, ceil($total / $limit));

// =====================
// DATA QUERY (NO N+1)
// =====================
$sql = "
SELECT 
    p.ID,
    p.post_title,
    img.guid AS thumbnail,
    GROUP_CONCAT(DISTINCT t2.name SEPARATOR ', ') AS genres,
    MAX(pmv.meta_value) AS views

FROM {$prefix}posts p

-- thumbnail
LEFT JOIN {$prefix}postmeta thumb 
    ON p.ID = thumb.post_id AND thumb.meta_key = '_thumbnail_id'
LEFT JOIN {$prefix}posts img 
    ON img.ID = thumb.meta_value

-- genre tampil
LEFT JOIN {$prefix}term_relationships tr2 ON p.ID = tr2.object_id
LEFT JOIN {$prefix}term_taxonomy tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
LEFT JOIN {$prefix}terms t2 ON tt2.term_id = t2.term_id

-- views
LEFT JOIN {$prefix}postmeta pmv 
    ON p.ID = pmv.post_id AND pmv.meta_key='views'

$join
$where

GROUP BY p.ID
$order

LIMIT $limit OFFSET $offset
";

$res = $conn->query($sql);
if (!$res) die("DATA ERROR: ".$conn->error."<br>$sql");

// =====================
// OUTPUT
// =====================
echo "<h2>Film</h2>";

echo "Total: $total<br>";
echo "Page: $page / $total_pages<br><br>";

while ($row = $res->fetch_assoc()) {

    echo "<div>";

    if (!empty($row['thumbnail'])) {
        echo "<img src='{$row['thumbnail']}' width='100'><br>";
    }

    echo "<b>{$row['post_title']}</b><br>";

    if (!empty($row['genres'])) {
        echo "Genre: {$row['genres']}<br>";
    }

    if (!empty($row['views'])) {
        echo "Views: {$row['views']}<br>";
    }

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