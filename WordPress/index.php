<?php
// =====================
// LOAD WP
// =====================
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../wp-load.php';

remove_action('template_redirect', 'redirect_canonical');
add_filter('redirect_canonical', '__return_false');

// =====================
// VALIDASI PARAM
// =====================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$year = isset($_GET['year']) ? preg_replace('/[^0-9]/', '', $_GET['year']) : '';

$allowed_sort = ['latest','views'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sort) ? $_GET['sort'] : 'latest';

$genre = $_GET['genre'] ?? [];
if (!is_array($genre)) $genre = [];
$genre = array_map('sanitize_title', $genre);

// =====================
// NORMALISASI URL
// =====================
$params = [];

if ($category) $params['category'] = $category;
if ($search) $params['search'] = $search;
if ($year) $params['year'] = $year;
if ($sort !== 'latest') $params['sort'] = $sort;
if ($genre) $params['genre'] = $genre;
if ($page > 1) $params['page'] = $page;

$params['_'] = floor(time()/60);

if ($_GET != $params) {
    header("Location: ?" . http_build_query($params));
    exit;
}

$cache = $params['_'];

// =====================
// URL BUILDER
// =====================
function url($p = []) {
    global $cache;
    $p['_'] = $cache;
    return '?' . http_build_query($p);
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
    $meta  = get_post_meta($post->ID);

    $views = 0;
    foreach (['post_views_count','views','view_count','idmuv_views'] as $k) {
        if (!empty($meta[$k][0])) {
            $views = (int)$meta[$k][0];
            break;
        }
    }

    $clean = [];
    foreach ($meta as $k => $v) {
        if ($k[0] === '_') continue;
        $clean[$k] = maybe_unserialize($v[0]);
    }

    $tax = [];
    foreach (get_object_taxonomies($post->post_type) as $t) {
        $terms = get_the_terms($post->ID, $t);
        if (!empty($terms) && !is_wp_error($terms)) {
            $tax[$t] = array_column($terms, 'name');
        }
    }

    echo "<head>";
    echo '<link rel="canonical" href="'.url(current_params()).'">';
    echo "</head>";

    echo "<h1>$title</h1>";
    if ($thumb) echo "<img src='$thumb'><br><br>";

    echo "Views: $views<br>";
    echo "Tanggal: {$post->post_date}<br><br><hr>";

    foreach ($clean as $k => $v) {
        echo "<div><b>$k:</b> ".(is_array($v)?implode(', ',$v):$v)."</div>";
    }

    echo "<hr>";

    foreach ($tax as $k => $v) {
        echo "<div><b>$k:</b> ".implode(', ',$v)."</div>";
    }

    echo "<br><a href='".url(current_params())."'>Kembali</a>";
    exit;
}

// =====================
// MAIN QUERY
// =====================
$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'paged' => $page,
    'no_found_rows' => false,
    'ignore_sticky_posts' => true,
];

if ($search) $args['s'] = $search;
if ($category) $args['cat'] = $category;

if ($genre) {
    $args['tax_query'] = [[
        'taxonomy' => 'category',
        'field' => 'slug',
        'terms' => $genre,
    ]];
}

if ($year) {
    $args['meta_query'] = [[
        'key' => 'year',
        'value' => $year,
    ]];
}

if ($sort === 'views') {
    $args['meta_key'] = 'views';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'DESC';
} else {
    $args['orderby'] = 'date';
    $args['order'] = 'DESC';
}

$q = new WP_Query($args);
$cats = get_categories(['hide_empty'=>true]);

echo "<head>";
echo '<link rel="canonical" href="'.url(current_params()).'">';
echo "</head>";

echo "<h2>Film</h2>";

// =====================
// FORM
// =====================
echo "<form method='get'>";
echo "Search: <input name='search' value='".htmlspecialchars($search)."'> ";

echo "Category: <select name='category'>";
echo "<option value=''>All</option>";
foreach ($cats as $c) {
    $sel = $category==$c->term_id?'selected':'';
    echo "<option value='{$c->term_id}' $sel>{$c->name}</option>";
}
echo "</select><br><br>";

// =====================
// GENRE + COUNT DINAMIS
// =====================
$all_genres = get_terms(['taxonomy'=>'category','hide_empty'=>true]);

echo "Genre:<br>";

foreach ($all_genres as $g) {

    $count_args = [
        'post_type'=>['post','tv'],
        'posts_per_page'=>1,
        'fields'=>'ids'
    ];

    if ($search) $count_args['s']=$search;
    if ($category) $count_args['cat']=$category;

    if ($year) {
        $count_args['meta_query'][] = [
            'key'=>'year',
            'value'=>$year
        ];
    }

    $selected = (array)$genre;
    $terms = array_unique(array_merge($selected, [$g->slug]));

    $count_args['tax_query'][] = [
        'taxonomy'=>'category',
        'field'=>'slug',
        'terms'=>$terms
    ];

    $count_query = new WP_Query($count_args);
    $count = $count_query->found_posts;
    wp_reset_postdata();

    $checked = in_array($g->slug,$selected)?'checked':'';

    echo "<label>";
    echo "<input type='checkbox' name='genre[]' value='{$g->slug}' $checked> ";
    echo "{$g->name} ($count)";
    echo "</label><br>";
}

echo "<br>";

echo "Year: <input name='year' value='$year'> ";

echo "Sort: <select name='sort'>
<option value='latest'>Latest</option>
<option value='views' ".($sort=='views'?'selected':'').">Views</option>
</select> ";

echo "<input type='hidden' name='_' value='$cache'>";
echo "<button>Filter</button>";
echo "</form><br>";

// =====================
// LIST
// =====================
while ($q->have_posts()) {
    $q->the_post();

    $post_id = get_the_ID();
    $title = get_the_title();
    $thumb = get_the_post_thumbnail_url($post_id,'thumbnail');

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
$total = max(1,(int)$q->max_num_pages);

if ($total > 1) {

    echo "<div>";

    if ($page > 1) {
        echo "<a href='".url(current_params()+['page'=>$page-1])."'>Prev</a> ";
    }

    $start = max(1, $page-2);
    $end   = min($total, $page+2);

    if ($start > 1) {
        echo "<a href='".url(current_params()+['page'=>1])."'>1</a> ... ";
    }

    for ($i=$start;$i<=$end;$i++){
        if ($i==$page){
            echo "<b>$i</b> ";
        } else {
            echo "<a href='".url(current_params()+['page'=>$i])."'>$i</a> ";
        }
    }

    if ($end < $total) {
        echo "... <a href='".url(current_params()+['page'=>$total])."'>$total</a> ";
    }

    if ($page < $total) {
        echo "<a href='".url(current_params()+['page'=>$page+1])."'>Next</a>";
    }

    echo "</div>";
}