<?php
/**
 * Returns true if we are on a JS powered admin page.
 */
function wc_admin_is_admin_page() {
	global $hook_suffix;
	if ( in_array( $hook_suffix, array( 'woocommerce_page_wc-admin' ) ) ) {
		return true;
	}
	return false;
}

/**
 * Returns true if we are on a "classic" (non JS app) powered admin page.
 * `wc_get_screen_ids` will also return IDs for extensions that have properly registered themselves.
 */
function wc_admin_is_embed_enabled_wc_page() {
	$screen_id = wc_admin_get_current_screen_id();
	if ( ! $screen_id ) {
		return false;
	}

	$screens = wc_admin_get_embed_enabled_screen_ids();

	if ( in_array( $screen_id, $screens ) ) {
		return true;
	}
	return false;
}

/**
 * Register menu pages for the Dashboard and Analytics sections
 */
function wc_admin_register_pages(){
	global $menu, $submenu;

	// woocommerce_page_wc-admin
	add_submenu_page(
		'woocommerce',
		__( 'WooCommerce Dashboard', 'wc-admin' ),
		__( 'Dashboard', 'wc-admin' ),
		'manage_options',
		'wc-admin',
		'wc_admin_page'
	);


	// toplevel_page_wooanalytics
	add_menu_page(
		__( 'WooCommerce Analytics', 'wc-admin' ),
		__( 'Analytics', 'wc-admin' ),
		'manage_options',
		'wc-admin#/analytics',
		'wc_admin_page',
		'dashicons-chart-bar',
		56 // After WooCommerce & Product menu items
	);

	// TODO: Remove. Test report link
	add_submenu_page(
		'wc-admin#/analytics',
		__( 'Report Title', 'wc-admin' ),
		__( 'Report Title', 'wc-admin' ),
		'manage_options',
		'wc-admin#/analytics/test',
		'wc_admin_page'
	);

	add_submenu_page(
		'wc-admin#/analytics',
		__( 'Revenue', 'wc-admin' ),
		__( 'Revenue', 'wc-admin' ),
		'manage_options',
		'wc-admin#/analytics/revenue',
		'wc_admin_page'
	);
}
add_action( 'admin_menu', 'wc_admin_register_pages' );

/**
 * This method is temporary while this is a feature plugin. As a part of core,
 * we can integrate this better with wc-admin-menus.
 *
 * It makes dashboard the top level link for 'WooCommerce' and renames the first Analytics menu item.
 */
function wc_admin_link_structure() {
	global $submenu;
	// User does not have capabilites to see the submenu
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$wc_admin_key = null;
	foreach ( $submenu['woocommerce'] as $submenu_key => $submenu_item ) {
		if ( 'wc-admin' === $submenu_item[2] ) {
			$wc_admin_key = $submenu_key;
			break;
		}
	}

	if ( ! $wc_admin_key ) {
		return;
	}

	$menu    = $submenu['woocommerce'][ $wc_admin_key ];
	$menu[2] = 'admin.php?page=wc-admin#/';
	unset( $submenu['woocommerce'][ $wc_admin_key ] );

	array_unshift( $submenu['woocommerce'], $menu );
	$submenu['wc-admin#/analytics'][0][0] = __( 'Overview', 'wc-admin' );
}

// priority is 20 to run after https://github.com/woocommerce/woocommerce/blob/a55ae325306fc2179149ba9b97e66f32f84fdd9c/includes/admin/class-wc-admin-menus.php#L165
add_action( 'admin_head', 'wc_admin_link_structure', 20 );

/**
 * Load the assets on the admin pages
 */
function wc_admin_enqueue_script(){
	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return;
	}

	wp_enqueue_script( WC_ADMIN_APP );
	wp_enqueue_style( WC_ADMIN_APP );
}
add_action( 'admin_enqueue_scripts', 'wc_admin_enqueue_script' );

