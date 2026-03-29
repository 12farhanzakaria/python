<?php
/**
 * Muvipro functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Muvipro
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'gmr_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 *
	 * @since v.1.0.0
	 */
	function gmr_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on Muvipro, use a find and replace
		 * to change 'muvipro' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'muvipro', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * See https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		add_image_size( 'blog-large', 430, 250, true );
		add_image_size( 'blog-small', 60, 60, true );

		// Add hardcrop in medium and large image.
		add_image_size( 'medium', get_option( 'medium_size_w' ), get_option( 'medium_size_h' ), true );
		add_image_size( 'large', get_option( 'large_size_w' ), get_option( 'large_size_h' ), true );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus(
			array(
				'primary'   => esc_html__( 'Primary', 'muvipro' ),
				'secondary' => esc_html__( 'Secondary', 'muvipro' ),
				'topnav'    => esc_html__( 'Top Navigation', 'muvipro' ),
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);

		/*
		 * RECOMMENDED: No reference to add_editor_style()
		 * Not usefull in theme, just pass theme checker plugin
		 */
		add_editor_style( 'editor-style.css' );

		// Set up the WordPress core custom background feature.
		add_theme_support(
			'custom-background',
			apply_filters(
				'muvipro_custom_background_args',
				array(
					'default-color' => 'eeeeee',
					'default-image' => '',
				)
			)
		);

		/**
		 * Sample implementation of the Custom Header feature.
		 *
		 * You can add an optional custom header image to header.php like so ...
		 *
			<?php if ( get_header_image() ) : ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<img src="<?php header_image(); ?>" width="<?php echo esc_attr( get_custom_header()->width ); ?>" height="<?php echo esc_attr( get_custom_header()->height ); ?>" alt="">
			</a>
			<?php endif; // End header image check. ?>
		 *
		 * @link https://developer.wordpress.org/themes/functionality/custom-headers/
		 *
		 * @since v.1.0.0
		 */

		add_theme_support(
			'custom-header',
			apply_filters(
				'muvipro_custom_header_args',
				array(
					'width'       => 1400,
					'height'      => 100,
					'flex-height' => true,
					'flex-width'  => true,
					'uploads'     => true,
					'header-text' => false,
				)
			)
		);
	}
endif; // endif gmr_setup.
add_action( 'after_setup_theme', 'gmr_setup' );

if ( ! function_exists( 'gmr_width_size_image' ) ) :
	/**
	 * Improve performance, it's mean, only when switch theme this functions is active.
	 *
	 * @since v.1.0.0
	 */
	function gmr_width_size_image() {
		// Thumbnail Size Thumbnail.
		update_option( 'thumbnail_size_w', 60 );
		update_option( 'thumbnail_size_h', 90 );
		// force hard crop medium size thumbnail.
		update_option( 'thumbnail_crop', 1 );

		// Medium Size Thumbnail.
		update_option( 'medium_size_w', 152 );
		update_option( 'medium_size_h', 228 );
		// force hard crop medium size thumbnail.
		update_option( 'medium_crop', '1' );

		// Large Size Thumbnail.
		update_option( 'large_size_w', 170 );
		update_option( 'large_size_h', 255 );
		// force hard crop large size thumbnail.
		update_option( 'large_crop', '1' );
	}
endif; // endif gmr_width_size_image.
add_action( 'after_switch_theme', 'gmr_width_size_image' );

if ( ! function_exists( 'gmr_content_width' ) ) :
	/**
	 * Set the content width in pixels, based on the theme's design and stylesheet.
	 *
	 * Priority 0 to make it available to lower priority callbacks.
	 *
	 * @since v.1.0.0
	 *
	 * @global int $content_width
	 */
	function gmr_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'gmr_content_width', 1140 );
	}
endif; // endif gmr_content_width.
add_action( 'after_setup_theme', 'gmr_content_width', 0 );

