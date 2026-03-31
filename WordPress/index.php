<?php
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../wp-load.php';

remove_action('template_redirect', 'redirect_canonical');
add_filter('redirect_canonical', '__return_false');

// =====================
// PARAM
// =====================
$id       = (int)($_GET['id'] ?? 0);
$category = (int)($_GET['category'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));
$search   = sanitize_text_field($_GET['search'] ?? '');
$year     = preg_replace('/[^0-9]/', '', $_GET['year'] ?? '');
$sort     = in_array($_GET['sort'] ?? '', ['latest','views']) ? $_GET['sort'] : 'latest';

$genre = $_GET['genre'] ?? [];
if (!is_array($genre)) $genre = [];
$genre = array_map('sanitize_title', $genre);

// =====================
// CONFIG
// =====================
$per_page = 20;
$offset   = ($page - 1) * $per_page;

// =====================
// URL BUILDER
// =====================
function url($params = []) {
    return '?' . http_build_query($params);
}

function current_params() {
    $p = [];

    if (!empty($_GET['category'])) $p['category'] = (int)$_GET['category'];
    if (!empty($_GET['search'])) $p['search'] = $_GET['search'];
    if (!empty($_GET['year'])) $p['year'] = $_GET['year'];
    if (!empty($_GET['sort']) && $_GET['sort'] !== 'latest') $p['sort'] = $_GET['sort'];
    if (!empty($_GET['genre'])) $p['genre'] = $_GET['genre'];

    return $p;
}

// =====================
// DETAIL
// =====================
if ($id) {

    $post = get_post($id);
    if (!$post) exit('Not found');

    $title = get_the_title($post);
    $thumb = get_the_post_thumbnail_url($post, 'full');

    echo "<h1>$title</h1>";
    if ($thumb) echo "<img src='$thumb'><br><br>";

    echo "<a href='".url(current_params())."'>Kembali</a>";

    echo inject_js(); // inject JS tetap jalan di detail
    exit;
}

// =====================
// BASE QUERY
// =====================
$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => $per_page,
    'offset' => $offset,
    'ignore_sticky_posts' => true,
    'post_status' => 'publish',
];

// FILTER
if ($search) $args['s'] = $search;

$tax_query = [];

if ($category) {
    $tax_query[] = [
        'taxonomy' => 'category',
        'field' => 'term_id',
        'terms' => [$category],
    ];
}

if ($genre) {
    $tax_query[] = [
        'taxonomy' => 'category',
        'field' => 'slug',
        'terms' => $genre,
    ];
}

if ($tax_query) {
    $tax_query['relation'] = 'AND';
    $args['tax_query'] = $tax_query;
}

if ($year) {
    $args['meta_query'][] = [
        'key' => 'year',
        'value' => $year,
    ];
}

// SORT
if ($sort === 'views') {
    $args['meta_key'] = 'views';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'DESC';
} else {
    $args['orderby'] = 'date';
    $args['order'] = 'DESC';
}

// =====================
// MAIN QUERY
// =====================
$q = new WP_Query($args);

// =====================
// COUNT QUERY (AKURAT)
// =====================
$count_args = $args;
$count_args['posts_per_page'] = 1;
$count_args['offset'] = 0;
$count_args['no_found_rows'] = false;

$count_query = new WP_Query($count_args);

$total_posts = $count_query->found_posts;
$total_pages = max(1, ceil($total_posts / $per_page));

// =====================
// OUTPUT
// =====================
echo "<h2>Film</h2>";

echo "Total: $total_posts<br>";
echo "Page: $page / $total_pages<br><br>";

// =====================
// LIST
// =====================
while ($q->have_posts()) {
    $q->the_post();

    $post_id = get_the_ID();
    $title = get_the_title();
    $thumb = get_the_post_thumbnail_url($post_id, 'thumbnail');

    echo "<div>";
    echo "<a href='".url(current_params()+['id'=>$post_id])."'>";

    if ($thumb) echo "<img src='$thumb'><br>";

    echo "$title</a>";
    echo "</div><br>";
}

wp_reset_postdata();

// =====================
// PAGINATION
// =====================
if ($total_pages > 1) {

    echo "<div>";

    if ($page > 1) {
        echo "<a href='".url(current_params()+['page'=>$page-1])."'>Prev</a> ";
    }

    for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++) {
        if ($i == $page) {
            echo "<b>$i</b> ";
        } else {
            echo "<a href='".url(current_params()+['page'=>$i])."'>$i</a> ";
        }
    }

    if ($page < $total_pages) {
        echo "<a href='".url(current_params()+['page'=>$page+1])."'>Next</a>";
    }

    echo "</div>";
}

// =====================
// JS CACHE BYPASS (AUTO INJECT)
// =====================
function inject_js() {
return <<<HTML
<script>
(function(){

    // ganti tiap 60 detik (stabil)
    const cacheKey = Math.floor(Date.now() / 60000);

    // update semua link
    document.querySelectorAll('a[href]').forEach(link => {
        try {
            const url = new URL(link.href, window.location.origin);

            if (url.protocol.startsWith('javascript')) return;

            url.searchParams.delete('_t');
            url.searchParams.set('_t', cacheKey);

            link.href = url.pathname + url.search;
        } catch(e){}
    });

    // update form
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(){
            let input = form.querySelector('input[name="_t"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_t';
                form.appendChild(input);
            }
            input.value = cacheKey;
        });
    });

})();
</script>
HTML;
}

echo inject_js();