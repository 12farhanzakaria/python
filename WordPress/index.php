<?php
// load WordPress
$path = __DIR__;
while (!file_exists($path . '/wp-load.php')) {
    $path = dirname($path);
}
require_once $path . '/wp-load.php';
$cat_slug = $_GET['cat'] ?? '';
$paged = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($paged < 1) $paged = 1;
$categories = get_categories([
    'hide_empty' => true
]);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// =====================
// 🔥 MODE DETAIL (FULL)
// =====================
if ($id) {

    $post = get_post($id);
    if (!$post) exit('Post not found');

    // 🔥 CLEAN TITLE
    $title = get_the_title($id);
    $title = strip_tags($title);
    $title = str_replace(['Nonton ', ' Sub Indo', ' hd', ' jf'], '', $title);

    // 🔥 META FULL (AUTO JSON + UNserialize)
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

    // 🔥 VIEWS dari meta
    $views = 0;
    foreach (['post_views_count','views','view_count','idmuv_views'] as $key) {
        if (!empty($meta[$key])) {
            $views = (int) $meta[$key];
            break;
        }
    }

    // 🔥 TAXONOMY FULL
    function get_terms_list($post_id, $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        $result = [];

        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $t) {
                $result[] = [
                    'id'   => $t->term_id,
                    'name' => $t->name,
                    'slug' => $t->slug
                ];
            }
        }

        return $result;
    }

    $taxonomies = get_object_taxonomies($post->post_type);
    $all_tax = [];

    foreach ($taxonomies as $tax) {
        $list = get_terms_list($id, $tax);
        if (!empty($list)) {
            $all_tax[$tax] = $list;
        }
    }

    // 🔥 THUMBNAIL
    $thumb_id = get_post_thumbnail_id($id);

    $thumbnail = [
        'full'   => wp_get_attachment_image_src($thumb_id, 'full')[0] ?? '',
        'medium' => wp_get_attachment_image_src($thumb_id, 'medium')[0] ?? '',
        'small'  => wp_get_attachment_image_src($thumb_id, 'thumbnail')[0] ?? ''
    ];
    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title><?= htmlspecialchars($title) ?></title>
        <style>
            body { background:#111; color:#fff; font-family:Arial; padding:20px; }
            .box { background:#222; padding:15px; margin-top:15px; border-radius:8px; }
            img { max-width:250px; }
            a { color:#0af; }
        </style>
    </head>
    <body>

    <h1><?= $title ?></h1>

    <img src="<?= $thumbnail['medium'] ?>">

    <div class="box">
        Views: <?= number_format($views) ?><br>
        Date: <?= $post->post_date ?><br>
        Type: <?= $post->post_type ?>
    </div>

    <?php if (!empty($meta['IDMUVICORE_Trailer'])): ?>
    <div class="box">
        <iframe width="100%" height="400"
        src="https://www.youtube.com/embed/<?= $meta['IDMUVICORE_Trailer'] ?>"></iframe>
    </div>
    <?php endif; ?>

    <div class="box">
        <h3>Taxonomy</h3>
        <?php foreach ($all_tax as $tax => $items): ?>
            <b><?= $tax ?>:</b>
            <?php
            $names = array_map(fn($i) => $i['name'], $items);
            echo implode(', ', $names);
            ?>
            <br>
        <?php endforeach; ?>
    </div>

    <div class="box">
        <h3>Meta</h3>
        <pre><?php print_r($meta); ?></pre>
    </div>

    <div class="box">
        <a href="index.php">⬅ Kembali</a>
    </div>

    </body>
    </html>

    <?php
    exit;
}

// =====================
// 🔥 MODE LIST
// =====================

$args = [
    'post_type' => ['post','tv'],
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'no_found_rows' => false, // ⚠️ ini harus false untuk pagination
    'paged' => $paged
];

// filter kategori
if (!empty($cat_slug)) {
    $args['category_name'] = $cat_slug;
}

$query = new WP_Query($args);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Film</title>
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
<div style="padding:20px;">
    <b>Kategori:</b>

    <a href="index.php" style="margin-right:10px;">Semua</a>

    <?php foreach ($categories as $cat): ?>
        <a href="?cat=<?= $cat->slug ?>"
           style="margin-right:10px; <?= ($cat_slug == $cat->slug ? 'color:yellow;' : '') ?>">
           <?= $cat->name ?>
        </a>
    <?php endforeach; ?>
</div>
<div class="grid">
<?php while ($query->have_posts()): $query->the_post(); ?>
    <div class="item">
        <a href="?id=<?php the_ID(); ?>">
            <img src="<?= get_the_post_thumbnail_url(get_the_ID(), 'medium'); ?>">
            <div><?= get_the_title(); ?></div>
        </a>
    </div>
<?php endwhile; wp_reset_postdata(); ?>
</div>
<div style="padding:20px; text-align:center;">

<?php if ($paged > 1): ?>
    <a href="?cat=<?= $cat_slug ?>&page=<?= $paged - 1 ?>">⬅ Prev</a>
<?php endif; ?>

<?php
$total_pages = $query->max_num_pages;

for ($i = 1; $i <= $total_pages; $i++):
?>
    <a href="?cat=<?= $cat_slug ?>&page=<?= $i ?>"
       style="margin:5px; <?= ($i == $paged ? 'color:yellow;' : '') ?>">
       <?= $i ?>
    </a>
<?php endfor; ?>

<?php if ($paged < $total_pages): ?>
    <a href="?cat=<?= $cat_slug ?>&page=<?= $paged + 1 ?>">Next ➡</a>
<?php endif; ?>

</div>
</body>
</html>