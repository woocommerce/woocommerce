<?php
/**
 * Setup menus in WP admin.
 *
 * @package WooCommerce\Admin
 * @version 2.5.0
 */

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\Marketplace;
use Automattic\WooCommerce\Internal\Admin\Orders\COTRedirectionController;
use Automattic\WooCommerce\Internal\Admin\Orders\PageController as Custom_Orders_PageController;
use Automattic\WooCommerce\Internal\Admin\Logging\PageController as LoggingPageController;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\{ FileListTable, SearchListTable };
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Admin_Menus', false ) ) {
	return new WC_Admin_Menus();
}

/**
 * WC_Admin_Menus Class.
 */
class WC_Admin_Menus {

	/**
	 * The CSS classes used to hide the submenu items in navigation.
	 *
	 * @var string
	 */
	const HIDE_CSS_CLASS = 'hide-if-js';

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus.
		add_action( 'admin_menu', array( $this, 'menu_highlight' ) );
		add_action( 'admin_menu', array( $this, 'menu_order_count' ) );
		add_action( 'admin_menu', array( $this, 'maybe_add_new_product_management_experience' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'orders_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'reports_menu' ), 20 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
		add_action( 'admin_menu', array( $this, 'status_menu' ), 60 );

		/**
		 * Controls whether we add a submenu item for the WooCommerce Addons page.
		 * Woo Express uses this filter.
		 *
		 * @since 8.2.1
		 *
		 * @param bool $show_addons_page If the addons page should be included.
		 */
		if ( apply_filters( 'woocommerce_show_addons_page', true ) ) {
			$container = wc_get_container();
			$container->get( Marketplace::class );

			add_action( 'admin_menu', array( $this, 'addons_my_subscriptions' ), 70 );
		}

		add_filter( 'menu_order', array( $this, 'menu_order' ) );
		add_filter( 'custom_menu_order', array( $this, 'custom_menu_order' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );

		// Add endpoints custom URLs in Appearance > Menus > Pages.
		add_action( 'admin_head-nav-menus.php', array( $this, 'add_nav_menu_meta_boxes' ) );

		// Admin bar menus.
		if ( apply_filters( 'woocommerce_show_admin_bar_visit_store', true ) ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 31 );
		}

		// Handle saving settings earlier than load-{page} hook to avoid race conditions in conditional menus.
		add_action( 'wp_loaded', array( $this, 'save_settings' ) );
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		global $menu, $admin_page_hooks;

		$woocommerce_icon = 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDg1LjkgNDcuNiI+CjxwYXRoIGZpbGw9IiNhMmFhYjIiIGQ9Ik03Ny40LDAuMWMtNC4zLDAtNy4xLDEuNC05LjYsNi4xTDU2LjQsMjcuN1Y4LjZjMC01LjctMi43LTguNS03LjctOC41cy03LjEsMS43LTkuNiw2LjVMMjguMywyNy43VjguOAoJYzAtNi4xLTIuNS04LjctOC42LTguN0g3LjNDMi42LDAuMSwwLDIuMywwLDYuM3MyLjUsNi40LDcuMSw2LjRoNS4xdjI0LjFjMCw2LjgsNC42LDEwLjgsMTEuMiwxMC44UzMzLDQ1LDM2LjMsMzguOWw3LjItMTMuNXYxMS40CgljMCw2LjcsNC40LDEwLjgsMTEuMSwxMC44czkuMi0yLjMsMTMtOC43bDE2LjYtMjhjMy42LTYuMSwxLjEtMTAuOC02LjktMTAuOEM3Ny4zLDAuMSw3Ny4zLDAuMSw3Ny40LDAuMXoiLz4KPC9zdmc+Cg==';

		if ( self::can_view_woocommerce_menu_item() ) {
			$menu[] = array( '', 'read', 'separator-woocommerce', '', 'wp-menu-separator woocommerce' ); // WPCS: override ok.
		}

		add_menu_page( __( 'WooCommerce', 'woocommerce' ), __( 'WooCommerce', 'woocommerce' ), 'edit_others_shop_orders', 'woocommerce', null, $woocommerce_icon, '55.5' );

		// Work around https://github.com/woocommerce/woocommerce/issues/35677 (and related https://core.trac.wordpress.org/ticket/18857).
		// Translating the menu item breaks screen IDs and page hooks, so we force the hookname to be untranslated.
		$admin_page_hooks['woocommerce'] = 'woocommerce'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		add_submenu_page( 'edit.php?post_type=product', __( 'Attributes', 'woocommerce' ), __( 'Attributes', 'woocommerce' ), 'manage_product_terms', 'product_attributes', array( $this, 'attributes_page' ) );
	}

	/**
	 * Add menu item.
	 */
	public function reports_menu() {
		if ( self::can_view_woocommerce_menu_item() ) {
			add_submenu_page( 'woocommerce', __( 'Reports', 'woocommerce' ), __( 'Reports', 'woocommerce' ), 'view_woocommerce_reports', 'wc-reports', array( $this, 'reports_page' ) );
		} else {
			add_menu_page( __( 'Sales reports', 'woocommerce' ), __( 'Sales reports', 'woocommerce' ), 'view_woocommerce_reports', 'wc-reports', array( $this, 'reports_page' ), 'dashicons-chart-bar', '55.6' );
		}
	}

	/**
	 * Add menu item.
	 */
	public function settings_menu() {
		$settings_page = add_submenu_page(
			'woocommerce',
			__( 'WooCommerce settings', 'woocommerce' ),
			__( 'Settings', 'woocommerce' ),
			'manage_woocommerce',
			'wc-settings',
			array( $this, 'settings_page' )
		);

		add_action( 'load-' . $settings_page, array( $this, 'settings_page_init' ) );
	}

	/**
	 * Check if the user can access the top-level WooCommerce item.
	 */
	public static function can_view_woocommerce_menu_item() {
		return current_user_can( 'edit_others_shop_orders' );
	}

	/**
	 * Loads gateways and shipping methods into memory for use within settings.
	 */
	public function settings_page_init() {
		WC()->payment_gateways();
		WC()->shipping();

		// Include settings pages.
		WC_Admin_Settings::get_settings_pages();

		// Add any posted messages.
		if ( ! empty( $_GET['wc_error'] ) ) { // WPCS: input var okay, CSRF ok.
			WC_Admin_Settings::add_error( wp_kses_post( wp_unslash( $_GET['wc_error'] ) ) ); // WPCS: input var okay, CSRF ok.
		}

		if ( ! empty( $_GET['wc_message'] ) ) { // WPCS: input var okay, CSRF ok.
			WC_Admin_Settings::add_message( wp_kses_post( wp_unslash( $_GET['wc_message'] ) ) ); // WPCS: input var okay, CSRF ok.
		}

		do_action( 'woocommerce_settings_page_init' );
	}

	/**
	 * Handle saving of settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		global $current_tab, $current_section;

		// We should only save on the settings page.
		if ( ! is_wc_admin_settings_page() ) {
			return;
		}

		// Include settings pages.
		WC_Admin_Settings::get_settings_pages();

		// Get current tab/section.
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // WPCS: input var okay, CSRF ok.
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // WPCS: input var okay, CSRF ok.

		// Save settings if data has been posted.
		if ( '' !== $current_section && apply_filters( "woocommerce_save_settings_{$current_tab}_{$current_section}", ! empty( $_POST['save'] ) ) ) { // WPCS: input var okay, CSRF ok.
			WC_Admin_Settings::save();
		} elseif ( '' === $current_section && apply_filters( "woocommerce_save_settings_{$current_tab}", ! empty( $_POST['save'] ) ) ) { // WPCS: input var okay, CSRF ok.
			WC_Admin_Settings::save();
		}
	}

	/**
	 * Add menu item.
	 */
	public function status_menu() {
		$status_page = add_submenu_page( 'woocommerce', __( 'WooCommerce status', 'woocommerce' ), __( 'Status', 'woocommerce' ), 'manage_woocommerce', 'wc-status', array( $this, 'status_page' ) );

		add_action(
			'load-' . $status_page,
			function () {
				if ( 'logs' === filter_input( INPUT_GET, 'tab' ) ) {
					// Initialize the logging page controller early so that it can hook into things.
					wc_get_container()->get( LoggingPageController::class );
				}
			},
			1
		);
	}

	/**
	 * Addons menu item.
	 *
	 * @deprecated 10.5.0 The marketplace feature is now always enabled. Use the Extensions menu instead.
	 */
	public function addons_menu() {
		wc_deprecated_function( __METHOD__, '10.5.0' );

		$count_html = WC_Helper_Updater::get_updates_count_html();
		/* translators: %s: extensions count */
		$menu_title = sprintf( __( 'Extensions %s', 'woocommerce' ), $count_html );
		add_submenu_page( 'woocommerce', __( 'WooCommerce extensions', 'woocommerce' ), $menu_title, 'manage_woocommerce', 'wc-addons', array( $this, 'addons_page' ) );
	}

	/**
	 * Registers the wc-addons page within the WooCommerce menu.
	 * Temporary measure till we convert the whole page to React.
	 *
	 * @return void
	 */
	public function addons_my_subscriptions() {
		add_submenu_page( 'woocommerce', __( 'WooCommerce extensions', 'woocommerce' ), null, 'manage_woocommerce', 'wc-addons', array( $this, 'addons_page' ) );
		// Temporarily hide the submenu item we've just added.
		$this->hide_submenu_page( 'woocommerce', 'wc-addons' );
	}

	/**
	 * Highlights the correct top level admin menu item for post type add screens.
	 */
	public function menu_highlight() {
		global $parent_file, $submenu_file, $post_type;

		switch ( $post_type ) {
			case 'shop_order':
			case 'shop_coupon':
				$parent_file = 'woocommerce'; // WPCS: override ok.
				break;
			case 'product':
				$screen = get_current_screen();
				if ( $screen && taxonomy_is_product_attribute( $screen->taxonomy ) ) {
					$submenu_file = 'product_attributes'; // WPCS: override ok.
					$parent_file  = 'edit.php?post_type=product'; // WPCS: override ok.
				}
				break;
		}
	}

	/**
	 * Adds the order processing count to the menu.
	 */
	public function menu_order_count() {
		global $submenu;

		if ( isset( $submenu['woocommerce'] ) ) {
			// Remove 'WooCommerce' sub menu item.
			unset( $submenu['woocommerce'][0] );

			// Add count if user has access.
			if ( apply_filters( 'woocommerce_include_processing_order_count_in_menu', true ) && current_user_can( 'edit_others_shop_orders' ) ) {
				$order_count = apply_filters( 'woocommerce_menu_order_count', wc_processing_order_count() );

				if ( $order_count ) {
					foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
						if ( 0 === strpos( $menu_item[0], _x( 'Orders', 'Admin menu name', 'woocommerce' ) ) ) {
							$submenu['woocommerce'][ $key ][0] .= ' <span class="awaiting-mod update-plugins count-' . esc_attr( $order_count ) . '"><span class="processing-count">' . number_format_i18n( $order_count ) . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Reorder the WC menu items in admin.
	 *
	 * @param int $menu_order Menu order.
	 * @return array
	 */
	public function menu_order( $menu_order ) {
		// Initialize our custom order array.
		$woocommerce_menu_order = array();

		// Get the index of our custom separator.
		$woocommerce_separator = array_search( 'separator-woocommerce', $menu_order, true );

		// Get index of product menu.
		$woocommerce_product = array_search( 'edit.php?post_type=product', $menu_order, true );

		// Loop through menu order and do some rearranging.
		foreach ( $menu_order as $index => $item ) {

			if ( 'woocommerce' === $item ) {
				$woocommerce_menu_order[] = 'separator-woocommerce';
				$woocommerce_menu_order[] = $item;
				$woocommerce_menu_order[] = 'edit.php?post_type=product';
				unset( $menu_order[ $woocommerce_separator ] );
				unset( $menu_order[ $woocommerce_product ] );
			} elseif ( ! in_array( $item, array( 'separator-woocommerce' ), true ) ) {
				$woocommerce_menu_order[] = $item;
			}
		}

		// Return order.
		return $woocommerce_menu_order;
	}

	/**
	 * Custom menu order.
	 *
	 * @param bool $enabled Whether custom menu ordering is already enabled.
	 * @return bool
	 */
	public function custom_menu_order( $enabled ) {
		return $enabled || self::can_view_woocommerce_menu_item();
	}

	/**
	 * Validate screen options on update.
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 */
	public function set_screen_option( $status, $option, $value ) {
		$screen_options = array(
			'woocommerce_keys_per_page',
			'woocommerce_webhooks_per_page',
			FileListTable::PER_PAGE_USER_OPTION_KEY,
			SearchListTable::PER_PAGE_USER_OPTION_KEY,
			WC_Admin_Log_Table_List::PER_PAGE_USER_OPTION_KEY,
		);

		if ( in_array( $option, $screen_options, true ) ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Init the reports page.
	 */
	public function reports_page() {
		WC_Admin_Reports::output();
	}

	/**
	 * Init the settings page.
	 */
	public function settings_page() {
		if ( Features::is_enabled( 'settings' ) ) {
			echo '<div id="wc-settings-page"/>';
		} else {
			WC_Admin_Settings::output();
		}
	}

	/**
	 * Init the attributes page.
	 */
	public function attributes_page() {
		WC_Admin_Attributes::output();
	}

	/**
	 * Init the status page.
	 */
	public function status_page() {
		WC_Admin_Status::output();
	}

	/**
	 * Init the addons page.
	 */
	public function addons_page() {
		WC_Admin_Addons::handle_legacy_marketplace_redirects();
	}

	/**
	 * Link to the order admin list table from the main WooCommerce menu.
	 *
	 * @return void
	 */
	public function orders_menu(): void {
		if ( wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			wc_get_container()->get( Custom_Orders_PageController::class )->setup();
		} else {
			wc_get_container()->get( COTRedirectionController::class )->setup();
		}
	}

	/**
	 * Add custom nav meta box.
	 *
	 * Adapted from http://www.johnmorrisonline.com/how-to-add-a-fully-functional-custom-meta-box-to-wordpress-navigation-menus/.
	 */
	public function add_nav_menu_meta_boxes() {
		add_meta_box( 'woocommerce_endpoints_nav_link', __( 'WooCommerce endpoints', 'woocommerce' ), array( $this, 'nav_menu_links' ), 'nav-menus', 'side', 'low' );
	}

	/**
	 * Output menu links.
	 */
	public function nav_menu_links() {
		// Get items from account menu.
		$endpoints = wc_get_account_menu_items();

		// Remove dashboard item.
		if ( isset( $endpoints['dashboard'] ) ) {
			unset( $endpoints['dashboard'] );
		}

		// Include missing lost password endpoint, if set in WooCommerce > Settings > Advanced > Account endpoints.
		if ( ! empty( get_option( 'woocommerce_myaccount_lost_password_endpoint' ) ) ) {
			$endpoints['lost-password'] = __( 'Lost password', 'woocommerce' );
		}

		$endpoints = apply_filters( 'woocommerce_custom_nav_menu_items', $endpoints );

		?>
		<div id="posttype-woocommerce-endpoints" class="posttypediv">
			<div id="tabs-panel-woocommerce-endpoints" class="tabs-panel tabs-panel-active">
				<ul id="woocommerce-endpoints-checklist" class="categorychecklist form-no-clear">
					<?php
					$i = -1;
					foreach ( $endpoints as $key => $value ) :
						?>
						<li>
							<label class="menu-item-title">
								<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-object-id]" value="<?php echo esc_attr( $i ); ?>" /> <?php echo esc_html( $value ); ?>
							</label>
							<input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-type]" value="custom" />
							<input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-title]" value="<?php echo esc_attr( $value ); ?>" />
							<input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-url]" value="<?php echo esc_url( wc_get_account_endpoint_url( $key ) ); ?>" />
							<input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-classes]" />
						</li>
						<?php
						--$i;
					endforeach;
					?>
				</ul>
			</div>
			<p class="button-controls" data-items-type="posttype-woocommerce-endpoints">
				<span class="list-controls">
					<label>
						<input type="checkbox" class="select-all" />
						<?php esc_html_e( 'Select all', 'woocommerce' ); ?>
					</label>
				</span>
				<span class="add-to-menu">
					<button type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to menu', 'woocommerce' ); ?>" name="add-post-type-menu-item" id="submit-posttype-woocommerce-endpoints"><?php esc_html_e( 'Add to menu', 'woocommerce' ); ?></button>
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Add the "Visit Store" link in admin bar main menu.
	 *
	 * @since 2.4.0
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function admin_bar_menus( $wp_admin_bar ) {
		if ( ! is_admin() || ! is_admin_bar_showing() ) {
			return;
		}

		// Show only when the user is a member of this site, or they're a super admin.
		if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
			return;
		}

		// Don't display when shop page is the same of the page on front.
		if ( intval( get_option( 'page_on_front' ) ) === wc_get_page_id( 'shop' ) ) {
			return;
		}

		// Add an option to visit the store.
		$wp_admin_bar->add_node(
			array(
				'parent' => 'site-name',
				'id'     => 'view-store',
				'title'  => __( 'Visit Store', 'woocommerce' ),
				'href'   => wc_get_page_permalink( 'shop' ),
			)
		);
	}

	/**
	 * Maybe add new management product experience.
	 */
	public function maybe_add_new_product_management_experience() {
		if ( FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			global $submenu;
			if ( isset( $submenu['edit.php?post_type=product'][10] ) ) {
				// Disable phpcs since we need to override submenu classes.
				// Note that `phpcs:ignore WordPress.Variables.GlobalVariables.OverrideProhibited` does not work to disable this check.
				// phpcs:disable
				$submenu['edit.php?post_type=product'][10][2] = 'admin.php?page=wc-admin&path=/add-product';
				// phps:enableWordPress.Variables.GlobalVariables.OverrideProhibited
			}
		}
	}

	/**
	 * Hide the submenu page based on slug and return the item that was hidden.
	 *
	 * Borrowed from Jetpack's Base_Admin_Menu class.
	 *
	 * Instead of actually removing the submenu item, a safer approach is to hide it and filter it in the API response.
	 * In this manner we'll avoid breaking third-party plugins depending on items that no longer exist.
	 *
	 * A false|array value is returned to be consistent with remove_submenu_page() function
	 *
	 * @param string $menu_slug The parent menu slug.
	 * @param string $submenu_slug The submenu slug that should be hidden.
	 * @return false|array
	 */
	public function hide_submenu_page( $menu_slug, $submenu_slug ) {
		global $submenu;

		if ( ! isset( $submenu[ $menu_slug ] ) ) {
			return false;
		}

		foreach ( $submenu[ $menu_slug ] as $i => $item ) {
			if ( $submenu_slug !== $item[2] ) {
				continue;
			}

			$this->hide_submenu_element( $i, $menu_slug, $item );

			return $item;
		}

		return false;
	}

	/**
	 * Apply the hide-if-js CSS class to a submenu item.
	 *
	 * Borrowed from Jetpack's Base_Admin_Menu class.
	 *
	 * @param int    $index The position of a submenu item in the submenu array.
	 * @param string $parent_slug The parent slug.
	 * @param array  $item The submenu item.
	 */
	public function hide_submenu_element( $index, $parent_slug, $item ) {
		global $submenu;

		$css_classes = empty( $item[4] ) ? self::HIDE_CSS_CLASS : $item[4] . ' ' . self::HIDE_CSS_CLASS;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu [ $parent_slug ][ $index ][4] = $css_classes;
	}
}

return new WC_Admin_Menus();
