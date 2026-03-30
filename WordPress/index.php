<?php
// =====================
// NO CACHE
// =====================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

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
$type = $_GET['type'] ?? '';
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
if ($id && $type) {

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
</head>
<body style="background:#111;color:#fff;font-family:Arial;padding:20px;">

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
$categories = get_categories([
    'hide_empty' => true
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
    'paged' => $page,
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
<a href="<?= BASE_URL ?>">Semua</a>

<?php foreach ($categories as $cat): ?>
<a href="<?= BASE_URL ?>?category=<?= $cat->term_id ?>"
style="<?= ($category_id == $cat->term_id ? 'color:yellow;' : '') ?>">
<?= $cat->name ?>
</a>
<?php endforeach; ?>
</div>

<div class="grid">
<?php while ($query->have_posts()): $query->the_post(); ?>
<?php $ptype = get_post_type() === 'tv' ? 'tv' : 'movie'; ?>
<div class="item">
<a href="<?= BASE_URL ?>?type=<?= $ptype ?>&id=<?= get_the_ID() ?>">
<img src="<?= get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>" loading="lazy">
<div><?= get_the_title(); ?></div>
</a>
</div>
<?php endwhile; wp_reset_postdata(); ?>
</div>

<?php if ($query->max_num_pages > 1): ?>
<div style="padding:20px;text-align:center;">
<?php if ($page > 1): ?>
<a href="<?= build_url($category_id,$page-1) ?>">⬅ Prev</a>
<?php endif; ?>

<?php if ($page < $query->max_num_pages): ?>
<a href="<?= build_url($category_id,$page+1) ?>">Next ➡</a>
<?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>