<?php
// =====================
// AUTO BYPASS CACHE
// =====================
if (!isset($_GET['_'])) {
    $p = $_GET;
    $p['_'] = floor(time() / 60);
    header("Location: ?" . http_build_query($p));
    exit;
}

$cache = $_GET['_'];

// =====================
// LOAD WP
// =====================
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
$search   = trim($_GET['search'] ?? '');
$genre    = $_GET['genre'] ?? [];
if (!is_array($genre)) $genre = [];
$year     = trim($_GET['year'] ?? '');
$sort     = $_GET['sort'] ?? 'latest';

// =====================
// URL BUILDER
// =====================
function url($params = []) {
    global $cache;
    $params['_'] = $cache;
    return '?' . http_build_query($params);
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

    echo "<h1>$title</h1>";
    if ($thumb) echo "<img src='$thumb'><br><br>";

    echo "Views: $views<br>";
    echo "Tanggal: {$post->post_date}<br><br>";

    echo "<a href='".url()."'>Kembali</a>";
    exit;
}

// =====================
// QUERY
// =====================
$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'paged' => $page,
];

if ($search) $args['s'] = $search;
if ($category) $args['cat'] = $category;

if ($genre) {
    $args['tax_query'][] = [
        'taxonomy' => 'category',
        'field' => 'slug',
        'terms' => $genre,
    ];
}

if ($year) {
    $args['meta_query'][] = [
        'key' => 'year',
        'value' => $year,
    ];
}

if ($sort === 'views') {
    $args['meta_key'] = 'views';
    $args['orderby'] = 'meta_value_num';
} else {
    $args['orderby'] = 'date';
}

$q = new WP_Query($args);
$cats = get_categories(['hide_empty'=>true]);

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
// CHECKBOX GENRE + COUNT DINAMIS
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
    echo "<a href='".url(['id'=>$post_id])."'>";

    if ($thumb) echo "<img src='$thumb'><br>";

    echo "$title</a>";
    echo "</div><br>";
}

wp_reset_postdata();

// =====================
// PAGINATION
// =====================
if ($q->max_num_pages>1){

    $total=$q->max_num_pages;

    echo "<div>";

    if ($page>1){
        echo "<a href='".url(['page'=>$page-1])."'>Prev</a> ";
    }

    for($i=max(1,$page-2);$i<=min($total,$page+2);$i++){
        echo $i==$page?"<b>$i</b> ":"<a href='".url(['page'=>$i])."'>$i</a> ";
    }

    if ($page<$total){
        echo "<a href='".url(['page'=>$page+1])."'>Next</a>";
    }

    echo "</div>";
}