<?php
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
$cat_slug = $_GET['cat'] ?? '';
$paged = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($paged < 1) $paged = 1;

$slug = $_GET['slug'] ?? '';
$type = $_GET['type'] ?? '';

// =====================
// URL BUILDER (SEO)
// =====================
function build_url($cat, $page) {
    if ($cat && $page > 1) return "/category/$cat/page/$page";
    if ($cat) return "/category/$cat";
    if ($page > 1) return "/page/$page";
    return "/";
}

// =====================
// 🔥 DETAIL MODE (MOVIE / TV)
// =====================
if ($slug && $type) {

    $post_type = ($type === 'tv') ? 'tv' : 'post';

    $post = get_page_by_path($slug, OBJECT, $post_type);

    if (!$post) exit('Not found');

    $id = $post->ID;

    // clean title
    $title = get_the_title($id);
    $title = strip_tags($title);
    $title = str_replace(['Nonton ', ' Sub Indo', ' hd', ' jf'], '', $title);

    // thumbnail
    $thumb = get_the_post_thumbnail_url($id, 'medium');

    // meta
    $meta = [];
    foreach (get_post_meta($id) as $key => $val) {
        if (strpos($key, '_') === 0) continue;

        $value = maybe_unserialize($val[0]);

        // auto decode JSON
        if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
            $decoded = json_decode($value, true);
            if ($decoded !== null) $value = $decoded;
        }

        $meta[$key] = $value;
    }

    // views
    $views = 0;
    foreach (['post_views_count','views','view_count','idmuv_views'] as $k) {
        if (!empty($meta[$k])) {
            $views = (int)$meta[$k];
            break;
        }
    }
    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title><?= htmlspecialchars($title) ?></title>
        <meta name="description" content="Nonton <?= $title ?> sub indo lengkap.">
        <link rel="canonical" href="/<?= $type ?>/<?= $slug ?>">
    </head>
    <body style="background:#111;color:#fff;font-family:Arial;padding:20px;">

    <h1><?= $title ?></h1>

    <img src="<?= $thumb ?>" style="max-width:250px;">

    <div>
        Views: <?= number_format($views) ?>
    </div>

    <?php if (!empty($meta['episodex'])): ?>
        <div>Episode: <?= $meta['episodex'] ?></div>
    <?php endif; ?>

    <?php if (!empty($meta['IDMUVICORE_Trailer'])): ?>
        <iframe width="100%" height="400"
        src="https://www.youtube.com/embed/<?= $meta['IDMUVICORE_Trailer'] ?>"></iframe>
    <?php endif; ?>

    <br><br>
    <a href="/">⬅ Kembali</a>

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
// QUERY LIST
// =====================
$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'paged' => $paged,
    'no_found_rows' => false
];

if (!empty($cat_slug)) {
    $args['category_name'] = $cat_slug;
}

$query = new WP_Query($args);
?>

<!DOCTYPE html>
<html>
<head>
    <title>
        <?= $cat_slug ? ucfirst($cat_slug) : 'Film Terbaru' ?>
        <?= $paged > 1 ? " - Page $paged" : '' ?>
    </title>

    <meta name="description" content="Nonton <?= $cat_slug ?: 'film terbaru' ?> sub indo lengkap.">
    <link rel="canonical" href="<?= build_url($cat_slug, $paged) ?>">

    <style>
        body { background:#111; color:#fff; font-family:Arial; }
        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:15px; padding:20px; }
        .item { background:#222; padding:10px; border-radius:8px; text-align:center; }
        img { width:100%; border-radius:5px; }
        a { color:#fff; text-decoration:none; }
    </style>
</head>
<body>

<h2 style="padding:20px;">🔥 Film Terbaru</h2>

<!-- 🔥 CATEGORY -->
<div style="padding:20px;">
    <b>Kategori:</b>

    <a href="/" style="margin-right:10px;">Semua</a>

    <?php foreach ($categories as $cat): ?>
        <a href="/category/<?= $cat->slug ?>"
           style="margin-right:10px; <?= ($cat_slug == $cat->slug ? 'color:yellow;' : '') ?>">
           <?= $cat->name ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- 🔥 GRID -->
<div class="grid">
<?php while ($query->have_posts()): $query->the_post(); ?>
    <?php
    $ptype = get_post_type() === 'tv' ? 'tv' : 'movie';
    ?>
    <div class="item">
        <a href="/<?= $ptype ?>/<?= get_post_field('post_name', get_the_ID()) ?>">
            <img src="<?= get_the_post_thumbnail_url(get_the_ID(), 'medium'); ?>">
            <div><?= get_the_title(); ?></div>
        </a>
    </div>
<?php endwhile; wp_reset_postdata(); ?>
</div>

<!-- 🔥 PAGINATION -->
<?php
$total_pages = $query->max_num_pages;
$range = 2;

if ($total_pages > 1):
?>
<div style="padding:20px; text-align:center;">

    <?php if ($paged > 1): ?>
        <a href="<?= build_url($cat_slug, $paged - 1) ?>">⬅ Prev</a>
    <?php endif; ?>

    <?php
    $start = max(1, $paged - $range);
    $end   = min($total_pages, $paged + $range);

    if ($start > 1) {
        echo '<a href="'.build_url($cat_slug,1).'">1</a> ... ';
    }

    for ($i = $start; $i <= $end; $i++):
    ?>
        <a href="<?= build_url($cat_slug, $i) ?>"
           style="
               margin:5px;
               padding:8px 12px;
               background:<?= ($i == $paged ? '#ff9800' : '#222') ?>;
               border-radius:5px;
               color:#fff;
           ">
           <?= $i ?>
        </a>
    <?php endfor;

    if ($end < $total_pages) {
        echo ' ... <a href="'.build_url($cat_slug,$total_pages).'">'.$total_pages.'</a>';
    }
    ?>

    <?php if ($paged < $total_pages): ?>
        <a href="<?= build_url($cat_slug, $paged + 1) ?>">Next ➡</a>
    <?php endif; ?>

</div>
<?php endif; ?>

</body>
</html>