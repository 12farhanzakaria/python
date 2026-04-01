<?php
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../wp-load.php';

remove_action('template_redirect', 'redirect_canonical');
add_filter('redirect_canonical', '__return_false');

// =====================
// PARAM
// =====================
$id       = (int)($_GET['id'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));
$search   = sanitize_text_field($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$year     = preg_replace('/[^0-9]/', '', $_GET['year'] ?? '');
$sort     = in_array($_GET['sort'] ?? '', ['latest','views']) ? $_GET['sort'] : 'latest';

$genre = $_GET['genre'] ?? [];
if (!is_array($genre)) $genre = [];
$genre = array_map('sanitize_title', $genre);

$per_page = 20;

// =====================
// URL HELPER
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
// DETAIL (FULL DATA)
// =====================
if ($id) {

    $post = get_post($id);
    if (!$post) exit('Not found');

    $thumb = get_the_post_thumbnail_url($id, 'full');

    // META (SEMUA)
    $meta_raw = get_post_meta($id);
    $meta = [];

    foreach ($meta_raw as $key => $val) {
        if ($key[0] === '_') continue;
        $meta[$key] = maybe_unserialize($val[0]);
    }

    // TAXONOMY (SEMUA)
    $tax = [];
    foreach (get_object_taxonomies($post->post_type) as $taxonomy) {
        $terms = get_the_terms($id, $taxonomy);
        if (!empty($terms) && !is_wp_error($terms)) {
            $tax[$taxonomy] = array_column($terms, 'name');
        }
    }

    // OUTPUT
    echo "<h1>{$post->post_title}</h1>";

    if ($thumb) echo "<img src='$thumb'><br><br>";

    echo "Tanggal: {$post->post_date}<br><br>";

    if ($meta) {
        echo "<h3>Meta:</h3>";
        foreach ($meta as $k => $v) {
            if (is_array($v)) $v = implode(', ', $v);
            echo "<div><b>$k:</b> $v</div>";
        }
        echo "<br>";
    }

    if ($tax) {
        echo "<h3>Taxonomy:</h3>";
        foreach ($tax as $k => $v) {
            echo "<div><b>$k:</b> ".implode(', ', $v)."</div>";
        }
        echo "<br>";
    }

    echo "<h3>Content:</h3>";
    echo $post->post_content;

    echo "<br><br><a href='".url(current_params())."'>Kembali</a>";

    echo inject_js();
    exit;
}

// =====================
// QUERY
// =====================
$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => $per_page,
    'paged' => $page,
    'post_status' => 'publish',
    'ignore_sticky_posts' => true,
    'no_found_rows' => false
];

// FILTER
if ($search) $args['s'] = $search;

$tax_query = [];

if ($category) {
    $tax_query[] = [
        'taxonomy'=>'category',
        'field'=>'term_id',
        'terms'=>[$category],
    ];
}

if ($genre) {
    $tax_query[] = [
        'taxonomy'=>'category',
        'field'=>'slug',
        'terms'=>$genre,
    ];
}

if ($tax_query) {
    $tax_query['relation'] = 'AND';
    $args['tax_query'] = $tax_query;
}

if ($year) {
    $args['meta_query'][] = [
        'key'=>'year',
        'value'=>$year
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

// RUN
$q = new WP_Query($args);

$total_posts = $q->found_posts;
$total_pages = $q->max_num_pages;

// =====================
// OUTPUT LIST
// =====================
echo "<h2>Film</h2>";

echo "<form method='get'>";
echo "Search: <input name='search' value='".esc_attr($search)."'> ";
echo "<button>Cari</button>";
echo "</form><br>";

echo "Total: $total_posts<br>";
echo "Page: $page / $total_pages<br><br>";

while ($q->have_posts()) {
    $q->the_post();

    $post_id = get_the_ID();
    $thumb = get_the_post_thumbnail_url($post_id, 'thumbnail');

    echo "<div>";

    if ($thumb) echo "<img src='$thumb'><br>";

    echo "<a href='".url(current_params()+['id'=>$post_id])."'><b>".get_the_title()."</b></a><br>";
    echo "Tanggal: ".get_the_date()."<br>";

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

    for ($i=max(1,$page-2); $i<=min($total_pages,$page+2); $i++) {
        echo $i==$page
            ? "<b>$i</b> "
            : "<a href='".url(current_params()+['page'=>$i])."'>$i</a> ";
    }

    if ($page < $total_pages) {
        echo "<a href='".url(current_params()+['page'=>$page+1])."'>Next</a>";
    }

    echo "</div>";
}

// =====================
// JS CACHE BYPASS
// =====================
function inject_js(){
return <<<HTML
<script>
(function(){
    const key = Math.floor(Date.now()/60000);
    document.querySelectorAll('a[href]').forEach(a=>{
        try{
            let u=new URL(a.href,location.origin);
            u.searchParams.set('_t',key);
            a.href=u.pathname+u.search;
        }catch(e){}
    });
})();
</script>
HTML;
}

echo inject_js();