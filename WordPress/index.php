<?php
// =====================
// AUTO BYPASS CACHE
// =====================
if (!isset($_GET['_'])) {
    $p = $_GET;
    $p['_'] = time();
    header("Location: ?" . http_build_query($p));
    exit;
}

$cache = $_GET['_'];

// =====================
// LOAD WORDPRESS
// =====================
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../wp-load.php';

// disable redirect WP
remove_action('template_redirect', 'redirect_canonical');
add_filter('redirect_canonical', '__return_false');

// =====================
// PARAM
// =====================
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cat = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));

// =====================
// DETAIL PAGE
// =====================
if ($id) {

    $post = get_post($id);
    if (!$post) exit('Not found');

    $title = get_the_title($post);
    $thumb = get_the_post_thumbnail_url($post, 'full');

    echo "<h1>$title</h1>";

    if ($thumb) {
        echo "<img src='$thumb'><br><br>";
    }

    echo "<a href='?_= $cache'>Kembali</a>";
    exit;
}

// =====================
// LIST
// =====================
$q = new WP_Query([
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'paged' => $page,
    'cat' => $cat ?: ''
]);

$cats = get_categories(['hide_empty' => true]);

echo "<h2>Film</h2>";

// =====================
// CATEGORY
// =====================
echo "<div>";
echo "<a href='?_= $cache'>Semua</a> ";

foreach ($cats as $c) {
    echo "<a href='?category={$c->term_id}&_=$cache'>{$c->name}</a> ";
}
echo "</div><br>";

// =====================
// LIST ITEM
// =====================
while ($q->have_posts()) {
    $q->the_post();

    $id = get_the_ID();
    $title = get_the_title();
    $thumb = get_the_post_thumbnail_url($id, 'thumbnail');

    echo "<div>";
    echo "<a href='?id=$id&_=$cache'>";
    
    if ($thumb) {
        echo "<img src='$thumb'><br>";
    }

    echo "$title</a>";
    echo "</div><br>";
}

wp_reset_postdata();

// =====================
// PAGINATION
// =====================
if ($q->max_num_pages > 1) {

    echo "<div>";

    for ($i = 1; $i <= $q->max_num_pages; $i++) {

        if ($i == $page) {
            echo "<b>$i</b> ";
        } else {
            echo "<a href='?category=$cat&page=$i&_=$cache'>$i</a> ";
        }
    }

    echo "</div>";
}