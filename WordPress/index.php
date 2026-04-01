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

$per_page = 20;

// =====================
// URL
// =====================
function url($params = []) {
    return '?' . http_build_query($params);
}

function current_params() {
    $p = [];
    foreach ($_GET as $k=>$v) {
        if ($k === 'id') continue;
        if (!empty($v)) $p[$k] = $v;
    }
    return $p;
}

// =====================
// DETAIL FULL + PLAYER
// =====================
if ($id) {

    $post = get_post($id);
    if (!$post) exit('Not found');

    $title = get_the_title($post);
    $thumb = get_the_post_thumbnail_url($post, 'full');

    // META
    $meta_raw = get_post_meta($post->ID);
    $meta = [];

    foreach ($meta_raw as $k => $v) {
        if ($k[0] === '_') continue;

        $val = maybe_unserialize($v[0]);
        if (is_array($val)) $val = implode(', ', $val);

        $meta[$k] = $val;
    }

    // TAX
    $tax = [];
    foreach (get_object_taxonomies($post->post_type) as $t) {
        $terms = get_the_terms($post->ID, $t);
        if ($terms && !is_wp_error($terms)) {
            $tax[$t] = array_column($terms, 'name');
        }
    }

    echo "<h1>".esc_html($title)."</h1>";

    if ($thumb) echo "<img src='".esc_url($thumb)."' width='200'><br><br>";

    // =====================
    // DRIVE SYSTEM
    // =====================
    $drive_links = [];

    if (!empty($meta['IDMUVICORE_Player1'])) {
        $drive_links[] = $meta['IDMUVICORE_Player1'];
    } else {
        preg_match_all('/https?:\/\/[^\s"]*drive\.google\.com[^\s"]*/', $post->post_content, $m);
        if (!empty($m[0])) {
            $drive_links = array_values(array_unique($m[0]));
        }
    }

    // =====================
    // PLAYER
    // =====================
    $first = $drive_links[0] ?? null;

    if ($first && preg_match('/\/d\/(.*?)\//', $first, $match)) {

        $file_id = $match[1];

        echo "<h3>Player</h3>";

        echo "<iframe 
            src='https://drive.google.com/file/d/$file_id/preview' 
            width='100%' height='480' 
            allowfullscreen>
        </iframe><br><br>";
    }

    // =====================
    // LIST LINK (SERIES)
    // =====================
    if (count($drive_links) > 1) {

        echo "<h3>Episode:</h3>";

        foreach ($drive_links as $i => $link) {

            $num = $i + 1;

            echo "<div>";
            echo "Episode $num - ";
            echo "<a href='".esc_url($link)."' target='_blank'>Buka</a>";
            echo "</div>";
        }

        echo "<br>";
    }

    // =====================
    // META FULL
    // =====================
    echo "<h3>Meta:</h3>";

    foreach ($meta as $k => $v) {
        if (!$v || $v === 'Array') continue;

        echo "<div><b>".esc_html($k).":</b> ".esc_html($v)."</div>";
    }

    echo "<hr>";

    // =====================
    // TAX FULL
    // =====================
    echo "<h3>Taxonomy:</h3>";

    foreach ($tax as $k => $v) {
        echo "<div><b>".esc_html($k).":</b> ".esc_html(implode(', ', $v))."</div>";
    }

    echo "<hr>";

    // =====================
    // CONTENT (BERSIH)
    // =====================
    echo "<h3>Content:</h3>";

    $content = $post->post_content;
    $content = strip_shortcodes($content);
    $content = preg_replace('/\[[^\]]*\]/', '', $content);
    $content = wp_strip_all_tags($content);

    echo "<div>".nl2br(trim($content))."</div>";

    echo "<br><a href='".url(current_params())."'>Kembali</a>";

    exit;
}

// =====================
// QUERY
// =====================
$args = [
    'post_type'=>['post','tv'],
    'posts_per_page'=>$per_page,
    'paged'=>$page,
    'post_status'=>'publish',
    'ignore_sticky_posts'=>true,
    'no_found_rows'=>false,
];

if ($search) $args['s']=$search;

// CATEGORY
if ($category) {
    $args['cat'] = $category;
}

// YEAR
if ($year) {
    $args['meta_query'][] = [
        'key'=>'year',
        'value'=>$year
    ];
}

// SORT
if ($sort==='views') {
    $args['meta_key']='views';
    $args['orderby']='meta_value_num';
} else {
    $args['orderby']='date';
}

$q = new WP_Query($args);

$total_posts = $q->found_posts;
$total_pages = $q->max_num_pages;

// =====================
// OUTPUT
// =====================
echo "<h2>Film</h2>";

// FILTER
echo "<form method='get'>";

echo "Search: <input name='search' value='".esc_attr($search)."'> ";

$cats = get_categories(['hide_empty'=>true]);
echo "<select name='category'>";
echo "<option value=''>All</option>";
foreach ($cats as $c) {
    $sel = $category==$c->term_id?'selected':'';
    echo "<option value='{$c->term_id}' $sel>{$c->name}</option>";
}
echo "</select>";

echo "<button>Filter</button>";
echo "</form><br>";

echo "Total: $total_posts<br>";
echo "Page: $page / $total_pages<br><br>";

// LIST
while ($q->have_posts()) {
    $q->the_post();

    $pid = get_the_ID();
    $thumb = get_the_post_thumbnail_url($pid,'thumbnail');

    echo "<div>";

    if ($thumb) echo "<img src='".esc_url($thumb)."'><br>";

    echo "<a href='".url(current_params()+['id'=>$pid])."'>";
    echo "<b>".esc_html(get_the_title())."</b>";
    echo "</a>";

    echo "</div><br>";
}

wp_reset_postdata();