if ( ! function_exists( 'gmr_widgets_init' ) ) :
	/**
	 * Register widget area.
	 *
	 * @since v.1.0.0
	 *
	 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
	 */
	function gmr_widgets_init() {
		// Sidebar widget areas.
		register_sidebar(
			array(
				'name'          => esc_html__( 'Sidebar', 'muvipro' ),
				'id'            => 'sidebar-1',
				'description'   => esc_html__( 'Add widgets here.', 'muvipro' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h3 class="widget-title">',
				'after_title'   => '</h3>',
			)
		);
		// Homepage module.
		register_sidebar(
			array(
				'name'          => esc_html__( 'Home Module', 'muvipro' ),
				'id'            => 'home-module',
				'description'   => esc_html__( 'Add module here.', 'muvipro' ),
				'before_widget' => '<div id="%1$s" class="home-widget widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h3 class="homemodule-title">',
				'after_title'   => '</h3>',
			)
		);
		// Footer widget areas.
		$mod = get_theme_mod( 'gmr_footer_column', '3col' );
		if ( '4col' === $mod ) {
			$number = 4;
		} elseif ( '1col' === $mod ) {
			$number = 1;
		} elseif ( '2col' === $mod ) {
			$number = 2;
		} else {
			$number = 3;
		}
		for ( $i = 1; $i <= $number; $i++ ) {
			register_sidebar(
				array(
					'name'          => esc_html__( 'Footer ', 'muvipro' ) . $i,
					'id'            => 'footer-' . $i,
					'description'   => '',
					'before_widget' => '<div id="%1$s" class="widget %2$s">',
					'after_widget'  => '</div>',
					'before_title'  => '<h3 class="widget-title">',
					'after_title'   => '</h3>',
				)
			);
		}

	}
endif; // endif gmr_widgets_init.
add_action( 'widgets_init', 'gmr_widgets_init' );

if ( ! function_exists( 'gmr_scripts' ) ) :
	/**
	 * Enqueue scripts and styles.
	 */
	function gmr_scripts() {
		// Font options.
		$fonts = array(
			get_theme_mod( 'gmr_primary-font', customizer_library_get_default( 'gmr_primary-font' ) ),
			get_theme_mod( 'gmr_secondary-font', customizer_library_get_default( 'gmr_secondary-font' ) ),
		);

		$font_uri = customizer_library_get_google_font_uri( $fonts );

		// Load Google Fonts.
		wp_enqueue_style( 'muvipro-fonts', $font_uri, array(), '1.0.0', 'all' );

		// Call if only woocommerce actived.
		if ( class_exists( 'WooCommerce' ) ) {
			// Custom Woocommerce CSS.
			wp_enqueue_style( 'muvipro-woocommerce', get_template_directory_uri() . '/css/woocommerce.css', array(), '1.0.0', 'all' );
		}

		// Add stylesheet.
		wp_enqueue_style( 'muvipro-style', get_stylesheet_uri(), array(), '1.0.0', 'all' );

		global $is_IE;
		if ( $is_IE ) {
			// Modernizr Jquery.
			wp_enqueue_script( 'muvipro-modernizr', get_template_directory_uri() . '/js/modernizr-custom.js', array( 'jquery' ), '1.0.0', true );
		}

		// Jquery Plugin.
		wp_enqueue_script( 'muvipro-jqueryplugin', get_template_directory_uri() . '/js/jquery-plugin-min.js', array( 'jquery' ), '1.0.0', true );

		// theiaStickySidebar setting.
		$sticky_sidebar = get_theme_mod( 'gmr_active-sticky-sidebar', 0 );
		if ( 0 === $sticky_sidebar ) {
			// theia-sticky-sidebar Jquery.
			wp_enqueue_script( 'muvipro-sticky-sidebar', get_template_directory_uri() . '/js/theia-sticky-sidebar-min.js', array( 'jquery' ), '1.0.0', true );
		}

		// Custom script.
		wp_enqueue_script( 'muvipro-customscript', get_template_directory_uri() . '/js/customscript.js', array( 'jquery' ), '1.0.0', true );

		// Ajax player.
		if ( is_singular() ) {
			$player_style = get_theme_mod( 'gmr_player_style', 'ajax' );
			if ( 'subpage' === $player_style ) {
			} else {
				global $post;
				$player1  = get_post_meta( $post->ID, 'IDMUVICORE_Player1', true );
				$player2  = get_post_meta( $post->ID, 'IDMUVICORE_Player2', true );
				$player3  = get_post_meta( $post->ID, 'IDMUVICORE_Player3', true );
				$player4  = get_post_meta( $post->ID, 'IDMUVICORE_Player4', true );
				$player5  = get_post_meta( $post->ID, 'IDMUVICORE_Player5', true );
				$player6  = get_post_meta( $post->ID, 'IDMUVICORE_Player6', true );
				$player7  = get_post_meta( $post->ID, 'IDMUVICORE_Player7', true );
				$player8  = get_post_meta( $post->ID, 'IDMUVICORE_Player8', true );
				$player9  = get_post_meta( $post->ID, 'IDMUVICORE_Player9', true );
				$player10 = get_post_meta( $post->ID, 'IDMUVICORE_Player10', true );
				$player11 = get_post_meta( $post->ID, 'IDMUVICORE_Player11', true );
				$player12 = get_post_meta( $post->ID, 'IDMUVICORE_Player12', true );
				$player13 = get_post_meta( $post->ID, 'IDMUVICORE_Player13', true );
				$player14 = get_post_meta( $post->ID, 'IDMUVICORE_Player14', true );
				$player15 = get_post_meta( $post->ID, 'IDMUVICORE_Player15', true );
				if ( ! empty( $player1 )
				|| ! empty( $player2 )
				|| ! empty( $player3 )
				|| ! empty( $player4 )
				|| ! empty( $player5 )
				|| ! empty( $player6 )
				|| ! empty( $player7 )
				|| ! empty( $player8 )
				|| ! empty( $player9 )
				|| ! empty( $player10 )
				|| ! empty( $player11 )
				|| ! empty( $player12 )
				|| ! empty( $player13 )
				|| ! empty( $player14 )
				|| ! empty( $player15 )
				) {
					wp_enqueue_script( 'muvipro-ajax-player', get_template_directory_uri() . '/js/ajax-player.js', array( 'jquery' ), '1.0.0', true );
					wp_localize_script(
						'muvipro-ajax-player',
						'mvpp',
						array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
					);
				}
			}
		}

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
endif; // endif gmr_scripts.
add_action( 'wp_enqueue_scripts', 'gmr_scripts' );

/**
 * Include the TGM_Plugin_Activation class.
 *
 * Depending on your implementation, you may want to change the include call:
 *
 * Parent Theme:
 * require_once get_template_directory() . '/path/to/class-tgm-plugin-activation.php';
 *
 * Child Theme:
 * require_once get_stylesheet_directory() . '/path/to/class-tgm-plugin-activation.php';
 *
 * Plugin:
 * require_once dirname( __FILE__ ) . '/path/to/class-tgm-plugin-activation.php';
 *
 * @since v.1.0.0
 */
require_once get_template_directory() . '/inc/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'muvipro_register_required_plugins' );

/**
 * Register the required plugins for this theme.
 *
 * In this example, we register five plugins:
 * - one included with the TGMPA library
 * - two from an external source, one from an arbitrary source, one from a GitHub repository
 * - two from the .org repo, where one demonstrates the use of the `is_callable` argument
 *
 * The variables passed to the `tgmpa()` function should be:
 * - an array of plugin arrays;
 * - optionally a configuration array.
 * If you are not changing anything in the configuration array, you can remove the array and remove the
 * variable from the function call: `tgmpa( $plugins );`.
 * In that case, the TGMPA default settings will be used.
 *
 * This function is hooked into `tgmpa_register`, which is fired on the WP `init` action on priority 10
 *
 * @since v.1.0.0
 */
function muvipro_register_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(

		// Include One Click Demo Import from the WordPress Plugin Repository.
		array(
			'name'     => 'One Click Demo Import',
			'slug'     => 'one-click-demo-import',
			'required' => true,
		),

		// This is an include a plugin bundled with a theme.
		array(
			'name'     => 'Idmuvi Core', // The plugin name.
			'slug'     => 'idmuvi-core', // The plugin slug (typically the folder name).
			'source'   => 'https://www.dropbox.com/s/frggtnz73uonp22/idmuvi-core.zip?dl=1', // The plugin source.
			'required' => true, // If false, the plugin is only 'recommended' instead of required.
		),

		// This is an include a plugin bundled with a theme.
		array(
			'name'     => 'WP PostViews', // The plugin name.
			'slug'     => 'wp-postviews',
			'required' => true,
		),

	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
	 * strings available, please help us make TGMPA even better by giving us access to these translations or by
	 * sending in a pull-request with .po file(s) with the translations.
	 */
	$config = array(
		'id'           => 'muvipro',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => true,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.
	);

	tgmpa( $plugins, $config );
}

if ( ! function_exists( 'muvipro_fb_comments_template' ) ) :
	/**
	 * Add facebook comments
	 *
	 * @since v.1.0.0
	 */
	function muvipro_fb_comments_template() {
		global $post;
		$fb_comment = get_theme_mod( 'gmr_comment', 'default-comment' );
		if ( ! ( is_singular() && ( have_comments() || 'open' === $post->comment_status ) ) ) {
			return;
		}
		if ( 'fb-comment' === $fb_comment ) {
			return get_template_directory() . '/inc/fb-comment.php';
		}
	}
endif;

$fb_comment = get_theme_mod( 'gmr_comment', 'default-comment' );

if ( 'fb-comment' === $fb_comment ) {
	add_filter( 'comments_template', 'muvipro_fb_comments_template', 20 );
}


/**
 * Custom template tags for this theme.
 *
 * @since v.1.0.0
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 *
 * @since v.1.0.0
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Custom Breadcrumb.
 *
 * @since v.1.0.0
 */
require get_template_directory() . '/inc/breadcrumbs.php';

/**
 * Slider
 *
 * @since v.1.0.0
 */
require get_template_directory() . '/inc/slider.php';

/**
 * Customizer additions.
 *
 * @since v.1.0.0
 */
require get_template_directory() . '/inc/customizer-library/customizer-library.php';
/* Custom options customizer */
require get_template_directory() . '/inc/customizer-library/gmrtheme-customizer.php';

/**
 * Load Jetpack compatibility file.
 *
 * @since v.1.0.0
 */
require get_template_directory() . '/inc/jetpack.php';

/**
 * Include related post in theme.
 *
 * @since v.2.0.0
 */
require get_template_directory() . '/inc/relatedpost.php';

/**
 * Add metabox to post or page
 *
 * @since v.1.0.0
 */
require get_template_directory() . '/inc/metabox.php';

/**
 * Load Theme Update Checker.
 *
 * @since v.1.0.0
 */
require get_template_directory() . '/inc/theme-update-checker.php';

/**
 * Call only if woocommerce actived
 *
 * @since v.1.0.0
 */
if ( class_exists( 'WooCommerce' ) ) {
	/**
	 * Load Woocommerce compatibility file.
	 *
	 * @since v.1.0.0
	 */
	require get_template_directory() . '/inc/woocommerce.php';
}

/**
 * Call only if One Click Demo Import actived
 *
 * @since v.1.0.0
 */
if ( class_exists( 'OCDI_Plugin' ) ) {
	/**
	 * Load One Click Demo Import
	 *
	 * @since v.1.0.0
	 */
	require get_template_directory() . '/inc/importer.php';
}

if ( class_exists( 'bbPress' ) ) {
	/**
	 * Load BBpress function
	 *
	 * @since v.1.0.0
	 */
	require get_template_directory() . '/inc/bbpress.php';
}

/**
 * Load All Widget For module homepage
 *
 * @since v.1.0.0
 */
//require get_template_directory() . '/inc/widgets/module-posts-widget.php';
require get_template_directory() . '/inc/widgets/module-episodes-widget.php';
require get_template_directory() . '/inc/widgets/tags-widget.php';

// Disable wp gutenberg, we will work until custom taxonomy fix in gutenberg, currently imposible to add automatic post in gutenberg.
add_filter( 'use_block_editor_for_post', '__return_false' );

// tambahan
// function to be executed when a custom post type is published
function mfields_set_default_object_terms( $post_id, $post ) {
    if ( 'publish' === $post->post_status ) {
		$titlex = get_the_title();
        $defaults = array(
            'muviquality' => array( 'HD' )
            );
        $taxonomies = get_object_taxonomies( $post->post_type );
        foreach ( (array) $taxonomies as $taxonomy ) {
            $terms = wp_get_post_terms( $post_id, $taxonomy );
            if ( empty( $terms ) && array_key_exists( $taxonomy, $defaults ) ) {
                wp_set_object_terms( $post_id, $defaults[$taxonomy], $taxonomy );
            }
        }
    }
}
add_action( 'save_post', 'mfields_set_default_object_terms', 100, 2 );

add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) {
        return $result;
    }

    if (!is_user_logged_in() && $_SERVER['REQUEST_URI'] !== "/wp-json/jwt-auth/v1/token" && $_SERVER['REQUEST_URI'] !== "/wp-json/jwt-auth/v1/token/validate") {
        return new WP_Error('rest_not_logged_in', 'You are not currently logged in.', array('status' => 401));
    }
    return $result;
});

