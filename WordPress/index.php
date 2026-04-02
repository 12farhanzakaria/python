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
// TMDB FUNCTION
// =====================
function get_tmdb($tmdb_id) {

    if (!$tmdb_id) return [];

    $api_key = '6b4357c41d9c606e4d7ebe2f4a8850ea';

    $url_movie = "https://api.themoviedb.org/3/movie/$tmdb_id?api_key=$api_key&language=id-ID&append_to_response=credits,videos,similar";
    $url_tv    = "https://api.themoviedb.org/3/tv/$tmdb_id?api_key=$api_key&language=id-ID&append_to_response=credits,videos,similar";

    $res = wp_remote_get($url_movie);

    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
        $res = wp_remote_get($url_tv);
    }

    if (is_wp_error($res)) return [];

    return json_decode(wp_remote_retrieve_body($res), true);
}

// =====================
// FILTER COUNT HELPER
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
        'taxonomy'=>$taxonomy,
        'hide_empty'=>false,
        'object_ids'=>$object_ids
    ]);

    $out = [];
    foreach ($terms as $t) {
        if ($t->count > 0) $out[] = $t;
    }

    return $out;
}

// =====================
// DETAIL
// =====================
if ($id) {

    $post = get_post($id);
    if (!$post) exit('Not found');

    $meta = get_post_meta($id);
    $tmdb_id = $meta['IDMUVICORE_tmdbID'][0] ?? '';

    $tmdb = get_tmdb($tmdb_id);

    // =====================
    // DEBUG TMDB
    // =====================
    if (!$tmdb) {

        echo "<h3>DEBUG TMDB</h3>";
        echo "TMDB ID: ".esc_html($tmdb_id)."<br>";

        if (!$tmdb_id) {
            echo "❌ TMDB ID kosong<br>";
        } else {

            $api_key = 'ISI_API_KEY_KAMU';
            $test_url = "https://api.themoviedb.org/3/movie/$tmdb_id?api_key=$api_key";

            echo "<a href='$test_url' target='_blank'>$test_url</a><br>";

            $res = wp_remote_get($test_url);

            if (is_wp_error($res)) {
                echo "❌ ".$res->get_error_message();
            } else {
                echo "HTTP: ".wp_remote_retrieve_response_code($res)."<br>";
                echo "<pre>";
                echo esc_html(substr(wp_remote_retrieve_body($res),0,500));
                echo "</pre>";
            }
        }

        echo "<hr>";
    }

    // =====================
    // TMDB OUTPUT
    // =====================
    if ($tmdb) {

        if (!empty($tmdb['backdrop_path'])) {
            echo "<img src='https://image.tmdb.org/t/p/original{$tmdb['backdrop_path']}'><br><br>";
        }

        echo "<h1>".($tmdb['title'] ?? $tmdb['name'])."</h1>";

        echo "Tahun: ".substr($tmdb['release_date'] ?? $tmdb['first_air_date'],0,4)."<br>";
        echo "Rating: {$tmdb['vote_average']} ({$tmdb['vote_count']})<br>";

        if (!empty($tmdb['genres'])) {
            echo "Genre: ".implode(', ', array_column($tmdb['genres'],'name'))."<br>";
        }

        if (!empty($tmdb['credits']['cast'])) {
            echo "<h3>Cast</h3>";
            foreach (array_slice($tmdb['credits']['cast'],0,5) as $c) {
                echo $c['name']."<br>";
            }
        }

        if (!empty($tmdb['overview'])) {
            echo "<h3>Sinopsis</h3>";
            echo "<p>".$tmdb['overview']."</p>";
        }

        if (!empty($tmdb['videos']['results'])) {
            foreach ($tmdb['videos']['results'] as $v) {
                if ($v['site']==='YouTube') {
                    echo "<iframe width='100%' height='300' src='https://www.youtube.com/embed/".$v['key']."'></iframe>";
                    break;
                }
            }
        }
    }

    // =====================
    // DRIVE
    // =====================
    $drive_links = [];

    foreach ($meta as $v) {
        if (!empty($v[0]) && strpos($v[0],'drive.google.com') !== false) {
            preg_match_all('/https?:\/\/[^\s"]*drive\.google\.com[^\s"]*/',$v[0],$m);
            if (!empty($m[0])) {
                $drive_links = array_merge($drive_links,$m[0]);
            }
        }
    }

    preg_match_all('/https?:\/\/[^\s"]*drive\.google\.com[^\s"]*/',$post->post_content,$m);
    if (!empty($m[0])) {
        $drive_links = array_merge($drive_links,$m[0]);
    }

    $drive_links = array_unique($drive_links);

    echo "<h3>Link Drive</h3>";
    foreach ($drive_links as $i => $link) {
        echo "<a href='$link' target='_blank'>Link ".($i+1)."</a><br>";
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
];

if ($search) $args['s']=$search;
if ($category) $args['cat']=$category;

if ($year) {
    $args['meta_query'][] = [
        'key'=>'year',
        'value'=>$year
    ];
}

if ($sort==='views') {
    $args['meta_key']='views';
    $args['orderby']='meta_value_num';
} else {
    $args['orderby']='date';
}

// =====================
// FILTER
// =====================
echo "<form method='get'>";
echo "<input name='search' value='".esc_attr($search)."'> ";

$cats = get_categories(['hide_empty'=>true]);
echo "<select name='category'><option value=''>All</option>";
foreach ($cats as $c) {
    $sel = $category==$c->term_id?'selected':'';
    echo "<option value='{$c->term_id}' $sel>{$c->name}</option>";
}
echo "</select> ";

echo "<input name='year' value='".esc_attr($year)."' placeholder='Year'> ";
echo "<button>Filter</button>";
echo "</form><br>";

// =====================
// LIST
// =====================
$q = new WP_Query($args);

while ($q->have_posts()) {
    $q->the_post();

    $pid = get_the_ID();
    $thumb = get_the_post_thumbnail_url($pid,'thumbnail');

    echo "<div>";

    if ($thumb) {
        echo "<a href='".url(current_params()+['id'=>$pid])."'>";
        echo "<img src='".esc_url($thumb)."' width='120'>";
        echo "</a><br>";
    }

    echo "<a href='".url(current_params()+['id'=>$pid])."'><b>".esc_html(get_the_title())."</b></a><br>";
    echo "<small>".get_the_date('Y')."</small>";

    echo "</div><br>";
}

wp_reset_postdata();