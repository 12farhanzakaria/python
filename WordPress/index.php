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
// HELPER COUNT FILTER
// =====================
function get_terms_with_count_filtered($taxonomy, $base_args) {

    $ids_args = $base_args;
    $ids_args['fields'] = 'ids';
    $ids_args['posts_per_page'] = -1;
    $ids_args['no_found_rows'] = true;

    $q_ids = new WP_Query($ids_args);
    $object_ids = $q_ids->posts ?: [];

    if (!$object_ids) return [];

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'object_ids' => $object_ids,
    ]);

    $out = [];
    foreach ($terms as $t) {
        if ($t->count > 0) {
            $out[] = $t;
        }
    }

    return $out;
}

// =====================
// DETAIL
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
    // DRIVE
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

    // PLAYER
    $first = $drive_links[0] ?? null;

    if ($first && preg_match('/\/d\/(.*?)\//', $first, $match)) {

        $file_id = $match[1];

        echo "<h3>Player</h3>";
        echo "<iframe src='https://drive.google.com/file/d/$file_id/preview' width='100%' height='480' allowfullscreen></iframe><br><br>";
    }

    // SERIES LIST
    if (count($drive_links) > 1) {

        echo "<h3>Episode:</h3>";

        foreach ($drive_links as $i => $link) {
            $num = $i + 1;

            echo "<div>Episode $num - <a href='".esc_url($link)."' target='_blank'>Buka</a></div>";
        }

        echo "<br>";
    }

    // META
    echo "<h3>Meta:</h3>";
    foreach ($meta as $k => $v) {
        if (!$v || $v === 'Array') continue;
        echo "<div><b>".esc_html($k).":</b> ".esc_html($v)."</div>";
    }

    echo "<hr>";

    // TAX
    echo "<h3>Taxonomy:</h3>";
    foreach ($tax as $k => $v) {
        echo "<div><b>".esc_html($k).":</b> ".esc_html(implode(', ', $v))."</div>";
    }

    echo "<hr>";

    // CONTENT
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
// QUERY BASE
// =====================
$args = [
    'post_type'=>['post','tv'],
    'posts_per_page'=>$per_page,
    'paged'=>$page,
    'post_status'=>'publish',
    'ignore_sticky_posts'=>true,
];

if ($search) $args['s']=$search;

// TAX QUERY
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
        'terms'=>array_map('sanitize_title',(array)$_GET['muvicountry'])
    ];
}

if (!empty($_GET['muvinetwork'])) {
    $tax_query[] = [
        'taxonomy'=>'muvinetwork',
        'field'=>'slug',
        'terms'=>array_map('sanitize_title',(array)$_GET['muvinetwork'])
    ];
}

if ($tax_query) {
    $tax_query['relation'] = 'AND';
    $args['tax_query'] = $tax_query;
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

// =====================
// FILTER FORM
// =====================
echo "<form method='get' style='padding:15px;border:1px solid #ddd;border-radius:10px;margin-bottom:20px;'>";

echo "<input name='search' value='".esc_attr($search)."' placeholder='Search...' style='width:100%;padding:8px;'><br><br>";

// CATEGORY
$cats = get_categories(['hide_empty'=>true]);
echo "<select name='category' style='width:100%;padding:8px;'><option value=''>All</option>";
foreach ($cats as $c) {
    $sel = $category==$c->term_id?'selected':'';
    echo "<option value='{$c->term_id}' $sel>{$c->name}</option>";
}
echo "</select><br><br>";

// COUNTRY (AUTO HIDE + COUNT)
$country_terms = get_terms_with_count_filtered('muvicountry', $args);
foreach ($country_terms as $t) {
    $checked = (isset($_GET['muvicountry']) && in_array($t->slug,(array)$_GET['muvicountry']))?'checked':'';
    echo "<label><input type='checkbox' name='muvicountry[]' value='{$t->slug}' $checked> {$t->name} ({$t->count})</label><br>";
}

echo "<br>";

// NETWORK
$network_terms = get_terms_with_count_filtered('muvinetwork', $args);
foreach ($network_terms as $t) {
    $checked = (isset($_GET['muvinetwork']) && in_array($t->slug,(array)$_GET['muvinetwork']))?'checked':'';
    echo "<label><input type='checkbox' name='muvinetwork[]' value='{$t->slug}' $checked> {$t->name} ({$t->count})</label><br>";
}

echo "<br>";

echo "<input name='year' value='".esc_attr($year)."' placeholder='Year'><br><br>";

echo "<button>Filter</button></form>";

// =====================
// RUN QUERY
// =====================
$q = new WP_Query($args);

while ($q->have_posts()) {
    $q->the_post();

    $pid = get_the_ID();
    $thumb = get_the_post_thumbnail_url($pid,'thumbnail');

    echo "<div>";
    if ($thumb) echo "<img src='".esc_url($thumb)."'><br>";
    echo "<a href='".url(current_params()+['id'=>$pid])."'><b>".esc_html(get_the_title())."</b></a>";
    echo "</div><br>";
}

wp_reset_postdata();