// tampilkan episode di admin post
add_filter('manage_posts_columns', 'my_columns');
function my_columns($columns) {
    $columns['episodex'] = 'Episode';
    return $columns;
}
add_action('manage_posts_custom_column',  'my_show_columns');
function my_show_columns($name) {
    global $post;
    switch ($name) {
        case 'episodex':
		if ( 'tv' == get_post_type() ) {
            $episodex = get_post_meta($post->ID, 'episodex', true);
			$episodex = str_replace("Episode ", "", $episodex);
			$episodex = str_replace("EPS ", "", $episodex);
			$episodex = str_replace("EPS", "", $episodex);
            echo $episodex;
		}
    }
}

add_filter('show_admin_bar', '__return_false');

function reset_post_date_wpse_121565($data,$postarr) {
  //var_dump($data,$postarr); die; debug
  $data['post_date'] = $data['post_modified'];
  $data['post_date_gmt'] = $data['post_modified_gmt'];
  return $data;
}
add_filter('wp_insert_post_data','reset_post_date_wpse_121565',99,2);

if ( ! function_exists( 'gmr_new_add_img_title' ) ) :
function gmr_new_add_img_title( $attr, $attachment = null ) {
$post_id = get_the_ID();
						$titlex = get_the_title( $post_id );
						$titlex = strip_tags( $titlex );
						$titlex = str_replace(' jf', '', $titlex);
						$titlex = str_replace(' hd', '', $titlex);
						$titlex = str_replace(' Sub Indo', '', $titlex);
						$titlex = str_replace('Nonton ', '', $titlex);	
$attr['title'] = 'Nonton Film ' . $titlex . ' Sub Indo';
$attr['alt'] = 'Nonton Film ' . $titlex . ' Sub Indo';
return $attr;
}
endif;
add_filter( 'wp_get_attachment_image_attributes','gmr_new_add_img_title', 10, 2 );

