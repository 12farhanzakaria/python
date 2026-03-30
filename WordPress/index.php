<?php
require_once __DIR__ . '/../wp-load.php';

// ambil parameter
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$type = $_GET['type'] ?? '';
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($page < 1) $page = 1;

// =====================
// DETAIL
// =====================
if ($id && $type) {

    $post = get_post($id);
    if (!$post) exit('Not found');

    $title = get_the_title($id);
    $thumb = get_the_post_thumbnail_url($id, 'thumbnail');

    echo "<h1>$title</h1>";
    echo "<img src='$thumb'>";
    echo "<br><a href='?'>Kembali</a>";

    exit;
}

// =====================
// CATEGORY LIST
// =====================
$categories = get_categories(['hide_empty' => true]);

echo "<h2>Category:</h2>";
echo "<a href='?'>Semua</a> ";

foreach ($categories as $cat) {
    echo "<a href='?category={$cat->term_id}'>{$cat->name}</a> ";
}

// =====================
// QUERY
// =====================
$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'paged' => $page
];

if ($category_id) {
    $args['cat'] = $category_id;
}

$query = new WP_Query($args);

// =====================
// LIST
// =====================
echo "<h2>List:</h2>";

while ($query->have_posts()) {
    $query->the_post();

    $id = get_the_ID();
    $title = get_the_title();
    $thumb = get_the_post_thumbnail_url($id, 'thumbnail');
    $type = get_post_type() === 'tv' ? 'tv' : 'movie';

    echo "<div>";
    echo "<a href='?type=$type&id=$id'>";
    echo "<img src='$thumb' width='120'><br>";
    echo "$title</a>";
    echo "</div><br>";
}

wp_reset_postdata();

// =====================
// PAGINATION
// =====================
if ($query->max_num_pages > 1) {

    if ($page > 1) {
        echo "<a href='?page=" . ($page - 1) . "'>Prev</a> ";
    }

    if ($page < $query->max_num_pages) {
        echo "<a href='?page=" . ($page + 1) . "'>Next</a>";
    }
}