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

    if ($thumb) echo "<img src='".esc_url($thumb)."'><br><br>";

    // INFO UTAMA
    echo "<h3>Informasi:</h3>";

    $map = [
        'IDMUVICORE_Title'=>'Judul',
        'IDMUVICORE_Year'=>'Tahun',
        'IDMUVICORE_Numbepisode'=>'Total Episode',
        'episodex'=>'Episode',
        'IDMUVICORE_tmdbID'=>'TMDB',
        'views'=>'Views'
    ];

    foreach ($map as $k=>$label) {
        if (!empty($meta[$k])) {
            echo "<div><b>$label:</b> ".esc_html($meta[$k])."</div>";
            unset($meta[$k]);
        }
    }

    // poster
    if (!empty($meta['IDMUVICORE_Poster'])) {
        echo "<br><img src='".esc_url($meta['IDMUVICORE_Poster'])."' width='200'><br>";
        unset($meta['IDMUVICORE_Poster']);
    }

    // trailer
    if (!empty($meta['IDMUVICORE_Trailer'])) {
        $yt = esc_html($meta['IDMUVICORE_Trailer']);
        echo "<iframe width='300' height='170' src='https://www.youtube.com/embed/$yt' allowfullscreen></iframe><br>";
        unset($meta['IDMUVICORE_Trailer']);
    }

    // sinopsis
    if (!empty($meta['data-sinopsis'])) {
        echo "<h3>Sinopsis:</h3>";
        echo "<p>".esc_html($meta['data-sinopsis'])."</p>";
        unset($meta['data-sinopsis']);
    }

    // TAXONOMY
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
            unset($tax[$k]);
        }
    }

    // META SISA
    echo "<h3>Lainnya:</h3>";

    foreach ($meta as $k=>$v) {
        if (!$v || $v==='Array') continue;
        echo "<div><b>".esc_html($k).":</b> ".esc_html($v)."</div>";
    }

    echo "<h3>Content:</h3>";
    echo apply_filters('the_content',$post->post_content);

    echo "<br><br><a href='".url(current_params())."'>Kembali</a>";
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
    'update_post_meta_cache'=>false,
    'update_post_term_cache'=>false,
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

// RUN
$q = new WP_Query($args);

$total_posts = $q->found_posts;
$total_pages = $q->max_num_pages;

// =====================
// OUTPUT
// =====================
echo "<h2>Film</h2>";

// =====================
// FILTER FORM
// =====================
echo "<form method='get' style='padding:10px;border:1px solid #ddd;border-radius:8px;'>";

// search
echo "<div><b>Search</b><br>";
echo "<input name='search' value='".esc_attr($search)."' style='width:100%;padding:6px;'></div><br>";

// category
$cats = get_categories(['hide_empty'=>true]);
echo "<div><b>Category</b><br>";
echo "<select name='category' style='width:100%;padding:6px;'>";
echo "<option value=''>All</option>";
foreach ($cats as $c) {
    $sel = $category==$c->term_id?'selected':'';
    echo "<option value='{$c->term_id}' $sel>{$c->name}</option>";
}
echo "</select></div><br>";

// country
$country_terms = get_terms([
    'taxonomy'=>'muvicountry',
    'hide_empty'=>true,
    'number'=>5,
    'orderby'=>'count',
    'order'=>'DESC'
]);

if ($country_terms) {
    echo "<div><b>🌍 Country</b><br>";
    foreach ($country_terms as $t) {
        $checked = (isset($_GET['muvicountry']) && in_array($t->slug,(array)$_GET['muvicountry']))?'checked':'';
        echo "<label><input type='checkbox' name='muvicountry[]' value='".esc_attr($t->slug)."' $checked> ".esc_html($t->name)."</label><br>";
    }
    echo "</div><br>";
}

// network
$network_terms = get_terms([
    'taxonomy'=>'muvinetwork',
    'hide_empty'=>true,
    'number'=>5,
    'orderby'=>'count',
    'order'=>'DESC'
]);

if ($network_terms) {
    echo "<div><b>📺 Network</b><br>";
    foreach ($network_terms as $t) {
        $checked = (isset($_GET['muvinetwork']) && in_array($t->slug,(array)$_GET['muvinetwork']))?'checked':'';
        echo "<label><input type='checkbox' name='muvinetwork[]' value='".esc_attr($t->slug)."' $checked> ".esc_html($t->name)."</label><br>";
    }
    echo "</div><br>";
}

// year
echo "<div><b>Year</b><br>";
echo "<input name='year' value='".esc_attr($year)."' style='width:100%;padding:6px;'>";
echo "</div><br>";

// sort
echo "<div><b>Sort</b><br>";
echo "<select name='sort' style='width:100%;padding:6px;'>
<option value='latest'>Latest</option>
<option value='views' ".($sort=='views'?'selected':'').">Views</option>
</select>";
echo "</div><br>";

echo "<button style='width:100%;padding:8px;background:#222;color:#fff;border:0;border-radius:5px;'>Filter</button>";
echo "</form><br>";

// =====================
// LIST
// =====================
echo "Total: $total_posts<br>";
echo "Page: $page / $total_pages<br><br>";

while ($q->have_posts()) {
    $q->the_post();

    $id = get_the_ID();
    $thumb = get_the_post_thumbnail_url($id,'thumbnail');

    echo "<div>";
    if ($thumb) echo "<img src='".esc_url($thumb)."'><br>";
    echo "<a href='".url(current_params()+['id'=>$id])."'><b>".esc_html(get_the_title())."</b></a>";
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