// Exclude Erotic Movies From Homepages
function exclude_category( $query ) {
if ( $query->is_home() && $query->is_main_query() ) {
$query->set( 'cat', '-2266' );
}
}
add_action( 'pre_get_posts', 'exclude_category' );
add_filter( 'rest_authentication_errors', function( $result ) {
    if ( ! empty( $result ) ) {
        return $result;
    }
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
    }
    return $result;
});
add_filter( 'press_sync_show_advanced_options', '__return_true' );
#add_filter( 'x_redirect_by', '__return_false' );
remove_filter('template_redirect','redirect_canonical');
setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
if ( SITECOOKIEPATH != COOKIEPATH ) setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);
add_action( 'admin_menu', 'wpdd_remove_menu_items', 999 );

add_action( 'admin_menu', 'my_remove_menu_pages' );


//global variable do
global $do;
$do = get_post_meta( get_the_ID(), 'IDMUVICORE_Player2', true );

//hide plugins menu
add_action('admin_menu', 'hide_plugins_menu');
function hide_plugins_menu() {
    remove_menu_page('plugins.php');
}

add_action('admin_menu', 'hide_themes_menu');
function hide_themes_menu() {
    remove_submenu_page('themes.php', 'themes.php');
}
//add_action('admin_menu', 'hide_general_settings_menu');
//function hide_general_settings_menu() {
//    remove_menu_page('options-general.php');
//}

/**
 * Load AMP Functions
 * Custom AMP implementation for JuraganFilm
 *
 * @since v.2.0.0
 */
require get_template_directory() . '/amp/amp-functions.php';
