<?php
/**
 * WooCommerce Marketplace.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Contains backend logic for the Marketplace feature.
 */
class Marketplace {

	const MARKETPLACE_TAB_SLUG = 'woo';

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 */
	final public function init() {
		if ( FeaturesUtil::feature_is_enabled( 'marketplace' ) ) {
			add_action( 'admin_menu', array( $this, 'register_pages' ), 70 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Add a Woo Marketplace link to the plugin install action links.
			add_filter( 'install_plugins_tabs', array( $this, 'add_woo_plugin_install_action_link' ) );
			add_action( 'install_plugins_pre_woo', array( $this, 'maybe_open_woo_tab' ) );
			add_action( 'admin_print_styles-plugin-install.php', array( $this, 'add_plugins_page_styles' ) );
		}
	}

	/**
	 * Registers report pages.
	 */
	public function register_pages() {
		if ( ! function_exists( 'wc_admin_register_page' ) ) {
			return;
		}

		$marketplace_pages = self::get_marketplace_pages();
		foreach ( $marketplace_pages as $marketplace_page ) {
			if ( ! is_null( $marketplace_page ) ) {
				wc_admin_register_page( $marketplace_page );
			}
		}
	}

	/**
	 * Get report pages.
	 */
	public static function get_marketplace_pages() {
		$marketplace_pages = array(
			array(
				'id'     => 'woocommerce-marketplace',
				'parent' => 'woocommerce',
				'title'  => __( 'Extensions', 'woocommerce' ),
				'path'   => '/extensions',
			),
		);

		/**
		 * The marketplace items used in the menu.
		 *
		 * @since 8.0
		 */
		return apply_filters( 'woocommerce_marketplace_menu_items', $marketplace_pages );
	}

	/**
	 * Enqueue update script.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( 'woocommerce_page_wc-admin' !== $hook_suffix ) {
			return;
		};

		if ( ! isset( $_GET['path'] ) || '/extensions' !== $_GET['path'] ) {
			return;
		}

		// Enqueue WordPress updates script to enable plugin and theme installs and updates.
		wp_enqueue_script( 'updates' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Add a Woo Marketplace link to the plugin install action links.
	 *
	 * @param array $tabs Plugins list tabs.
	 * @return array
	 */
	public function add_woo_plugin_install_action_link( $tabs ) {
		$tabs[ self::MARKETPLACE_TAB_SLUG ] = 'Woo';
		return $tabs;
	}

	/**
	 * Open the Woo tab when the user clicks on the Woo link in the plugin installer.
	 */
	public function maybe_open_woo_tab() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['tab'] ) || self::MARKETPLACE_TAB_SLUG !== $_GET['tab'] ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$woo_url = add_query_arg(
			array(
				'page' => 'wc-admin',
				'path' => '/extensions',
				'tab'  => 'extensions',
				'ref'  => 'plugins',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $woo_url );
		exit;
	}

	/**
	 * Add styles to the plugin install page.
	 */
	public function add_plugins_page_styles() {
		?>
		<style>
			.plugin-install-woo > a::after {
				content: "";
				display: inline-block;
				background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8.33321 3H12.9999V7.66667H11.9999V4.70711L8.02009 8.68689L7.31299 7.97978L11.2928 4H8.33321V3Z' fill='%23646970'/%3E%3Cpath d='M6.33333 4.1665H4.33333C3.8731 4.1665 3.5 4.5396 3.5 4.99984V11.6665C3.5 12.1267 3.8731 12.4998 4.33333 12.4998H11C11.4602 12.4998 11.8333 12.1267 11.8333 11.6665V9.6665' stroke='%23646970'/%3E%3C/svg%3E%0A");
				width: 16px;
				height: 16px;
				background-repeat: no-repeat;
				vertical-align: text-top;
				margin-left: 2px;
			}
			.plugin-install-woo:hover > a::after {
				background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8.33321 3H12.9999V7.66667H11.9999V4.70711L8.02009 8.68689L7.31299 7.97978L11.2928 4H8.33321V3Z' fill='%23135E96'/%3E%3Cpath d='M6.33333 4.1665H4.33333C3.8731 4.1665 3.5 4.5396 3.5 4.99984V11.6665C3.5 12.1267 3.8731 12.4998 4.33333 12.4998H11C11.4602 12.4998 11.8333 12.1267 11.8333 11.6665V9.6665' stroke='%23135E96'/%3E%3C/svg%3E%0A");
			}
		</style>
		<?php
	}
}
