<?php
// =====================
// BYPASS CACHE
// =====================
if (!headers_sent()) {
    if (!isset($_COOKIE['nocache_api'])) {
        setcookie("nocache_api", "1", time()+3600, "/");
    }

    header("Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("X-LiteSpeed-Cache-Control: no-cache");
    header("X-Accel-Expires: 0");
    header("Vary: Cookie");
}

define('WP_USE_THEMES', false);
require_once __DIR__ . '/../wp-load.php';

// matikan redirect WP
remove_action('template_redirect', 'redirect_canonical');
add_filter('redirect_canonical', '__return_false');

// =====================
// PARAM
// =====================
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$cat = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));

// =====================
// DETAIL
// =====================
if ($id) {

    $post = get_post($id);
    if (!$post) exit('Not found');

    $title = str_replace(['Nonton ', ' Sub Indo', ' hd', ' jf'], '', get_the_title($post));
    $thumb = get_the_post_thumbnail_url($post, 'full');
    $meta = get_post_meta($post->ID);

    $views = 0;
    foreach (['post_views_count','views','view_count','idmuv_views'] as $k) {
        if (!empty($meta[$k][0])) {
            $views = (int)$meta[$k][0];
            break;
        }
    }

    // meta bersih
    $clean = [];
    foreach ($meta as $k => $v) {
        if ($k[0] === '_') continue;
        $clean[$k] = maybe_unserialize($v[0]);
    }

    // taxonomy
    $tax = [];
    foreach (get_object_taxonomies($post->post_type) as $t) {
        $terms = get_the_terms($post->ID, $t);
        if (!empty($terms) && !is_wp_error($terms)) {
            $tax[$t] = array_column($terms, 'name');
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

<?php if ($thumb): ?>
<img src="<?= $thumb ?>" style="max-width:300px"><br><br>
<?php endif; ?>

<div>Views: <?= number_format($views) ?></div>
<div>Tanggal: <?= $post->post_date ?></div>

<hr>

<?php foreach ($clean as $k => $v): ?>
<div><b><?= $k ?>:</b> <?= is_array($v) ? implode(', ', $v) : $v ?></div>
<?php endforeach; ?>

<hr>

<?php foreach ($tax as $k => $v): ?>
<div><b><?= $k ?>:</b> <?= implode(', ', $v) ?></div>
<?php endforeach; ?>

<br><br>
<a href="?">⬅ Kembali</a>

</body>
</html>
<?php
exit;
}

// =====================
// LIST
// =====================
$q = new WP_Query([
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'paged' => $page,
    'cat' => $cat ?: ''
]);

$cats = get_categories(['hide_empty' => true]);
?>

<!DOCTYPE html>
<html>
<head>
<title>Film</title>
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
<a href="?">Semua</a>
<?php foreach ($cats as $c): ?>
<a href="?category=<?= $c->term_id ?>"><?= $c->name ?></a>
<?php endforeach; ?>
</div>

<div class="grid">
<?php while ($q->have_posts()): $q->the_post(); ?>
<div class="item">
<a href="?id=<?= get_the_ID() ?>">
<img src="<?= get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>">
<div><?= get_the_title(); ?></div>
</a>
</div>
<?php endwhile; wp_reset_postdata(); ?>
</div>

<?php if ($q->max_num_pages > 1): ?>
<div style="padding:20px;text-align:center;">
<?php for ($i=1;$i<=$q->max_num_pages;$i++): ?>
<?= $i == $page 
    ? "<b>$i</b>" 
    : "<a href='?category=$cat&page=$i'>$i</a>" ?>
<?php endfor; ?>
</div>
<?php endif; ?>

</body>
</html>