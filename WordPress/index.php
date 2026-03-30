<?php
// =====================
// 🔥 BYPASS CACHE TANPA UBAH URL
// =====================
if (!isset($_COOKIE['nocache_api'])) {
    setcookie("nocache_api", "1", time()+3600, "/");
}

header("Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0, s-maxage=0");
header("Pragma: no-cache");
header("Expires: 0");
header("X-LiteSpeed-Cache-Control: no-cache");
header("X-Accel-Expires: 0");
header("Vary: Cookie");

define('WP_USE_THEMES', false);
define('BASE_URL', '/api/index.php');

// =====================
// LOAD WORDPRESS
// =====================
require_once __DIR__ . '/../wp-load.php';

// matikan redirect WP
remove_action('template_redirect', 'redirect_canonical');
add_filter('redirect_canonical', '__return_false');

// =====================
// PARAM
// =====================
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($page < 1) $page = 1;

// =====================
// VALIDASI
// =====================
if ($id && !get_post($id)) {
    http_response_code(404);
    exit('Post Not Found');
}

if ($category_id && !get_category($category_id)) {
    http_response_code(404);
    exit('Category Not Found');
}

// =====================
// URL BUILDER
// =====================
function build_url($category, $page) {
    $url = BASE_URL . '?';
    if ($category) $url .= "category=$category&";
    if ($page > 1) $url .= "page=$page&";
    return rtrim($url, '&');
}

// =====================
// DETAIL PAGE
// =====================
if ($id) {

    $post = get_post($id);

    $title = str_replace(['Nonton ', ' Sub Indo', ' hd', ' jf'], '', get_the_title($id));
    $thumb = get_the_post_thumbnail_url($id, 'full');
    $meta = get_post_meta($id);

    // views
    $views = 0;
    foreach (['post_views_count','views','view_count','idmuv_views'] as $k) {
        if (!empty($meta[$k][0])) {
            $views = (int)$meta[$k][0];
            break;
        }
    }

    // meta bersih
    $all_meta = [];
    foreach ($meta as $key => $val) {
        if (strpos($key, '_') === 0) continue;
        $all_meta[$key] = maybe_unserialize($val[0]);
    }

    // taxonomy
    $taxonomies = get_object_taxonomies($post->post_type);
    $terms_data = [];

    foreach ($taxonomies as $tax) {
        $terms = get_the_terms($id, $tax);
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $t) {
                $terms_data[$tax][] = $t->name;
            }
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($title) ?></title>
<style>
body{background:#111;color:#fff;font-family:Arial;padding:20px}
img{border-radius:10px}
</style>
</head>
<body>

<h1><?= $title ?></h1>

<img src="<?= $thumb ?>" style="max-width:300px"><br><br>

<div>Views: <?= number_format($views) ?></div>
<div>Tanggal: <?= $post->post_date ?></div>

<hr>

<h3>🎬 Detail</h3>
<?php foreach ($all_meta as $k => $v): ?>
<div><b><?= $k ?>:</b> <?= is_array($v) ? implode(', ', $v) : $v ?></div>
<?php endforeach; ?>

<hr>

<h3>📂 Taxonomy</h3>
<?php foreach ($terms_data as $tax => $list): ?>
<div><b><?= $tax ?>:</b> <?= implode(', ', $list) ?></div>
<?php endforeach; ?>

<br><br>
<a href="<?= BASE_URL ?>">⬅ Kembali</a>

</body>
</html>
<?php
exit;
}

// =====================
// CATEGORY LIST
// =====================
$categories = get_categories(['hide_empty' => true]);

// =====================
// QUERY LIST
// =====================
$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'paged' => $page,
    'post_status' => 'publish'
];

if ($category_id) {
    $args['cat'] = $category_id;
}

$query = new WP_Query($args);
?>

<!DOCTYPE html>
<html>
<head>
<title><?= $category_id ? "Kategori $category_id" : "Film Terbaru" ?></title>

<style>
body{background:#111;color:#fff;font-family:Arial}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;padding:20px}
.item{background:#222;padding:10px;border-radius:10px;text-align:center}
img{width:100%;border-radius:10px}
a{color:#fff;text-decoration:none;padding:5px 10px;margin:2px;display:inline-block;background:#222;border-radius:5px}
b{padding:5px 10px;background:yellow;color:#000;border-radius:5px}
</style>
</head>
<body>

<h2 style="padding:20px;">🔥 Film Terbaru</h2>

<div style="padding:20px;">
<a href="<?= BASE_URL ?>">Semua</a>

<?php foreach ($categories as $cat): ?>
<a href="<?= BASE_URL ?>?category=<?= $cat->term_id ?>">
<?= $cat->name ?>
</a>
<?php endforeach; ?>
</div>

<div class="grid">
<?php while ($query->have_posts()): $query->the_post(); ?>
<div class="item">
<a href="<?= BASE_URL ?>?id=<?= get_the_ID() ?>">
<img src="<?= get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>">
<div><?= get_the_title(); ?></div>
</a>
</div>
<?php endwhile; wp_reset_postdata(); ?>
</div>

<!-- PAGINATION -->
<?php if ($query->max_num_pages > 1): ?>
<div style="padding:20px;text-align:center;">

<?php
$total = $query->max_num_pages;
$current = $page;

$start = max(1, $current - 2);
$end   = min($total, $current + 2);

if ($current > 1) {
    echo '<a href="'.build_url($category_id, $current - 1).'">⬅ Prev</a>';
}

if ($start > 1) {
    echo '<a href="'.build_url($category_id, 1).'">1</a> ...';
}

for ($i = $start; $i <= $end; $i++) {
    if ($i == $current) {
        echo '<b>'.$i.'</b>';
    } else {
        echo '<a href="'.build_url($category_id, $i).'">'.$i.'</a>';
    }
}

if ($end < $total) {
    echo '... <a href="'.build_url($category_id, $total).'">'.$total.'</a>';
}

if ($current < $total) {
    echo '<a href="'.build_url($category_id, $current + 1).'">Next ➡</a>';
}
?>

</div>
<?php endif; ?>

</body>
</html>