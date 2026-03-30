<?php
echo "MASUK API"; exit;
// =====================
// 🔥 FORCE NO CACHE (PENTING BANGET)
// =====================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

define('WP_USE_THEMES', false);
define('BASE_URL', '/api');

// =====================
// LOAD WORDPRESS (FIXED)
// =====================
require_once __DIR__ . '/../wp-load.php';

// matikan redirect WP
remove_action('template_redirect', 'redirect_canonical');
remove_action('template_redirect', 'wp_redirect_admin_locations');
add_filter('redirect_canonical', '__return_false');

// =====================
// 🔥 ROUTER (STRICT)
// =====================
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = explode('/', $uri);

// hapus "api"
if (!empty($segments) && $segments[0] === 'api') {
    array_shift($segments);
}

$_GET = [];
$is_valid_route = false;

// movie
if (($segments[0] ?? '') === 'movie' && isset($segments[1]) && is_numeric($segments[1])) {
    $_GET['type'] = 'movie';
    $_GET['id'] = (int)$segments[1];
    $is_valid_route = true;
}

// tv
elseif (($segments[0] ?? '') === 'tv' && isset($segments[1]) && is_numeric($segments[1])) {
    $_GET['type'] = 'tv';
    $_GET['id'] = (int)$segments[1];
    $is_valid_route = true;
}

// category
elseif (($segments[0] ?? '') === 'category' && isset($segments[1]) && is_numeric($segments[1])) {
    $_GET['category_id'] = (int)$segments[1];
    $is_valid_route = true;

    if (($segments[2] ?? '') === 'page' && isset($segments[3]) && is_numeric($segments[3])) {
        $_GET['page'] = (int)$segments[3];
    }
}

// page
elseif (($segments[0] ?? '') === 'page' && isset($segments[1]) && is_numeric($segments[1])) {
    $_GET['page'] = (int)$segments[1];
    $is_valid_route = true;
}

// homepage
elseif (empty($segments[0])) {
    $is_valid_route = true;
}

// invalid route
if (!$is_valid_route) {
    http_response_code(404);
    exit('404 Not Found');
}

// =====================
// VALIDASI DATA
// =====================
if (!empty($_GET['id'])) {
    if (!get_post($_GET['id'])) {
        http_response_code(404);
        exit('Post Not Found');
    }
}

if (!empty($_GET['category_id'])) {
    if (!get_category($_GET['category_id'])) {
        http_response_code(404);
        exit('Category Not Found');
    }
}

// =====================
// PARAM FINAL
// =====================
$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? '';
$category_id = $_GET['category_id'] ?? 0;
$paged = $_GET['page'] ?? 1;

if ($paged < 1) $paged = 1;

// =====================
// URL BUILDER
// =====================
function build_url($category_id, $page) {
    if ($category_id && $page > 1) return BASE_URL . "/category/$category_id/page/$page";
    if ($category_id) return BASE_URL . "/category/$category_id";
    if ($page > 1) return BASE_URL . "/page/$page";
    return BASE_URL . "/";
}

// =====================
// DETAIL PAGE
// =====================
if ($id && $type) {

    $title = str_replace(['Nonton ', ' Sub Indo', ' hd', ' jf'], '', get_the_title($id));
    $thumb = get_the_post_thumbnail_url($id, 'thumbnail');
    $meta = get_post_meta($id);

    $views = 0;
    foreach (['post_views_count','views','view_count','idmuv_views'] as $k) {
        if (!empty($meta[$k][0])) {
            $views = (int)$meta[$k][0];
            break;
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($title) ?></title>
<link rel="canonical" href="<?= BASE_URL ?>/<?= $type ?>/<?= $id ?>">
</head>
<body style="background:#111;color:#fff;font-family:Arial;padding:20px;">

<h1><?= $title ?></h1>
<img src="<?= $thumb ?>">
<div>Views: <?= number_format($views) ?></div>

<a href="<?= BASE_URL ?>/">⬅ Kembali</a>

</body>
</html>
<?php
exit;
}

// =====================
// CATEGORY LIST
// =====================
$categories = get_categories([
    'hide_empty' => true,
    'number' => 20
]);

// =====================
// QUERY
// =====================
$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'paged' => $paged,
    'ignore_sticky_posts' => true
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
<link rel="canonical" href="<?= build_url($category_id, $paged) ?>">

<style>
body{background:#111;color:#fff;font-family:Arial}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;padding:20px}
.item{background:#222;padding:10px;border-radius:8px;text-align:center}
img{width:100%}
a{color:#fff;text-decoration:none}
</style>
</head>
<body>

<h2 style="padding:20px;">🔥 Film Terbaru</h2>

<div style="padding:20px;">
<a href="<?= BASE_URL ?>/">Semua</a>

<?php foreach ($categories as $cat): ?>
<a href="<?= BASE_URL ?>/category/<?= $cat->term_id ?>"
style="<?= ($category_id == $cat->term_id ? 'color:yellow;' : '') ?>">
<?= $cat->name ?>
</a>
<?php endforeach; ?>
</div>

<div class="grid">
<?php while ($query->have_posts()): $query->the_post(); ?>
<?php $ptype = get_post_type() === 'tv' ? 'tv' : 'movie'; ?>
<div class="item">
<a href="<?= BASE_URL ?>/<?= $ptype ?>/<?= get_the_ID() ?>">
<img src="<?= get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>" loading="lazy">
<div><?= get_the_title(); ?></div>
</a>
</div>
<?php endwhile; wp_reset_postdata(); ?>
</div>

<?php if ($query->max_num_pages > 1): ?>
<div style="padding:20px;text-align:center;">
<?php if ($paged > 1): ?>
<a href="<?= build_url($category_id,$paged-1) ?>">⬅ Prev</a>
<?php endif; ?>
<?php if ($paged < $query->max_num_pages): ?>
<a href="<?= build_url($category_id,$paged+1) ?>">Next ➡</a>
<?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>