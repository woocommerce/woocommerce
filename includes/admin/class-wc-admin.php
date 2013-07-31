<?php
/**
 * WooCommerce Admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Admin' ) ) :

/**
 * @todo Load all pages conditonally when needed from this class
 */
class WC_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'init', array( $this, 'includes' ) );
		add_filter( 'current_screen', array( $this, 'conditonal_includes' ) );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {

		/**
		 * Functions for the shop_coupon post type
		 */
		include_once( 'post-types/shop_coupon.php' );


		/**
		 * Hooks in admin
		 */
		include_once( 'woocommerce-admin-hooks.php' );

		/**
		 * Functions in admin
		 */
		include_once( 'woocommerce-admin-functions.php' );

		// Functions
		include( 'wc-admin-functions.php' );

		// Classes
		include( 'class-wc-admin-help.php' );
		include( 'class-wc-admin-menus.php' );
		include( 'class-wc-admin-welcome.php' );
		include( 'class-wc-admin-notices.php' );
		include( 'class-wc-admin-assets.php' );
		include( 'class-wc-admin-permalink-settings.php' );
		include( 'class-wc-admin-post-types.php' );
		include( 'class-wc-admin-taxonomies.php' );

		// Importers
		if ( defined( 'WP_LOAD_IMPORTERS' ) )
			include( 'class-wc-admin-importers.php' );
	}

	public function conditonal_includes() {
		$screen = get_current_screen();

		switch ( $screen->id ) {
			case 'dashboard' :
				include( 'class-wc-admin-dashboard.php' );
			break;
			case 'users' :
			case 'user' :
			case 'profile' :
				include( 'class-wc-admin-profile.php' );
			break;
		}
	}

}

endif;

return new WC_Admin();