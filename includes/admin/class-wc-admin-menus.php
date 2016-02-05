<?php
/**
 * Setup menus in WP admin.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Admin_Menus' ) ) :

/**
 * WC_Admin_Menus Class.
 */
class WC_Admin_Menus {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'reports_menu' ), 20 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
		add_action( 'admin_menu', array( $this, 'status_menu' ), 60 );

		if ( apply_filters( 'woocommerce_show_addons_page', true ) ) {
			add_action( 'admin_menu', array( $this, 'addons_menu' ), 70 );
		}

		add_action( 'admin_head', array( $this, 'menu_highlight' ) );
		add_action( 'admin_head', array( $this, 'menu_order_count' ) );
		add_filter( 'menu_order', array( $this, 'menu_order' ) );
		add_filter( 'custom_menu_order', array( $this, 'custom_menu_order' ) );

		// Add endpoints custom URLs in Appearance > Menus > Pages
		add_action( 'admin_init', array( $this, 'add_nav_menu_meta_boxes' ) );

		// Admin bar menus
		if ( apply_filters( 'woocommerce_show_admin_bar_visit_store', true ) ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 31 );
		}
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		global $menu;

		if ( current_user_can( 'manage_woocommerce' ) ) {
			$menu[] = array( '', 'read', 'separator-woocommerce', '', 'wp-menu-separator woocommerce' );
		}

		add_menu_page( __( 'WooCommerce', 'woocommerce' ), __( 'WooCommerce', 'woocommerce' ), 'manage_woocommerce', 'woocommerce', null, null, '55.5' );

		add_submenu_page( 'edit.php?post_type=product', __( 'Attributes', 'woocommerce' ), __( 'Attributes', 'woocommerce' ), 'manage_product_terms', 'product_attributes', array( $this, 'attributes_page' ) );
	}

	/**
	 * Add menu item.
	 */
	public function reports_menu() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			add_submenu_page( 'woocommerce', __( 'Reports', 'woocommerce' ),  __( 'Reports', 'woocommerce' ) , 'view_woocommerce_reports', 'wc-reports', array( $this, 'reports_page' ) );
		} else {
			add_menu_page( __( 'Sales Reports', 'woocommerce' ),  __( 'Sales Reports', 'woocommerce' ) , 'view_woocommerce_reports', 'wc-reports', array( $this, 'reports_page' ), null, '55.6' );
		}
	}

	/**
	 * Add menu item.
	 */
	public function settings_menu() {
		$settings_page = add_submenu_page( 'woocommerce', __( 'WooCommerce Settings', 'woocommerce' ),  __( 'Settings', 'woocommerce' ) , 'manage_woocommerce', 'wc-settings', array( $this, 'settings_page' ) );

		add_action( 'load-' . $settings_page, array( $this, 'settings_page_init' ) );
	}

	/**
	 * Loads gateways and shipping methods into memory for use within settings.
	 */
	public function settings_page_init() {
		WC()->payment_gateways();
		WC()->shipping();
	}

	/**
	 * Add menu item.
	 */
	public function status_menu() {
		add_submenu_page( 'woocommerce', __( 'WooCommerce Status', 'woocommerce' ),  __( 'System Status', 'woocommerce' ) , 'manage_woocommerce', 'wc-status', array( $this, 'status_page' ) );
		register_setting( 'woocommerce_status_settings_fields', 'woocommerce_status_options' );
	}

	/**
	 * Addons menu item.
	 */
	public function addons_menu() {
		add_submenu_page( 'woocommerce', __( 'WooCommerce Add-ons/Extensions', 'woocommerce' ),  __( 'Add-ons', 'woocommerce' ) , 'manage_woocommerce', 'wc-addons', array( $this, 'addons_page' ) );
	}

	/**
	 * Highlights the correct top level admin menu item for post type add screens.
	 */
	public function menu_highlight() {
		global $parent_file, $submenu_file, $post_type;

		switch ( $post_type ) {
			case 'shop_order' :
			case 'shop_coupon' :
				$parent_file = 'woocommerce';
			break;
			case 'product' :
				$screen = get_current_screen();
				if ( $screen && taxonomy_is_product_attribute( $screen->taxonomy ) ) {
					$submenu_file = 'product_attributes';
					$parent_file  = 'edit.php?post_type=product';
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
			// Remove 'WooCommerce' sub menu item
			unset( $submenu['woocommerce'][0] );

			// Add count if user has access
			if ( apply_filters( 'woocommerce_include_processing_order_count_in_menu', true ) && current_user_can( 'manage_woocommerce' ) && ( $order_count = wc_processing_order_count() ) ) {
				foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
					if ( 0 === strpos( $menu_item[0], _x( 'Orders', 'Admin menu name', 'woocommerce' ) ) ) {
						$submenu['woocommerce'][ $key ][0] .= ' <span class="awaiting-mod update-plugins count-' . $order_count . '"><span class="processing-count">' . number_format_i18n( $order_count ) . '</span></span>';
						break;
					}
				}
			}
		}
	}

	/**
	 * Reorder the WC menu items in admin.
	 *
	 * @param mixed $menu_order
	 * @return array
	 */
	public function menu_order( $menu_order ) {
		// Initialize our custom order array
		$woocommerce_menu_order = array();

		// Get the index of our custom separator
		$woocommerce_separator = array_search( 'separator-woocommerce', $menu_order );

		// Get index of product menu
		$woocommerce_product = array_search( 'edit.php?post_type=product', $menu_order );

		// Loop through menu order and do some rearranging
		foreach ( $menu_order as $index => $item ) {

			if ( ( ( 'woocommerce' ) == $item ) ) {
				$woocommerce_menu_order[] = 'separator-woocommerce';
				$woocommerce_menu_order[] = $item;
				$woocommerce_menu_order[] = 'edit.php?post_type=product';
				unset( $menu_order[ $woocommerce_separator ] );
				unset( $menu_order[ $woocommerce_product ] );
			} elseif ( ! in_array( $item, array( 'separator-woocommerce' ) ) ) {
				$woocommerce_menu_order[] = $item;
			}

		}

		// Return order
		return $woocommerce_menu_order;
	}

	/**
	 * Custom menu order.
	 *
	 * @return bool
	 */
	public function custom_menu_order() {
		return current_user_can( 'manage_woocommerce' );
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
		WC_Admin_Settings::output();
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
		WC_Admin_Addons::output();
	}

	/**
	 * Add custom nav meta box.
	 *
	 * Adapted from http://www.johnmorrisonline.com/how-to-add-a-fully-functional-custom-meta-box-to-wordpress-navigation-menus/.
	 */
	public function add_nav_menu_meta_boxes() {
		add_meta_box( 'woocommerce_endpoints_nav_link', __( 'WooCommerce Endpoints', 'woocommerce' ), array( $this, 'nav_menu_links' ), 'nav-menus', 'side', 'low' );
	}

	/**
	 * Output menu links.
	 */
	public function nav_menu_links() {
		$exclude = array( 'view-order', 'add-payment-method', 'order-pay', 'order-received' );
		?>
		<div id="posttype-woocommerce-endpoints" class="posttypediv">
			<div id="tabs-panel-woocommerce-endpoints" class="tabs-panel tabs-panel-active">
				<ul id="woocommerce-endpoints-checklist" class="categorychecklist form-no-clear">
					<?php
					$i = -1;
					foreach ( WC()->query->query_vars as $key => $value ) {
						if ( in_array( $key, $exclude ) ) {
							continue;
						}
						?>
						<li>
							<label class="menu-item-title">
								<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-object-id]" value="<?php echo esc_attr( $i ); ?>" /> <?php echo esc_html( $key ); ?>
							</label>
							<input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-type]" value="custom" />
							<input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-title]" value="<?php echo esc_html( $key ); ?>" />
							<input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-url]" value="<?php echo esc_url( wc_get_endpoint_url( $key, '', wc_get_page_permalink( 'myaccount' ) ) ); ?>" />
							<input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-classes]" />
						</li>
						<?php
						$i --;
					}
					?>
				</ul>
			</div>
			<p class="button-controls">
				<span class="list-controls">
					<a href="<?php echo admin_url( 'nav-menus.php?page-tab=all&selectall=1#posttype-woocommerce-endpoints' ); ?>" class="select-all"><?php _e( 'Select All', 'woocommerce' ); ?></a>
				</span>
				<span class="add-to-menu">
					<input type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'woocommerce' ); ?>" name="add-post-type-menu-item" id="submit-posttype-woocommerce-endpoints">
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
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar_menus( $wp_admin_bar ) {
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		// Show only when the user is a member of this site, or they're a super admin.
		if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
			return;
		}

		// Don't display when shop page is the same of the page on front.
		if ( get_option( 'page_on_front' ) == wc_get_page_id( 'shop' ) ) {
			return;
		}

		// Add an option to visit the store.
		$wp_admin_bar->add_node( array(
			'parent' => 'site-name',
			'id'     => 'view-store',
			'title'  => __( 'Visit Store', 'woocommerce' ),
			'href'   => wc_get_page_permalink( 'shop' )
		) );
	}
}

endif;

return new WC_Admin_Menus();
