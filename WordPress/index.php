<?php

$conn = new mysqli("localhost","juraganfilm","Paddy-041200","juragan");
if ($conn->connect_error) die("DB ERROR");

$prefix = "drama_";

// =====================
// PARAM
// =====================
$id       = (int)($_GET['id'] ?? 0);
$page     = max(1,(int)($_GET['page'] ?? 1));
$search   = $conn->real_escape_string($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$year     = preg_replace('/[^0-9]/','',$_GET['year'] ?? '');
$sort     = $_GET['sort'] ?? 'latest';

$genre = $_GET['genre'] ?? [];
if (!is_array($genre)) $genre = [];

$limit  = 20;
$offset = ($page-1)*$limit;

// =====================
// DETAIL MODE
// =====================
if ($id) {

$sql = "
SELECT 
    p.ID,
    p.post_title,
    p.post_content,
    p.post_date,
    img.guid AS thumbnail,
    GROUP_CONCAT(DISTINCT t.name) AS genres,
    MAX(CASE WHEN pm.meta_key='views' THEN pm.meta_value END) AS views,
    MAX(CASE WHEN pm.meta_key='year' THEN pm.meta_value END) AS year

FROM {$prefix}posts p

LEFT JOIN {$prefix}postmeta thumb 
    ON p.ID = thumb.post_id AND thumb.meta_key='_thumbnail_id'

LEFT JOIN {$prefix}posts img 
    ON img.ID = thumb.meta_value

LEFT JOIN {$prefix}term_relationships tr 
    ON p.ID = tr.object_id

LEFT JOIN {$prefix}term_taxonomy tt 
    ON tr.term_taxonomy_id = tt.term_taxonomy_id

LEFT JOIN {$prefix}terms t 
    ON tt.term_id = t.term_id

LEFT JOIN {$prefix}postmeta pm 
    ON p.ID = pm.post_id

WHERE p.ID = $id
GROUP BY p.ID
";

$res = $conn->query($sql);
if (!$res) die("DETAIL ERROR: ".$conn->error);

$row = $res->fetch_assoc();

// OUTPUT
echo "<h1>{$row['post_title']}</h1>";

if (!empty($row['thumbnail'])) {
    echo "<img src='{$row['thumbnail']}'><br><br>";
}

echo "Tanggal: {$row['post_date']}<br>";

if (!empty($row['year'])) {
    echo "Year: {$row['year']}<br>";
}

if (!empty($row['views'])) {
    echo "Views: {$row['views']}<br>";
}

if (!empty($row['genres'])) {
    echo "Genre: {$row['genres']}<br>";
}

echo "<br>";
echo $row['post_content'];

echo "<br><br><a href='?'>Kembali</a>";

exit;
}

// =====================
// LIST FILTER QUERY
// =====================
$where = "WHERE p.post_status='publish' AND p.post_type IN ('post','tv')";
$join  = "";

// search
if ($search) {
    $where .= " AND p.post_title LIKE '%$search%'";
}

// category / genre
if ($category || $genre) {

    $join .= "
    JOIN {$prefix}term_relationships tr ON p.ID=tr.object_id
    JOIN {$prefix}term_taxonomy tt ON tr.term_taxonomy_id=tt.term_taxonomy_id
    JOIN {$prefix}terms t ON tt.term_id=t.term_id
    ";

    if ($category) {
        $where .= " AND tt.term_id=$category";
    }

    if ($genre) {
        $slugs = array_map(fn($g)=>"'".$conn->real_escape_string($g)."'",$genre);
        $where .= " AND t.slug IN (".implode(',',$slugs).")";
    }
}

// year
if ($year) {
    $join .= "
    JOIN {$prefix}postmeta pm ON p.ID=pm.post_id AND pm.meta_key='year'
    ";
    $where .= " AND pm.meta_value='$year'";
}

// sort
$order = "ORDER BY p.post_date DESC";

if ($sort==='views') {
    $join .= "
    LEFT JOIN {$prefix}postmeta pmv 
    ON p.ID=pmv.post_id AND pmv.meta_key='views'
    ";
    $order = "ORDER BY CAST(pmv.meta_value AS UNSIGNED) DESC";
}

// =====================
// COUNT
// =====================
$total = $conn->query("
SELECT COUNT(DISTINCT p.ID) as total
FROM {$prefix}posts p
$join
$where
")->fetch_assoc()['total'];

$total_pages = max(1,ceil($total/$limit));

// =====================
// LIST ID ONLY
// =====================
$res = $conn->query("
SELECT DISTINCT p.ID
FROM {$prefix}posts p
$join
$where
$order
LIMIT $limit OFFSET $offset
");

$ids=[];
while($r=$res->fetch_assoc()){
    $ids[]=$r['ID'];
}

if(!$ids){
    echo "Tidak ada data";
    exit;
}

$id_list = implode(',',$ids);

// =====================
// DATA LENGKAP (BATCH)
// =====================
$data = $conn->query("
SELECT 
    p.ID,
    p.post_title,
    p.post_date,
    img.guid as thumb,
    GROUP_CONCAT(DISTINCT t.name) as genres,
    MAX(pmv.meta_value) as views

FROM {$prefix}posts p

LEFT JOIN {$prefix}postmeta thumb 
    ON p.ID=thumb.post_id AND thumb.meta_key='_thumbnail_id'
LEFT JOIN {$prefix}posts img 
    ON img.ID=thumb.meta_value

LEFT JOIN {$prefix}term_relationships tr 
    ON p.ID=tr.object_id
LEFT JOIN {$prefix}term_taxonomy tt 
    ON tr.term_taxonomy_id=tt.term_taxonomy_id
LEFT JOIN {$prefix}terms t 
    ON tt.term_id=t.term_id

LEFT JOIN {$prefix}postmeta pmv 
    ON p.ID=pmv.post_id AND pmv.meta_key='views'

WHERE p.ID IN ($id_list)

GROUP BY p.ID
");

echo "<h2>Film</h2>";

echo "Total: $total<br>";
echo "Page: $page / $total_pages<br><br>";

while($row=$data->fetch_assoc()){

    echo "<div>";

    if($row['thumb']){
        echo "<img src='{$row['thumb']}' width='100'><br>";
    }

    echo "<a href='?id={$row['ID']}'><b>{$row['post_title']}</b></a><br>";

    echo "Tanggal: {$row['post_date']}<br>";

    if($row['genres']){
        echo "Genre: {$row['genres']}<br>";
    }

    if($row['views']){
        echo "Views: {$row['views']}<br>";
    }

    echo "</div><br>";
}

// =====================
// PAGINATION
// =====================
if($total_pages>1){

    echo "<div>";

    if($page>1){
        echo "<a href='?page=".($page-1)."'>Prev</a> ";
    }

    for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++){
        echo $i==$page
            ? "<b>$i</b> "
            : "<a href='?page=$i'>$i</a> ";
    }

    if($page<$total_pages){
        echo "<a href='?page=".($page+1)."'>Next</a>";
    }

    echo "</div>";
}