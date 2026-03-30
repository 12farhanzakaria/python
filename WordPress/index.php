<?php
// =====================
// ⚡ BASIC SPEED
// =====================
define('WP_USE_THEMES', false);

// =====================
// 🔥 BASE URL (WAJIB)
// =====================
define('BASE_URL', '/api');

// =====================
// ⚡ CACHE SYSTEM
// =====================
$cache_file = __DIR__ . '/cache_' . md5($_SERVER['REQUEST_URI']) . '.html';

if (file_exists($cache_file) && time() - filemtime($cache_file) < 300) {
    readfile($cache_file);
    exit;
}

ob_start();

// =====================
// LOAD WORDPRESS
// =====================
$path = __DIR__;
while (!file_exists($path . '/wp-load.php')) {
    $path = dirname($path);
}
require_once $path . '/wp-load.php';

// =====================
// GET PARAM
// =====================
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$type = $_GET['type'] ?? '';

$category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;

$paged = isset($_GET['page']) ? (int) $_GET['page'] : 1;
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
// 🔥 DETAIL MODE
// =====================
if ($id && $type) {

    $post = get_post($id);
    if (!$post) exit('Not found');

    if ($type === 'tv' && $post->post_type !== 'tv') exit('Invalid');
    if ($type === 'movie' && $post->post_type !== 'post') exit('Invalid');

    $title = str_replace(['Nonton ', ' Sub Indo', ' hd', ' jf'], '', get_the_title($id));
    $thumb = get_the_post_thumbnail_url($id, 'medium');

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
<meta name="description" content="Nonton <?= $title ?> sub indo lengkap.">
<link rel="canonical" href="<?= BASE_URL ?>/<?= $type ?>/<?= $id ?>">
</head>
<body style="background:#111;color:#fff;font-family:Arial;padding:20px;">

<h1><?= $title ?></h1>

<img src="<?= $thumb ?>" style="max-width:250px;">

<div>Views: <?= number_format($views) ?></div>

<?php if (!empty($meta['episodex'][0])): ?>
<div>Episode: <?= $meta['episodex'][0] ?></div>
<?php endif; ?>

<?php if (!empty($meta['IDMUVICORE_Trailer'][0])): ?>
<iframe width="100%" height="400"
src="https://www.youtube.com/embed/<?= $meta['IDMUVICORE_Trailer'][0] ?>"></iframe>
<?php endif; ?>

<br><br>
<a href="<?= BASE_URL ?>/">⬅ Kembali</a>

</body>
</html>
<?php
    $html = ob_get_contents();
    file_put_contents($cache_file, preg_replace('/\s+/', ' ', $html));
    ob_end_flush();
    exit;
}

// =====================
// CATEGORY LIST
// =====================
$categories = get_categories([
    'hide_empty' => true
]);

// =====================
// ⚡ OPTIMIZED QUERY
// =====================
$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'paged' => $paged,

    'no_found_rows' => false,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
    'cache_results' => true,
    'ignore_sticky_posts' => true,
    'fields' => 'ids'
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
<meta name="description" content="Nonton film terbaru sub indo lengkap.">
<link rel="canonical" href="<?= build_url($category_id, $paged) ?>">
<style>
body{background:#111;color:#fff;font-family:Arial}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;padding:20px}
.item{background:#222;padding:10px;border-radius:8px;text-align:center}
img{width:100%;border-radius:5px}
a{color:#fff;text-decoration:none}
</style>
</head>
<body>

<h2 style="padding:20px;">🔥 Film Terbaru</h2>

<!-- CATEGORY -->
<div style="padding:20px;">
<b>Kategori:</b>

<a href="<?= BASE_URL ?>/">Semua</a>

<?php foreach ($categories as $cat): ?>
<a href="<?= BASE_URL ?>/category/<?= $cat->term_id ?>"
style="<?= ($category_id == $cat->term_id ? 'color:yellow;' : '') ?>">
<?= $cat->name ?>
</a>
<?php endforeach; ?>
</div>

<!-- GRID -->
<div class="grid">
<?php foreach ($query->posts as $id): ?>
<?php $ptype = get_post_type($id) === 'tv' ? 'tv' : 'movie'; ?>
<div class="item">
<a href="<?= BASE_URL ?>/<?= $ptype ?>/<?= $id ?>">
<img src="<?= get_the_post_thumbnail_url($id, 'medium'); ?>" loading="lazy">
<div><?= get_the_title($id); ?></div>
</a>
</div>
<?php endforeach; ?>
</div>

<!-- PAGINATION -->
<?php
$total_pages = $query->max_num_pages;
$range = 2;

if ($total_pages > 1):
?>
<div style="padding:20px;text-align:center;">

<?php if ($paged > 1): ?>
<a href="<?= build_url($category_id, $paged - 1) ?>">⬅ Prev</a>
<?php endif; ?>

<?php
$start = max(1, $paged - $range);
$end = min($total_pages, $paged + $range);

if ($start > 1) echo '<a href="'.build_url($category_id,1).'">1</a> ... ';

for ($i = $start; $i <= $end; $i++):
?>
<a href="<?= build_url($category_id,$i) ?>"
style="margin:5px;padding:8px;background:<?= ($i==$paged?'#ff9800':'#222') ?>">
<?= $i ?>
</a>
<?php endfor;

if ($end < $total_pages) echo ' ... <a href="'.build_url($category_id,$total_pages).'">'.$total_pages.'</a>';
?>

<?php if ($paged < $total_pages): ?>
<a href="<?= build_url($category_id, $paged + 1) ?>">Next ➡</a>
<?php endif; ?>

</div>
<?php endif; ?>

</body>
</html>

<?php
$html = ob_get_contents();
file_put_contents($cache_file, preg_replace('/\s+/', ' ', $html));
ob_end_flush();