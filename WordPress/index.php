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
// DETAIL
// =====================
if ($id) {

    $post = get_post($id);
    if (!$post) exit('Not found');

    $thumb = get_the_post_thumbnail_url($id, 'full');

    // META
    $meta_raw = get_post_meta($id);
    $meta = [];

    foreach ($meta_raw as $k=>$v) {
        if ($k[0]==='_') continue;

        $val = maybe_unserialize($v[0]);
        if (is_array($val)) $val = implode(', ', $val);

        $meta[$k] = $val;
    }

    // TAX
    $tax = [];
    foreach (get_object_taxonomies($post->post_type) as $t) {
        $terms = get_the_terms($id, $t);
        if ($terms && !is_wp_error($terms)) {
            $tax[$t] = array_column($terms, 'name');
        }
    }

    echo "<h1>".esc_html($post->post_title)."</h1>";

    if ($thumb) echo "<img src='".esc_url($thumb)."' width='200'><br><br>";

    // =====================
    // DRIVE SYSTEM
    // =====================
    $drive_links = [];

    // MOVIE
    if (!empty($meta['IDMUVICORE_Player1'])) {
        $drive_links[] = $meta['IDMUVICORE_Player1'];
    } else {
        // SERIES
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

        echo "<h3>Link Lain:</h3>";

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
    // INFO
    // =====================
    echo "<h3>Detail:</h3>";

    $tax_map = [
        'category'=>'Kategori',
        'post_tag'=>'Tag',
        'muvicast'=>'Cast',
        'muvicountry'=>'Negara',
        'muvinetwork'=>'Network'
    ];

    foreach ($tax_map as $k=>$label) {
        if (!empty($tax[$k])) {
            echo "<div><b>$label:</b> ".esc_html(implode(', ', $tax[$k]))."</div>";
        }
    }

    // SINOPSIS
    if (!empty($meta['data-sinopsis'])) {
        echo "<h3>Sinopsis:</h3>";
        echo "<p>".esc_html($meta['data-sinopsis'])."</p>";
    }

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

// TAX
$tax_query = [];

if ($category) {
    $tax_query[] = [
        'taxonomy'=>'category',
        'field'=>'term_id',
        'terms'=>[$category]
    ];
}

if (!empty($_GET['muvicountry'])) {
    $tax_query[] = [
        'taxonomy'=>'muvicountry',
        'field'=>'slug',
        'terms'=>array_map('sanitize_title',(array)$_GET['muvicountry']),
        'operator'=>'IN'
    ];
}

if (!empty($_GET['muvinetwork'])) {
    $tax_query[] = [
        'taxonomy'=>'muvinetwork',
        'field'=>'slug',
        'terms'=>array_map('sanitize_title',(array)$_GET['muvinetwork']),
        'operator'=>'IN'
    ];
}

if ($tax_query) {
    $tax_query['relation'] = 'AND';
    $args['tax_query'] = $tax_query;
}

// SORT
if ($sort==='views') {
    $args['meta_key']='views';
    $args['orderby']='meta_value_num';
} else {
    $args['orderby']='date';
}

$q = new WP_Query($args);

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

// LIST
while ($q->have_posts()) {
    $q->the_post();

    $id = get_the_ID();
    $thumb = get_the_post_thumbnail_url($id,'thumbnail');

    echo "<div>";

    if ($thumb) echo "<img src='".esc_url($thumb)."'><br>";

    echo "<a href='".url(current_params()+['id'=>$id])."'>";
    echo esc_html(get_the_title());
    echo "</a>";

    echo "</div><br>";
}

wp_reset_postdata();