function wc_admin_admin_body_class( $admin_body_class = '' ) {
	global $hook_suffix;

	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return $admin_body_class;
	}

	$classes = explode( ' ', trim( $admin_body_class ) );
	$classes[] = 'woocommerce-page';
	if ( wc_admin_is_embed_enabled_wc_page() ) {
		$classes[] = 'woocommerce-embed-page';
	}
	$admin_body_class = implode( ' ', array_unique( $classes ) );
	return " $admin_body_class ";
}
add_filter( 'admin_body_class', 'wc_admin_admin_body_class' );


function wc_admin_admin_before_notices() {
	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return;
	}
	echo '<div class="woocommerce-layout__notice-list-hide" id="wp__notice-list">';
	echo '<div class="wp-header-end" id="woocommerce-layout__notice-catcher"></div>'; // https://github.com/WordPress/WordPress/blob/f6a37e7d39e2534d05b9e542045174498edfe536/wp-admin/js/common.js#L737
}
add_action( 'admin_notices', 'wc_admin_admin_before_notices', 0 );

function wc_admin_admin_after_notices() {
	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return;
	}
	echo '</div>';
}
add_action( 'admin_notices', 'wc_admin_admin_after_notices', PHP_INT_MAX );

// TODO Can we do some URL rewriting so we can figure out which page they are on server side?
function wc_admin_admin_title( $admin_title ) {
	if ( ! wc_admin_is_admin_page() && ! wc_admin_is_embed_enabled_wc_page() ) {
		return $admin_title;
	}

	if ( wc_admin_is_embed_enabled_wc_page() ) {
		$sections = wc_admin_get_embed_breadcrumbs();
		$sections = is_array( $sections ) ? $sections : array( $sections );
		$pieces   = array();

		foreach ( $sections as $section ) {
			$pieces[] = is_array( $section ) ? $section[ 1 ] : $section;
		}

		$pieces = array_reverse( $pieces );
		$title = implode( ' &lsaquo; ', $pieces );
	} else {
		$title = __( 'Dashboard', 'wc-admin' );
	}

	return sprintf( __( '%1$s &lsaquo; %2$s &#8212; WooCommerce', 'wc-admin' ), $title, get_bloginfo( 'name' ) );
}
add_filter( 'admin_title',  'wc_admin_admin_title' );

/**
 * Set up a div for the app to render into.
 */
function wc_admin_page(){
	?>
	<div class="wrap">
		<div id="root"></div>
	</div>
<?php
}

/**
 * Set up a div for the header embed to render into.
 * The initial contents here are meant as a place loader for when the PHP page initialy loads.

 * TODO Icon Placeholders for the ActivityPanel, when we implement the new designs.
 */
function woocommerce_embed_page_header() {
	if ( ! wc_admin_is_embed_enabled_wc_page() ) {
		return;
	}

	$sections    = wc_admin_get_embed_breadcrumbs();
	$sections    = is_array( $sections ) ? $sections : array( $sections );
	$breadcrumbs = '';
	foreach ( $sections as $section ) {
		$piece = is_array( $section ) ? '<a href="' . esc_url( admin_url( $section[ 0 ] ) ) .'">' . $section[ 1 ] . '</a>' : $section;
		$breadcrumbs .= '<span>' . $piece . '</span>';
	}
	?>
	<div id="woocommerce-embedded-root">
		<div class="woocommerce-layout">
			<div class="woocommerce-layout__header is-embed-loading">
				<h1 class="woocommerce-layout__header-breadcrumbs">
					<span><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-admin#/' ) ); ?>">WooCommerce</a></span>
					<?php echo $breadcrumbs; ?>
				</h1>
			</div>
		</div>
		<div class="woocommerce-layout__primary is-embed-loading" id="woocommerce-layout__primary">
			<div id="woocommerce-layout__notice-list" class="woocommerce-layout__notice-list"></div>
		</div>
	</div>
	<?php
}

add_action( 'in_admin_header', 'woocommerce_embed_page_header' );
