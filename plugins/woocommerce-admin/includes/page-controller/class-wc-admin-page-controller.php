<?php
/**
 * WC_Admin_Page_Controller
 *
 * @package Woocommerce Admin
 */

/**
 * WC_Admin_Page_Controller
 */
class WC_Admin_Page_Controller {
	// JS-powered page root.
	const PAGE_ROOT = 'wc-admin';

	/**
	 * Singleton instance of self.
	 *
	 * @var WC_Admin_Page_Controller
	 */
	private static $instance = false;

	/**
	 * Current page ID (or false if not registered with this controller).
	 *
	 * @var string
	 */
	private $current_page = null;

	/**
	 * Registered pages
	 * Contains information (breadcrumbs, menu info) about JS powered pages and classic WooCommerce pages.
	 *
	 * @var array
	 */
	private $pages = array();

	/**
	 * We want a single instance of this class so we can accurately track registered menus and pages.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Connect an existing page to wc-admin.
	 *
	 * @param array $options {
	 *   Array describing the page.
	 *
	 *   @type string       id           Id to reference the page.
	 *   @type string|array title        Page title. Used in menus and breadcrumbs.
	 *   @type string|null  parent       Parent ID. Null for new top level page.
	 *   @type string       path         Path for this page. E.g. admin.php?page=wc-settings&tab=checkout
	 *   @type string       capability   Capability needed to access the page.
	 *   @type string       icon         Icon. Dashicons helper class, base64-encoded SVG, or 'none'.
	 *   @type int          position     Menu item position.
	 * }
	 */
	public function connect_page( $options ) {
		if ( ! is_array( $options['title'] ) ) {
			$options['title'] = array( $options['title'] );
		}

		// TODO: check for null ID, or collision.
		$this->pages[ $options['id'] ] = $options;
	}

	/**
	 * Determine the current page ID, if it was registered with this controller.
	 */
	public function determine_current_page() {
		$current_url       = '';
		$current_screen_id = $this->get_current_screen_id();

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$current_url = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		$current_path     = wp_parse_url( $current_url, PHP_URL_PATH );
		$current_query    = wp_parse_url( $current_url, PHP_URL_QUERY );
		$current_fragment = wp_parse_url( $current_url, PHP_URL_FRAGMENT );

		foreach ( $this->pages as $page ) {
			if ( isset( $page['js_page'] ) && $page['js_page'] ) {
				// Check registered admin pages.
				$full_page_path = add_query_arg( 'page', $page['path'], admin_url( 'admin.php' ) );
				$page_path      = wp_parse_url( $full_page_path, PHP_URL_PATH );
				$page_query     = wp_parse_url( $full_page_path, PHP_URL_QUERY );
				$page_fragment  = wp_parse_url( $full_page_path, PHP_URL_FRAGMENT );

				if (
					$page_path === $current_path &&
					$page_query === $current_query &&
					$page_fragment === $current_fragment
				) {
					$this->current_page = $page;
					return;
				}
			} else {
				// Check connected admin pages.
				if (
					isset( $page['screen_id'] ) &&
					$page['screen_id'] === $current_screen_id
				) {
					$this->current_page = $page;
					return;
				}
			}
		}

		$this->current_page = false;
	}


	/**
	 * Get breadcrumbs for WooCommerce Admin Page navigation.
	 *
	 * @return array Navigation pieces (breadcrumbs).
	 */
	public function get_breadcrumbs() {
		$current_page = $this->get_current_page();

		// Bail if this isn't a page registered with this controller.
		if ( false === $current_page ) {
			return array( '' );
		}

		if ( 1 === count( $current_page['title'] ) ) {
			$breadcrumbs = $current_page['title'];
		} else {
			// If this page has multiple title pieces, only link the first one.
			$breadcrumbs = array_merge(
				array(
					array( $current_page['path'], reset( $current_page['title'] ) ),
				),
				array_slice( $current_page['title'], 1 )
			);
		}

		if ( isset( $current_page['parent'] ) ) {
			$parent_id = $current_page['parent'];

			while ( $parent_id ) {
				if ( isset( $this->pages[ $parent_id ] ) ) {
					$parent = $this->pages[ $parent_id ];
					array_unshift( $breadcrumbs, array( $parent['path'], reset( $parent['title'] ) ) );
					$parent_id = isset( $parent['parent'] ) ? $parent['parent'] : false;
				} else {
					$parent_id = false;
				}
			}
		}

		return $breadcrumbs;
	}

	/**
	 * Get the current page.
	 *
	 * @return array|boolean Current page or false if not registered with this controller.
	 */
	public function get_current_page() {
		if ( is_null( $this->current_page ) ) {
			$this->determine_current_page();
		}

		return $this->current_page;
	}


	/**
	 * Returns the current screen ID.
	 * This is slightly different from WP's get_current_screen, in that it attaches an action,
	 * so certain pages like 'add new' pages can have different breadcrumbs or handling.
	 * It also catches some more unique dynamic pages like taxonomy/attribute management.
	 *
	 * Format: {$current_screen->action}-{$current_screen->action}-tab,
	 * {$current_screen->action}-{$current_screen->action} if no tab is present,
	 * or just {$current_screen->action} if no action or tab is present.
	 *
	 * @return string Current screen ID.
	 */
	public function get_current_screen_id() {
		$current_screen = get_current_screen();
		if ( ! $current_screen ) {
			return false;
		}

		$screen_pieces = array( $current_screen->id );

		if ( $current_screen->action ) {
			$screen_pieces[] = $current_screen->action;
		}

		if (
			! empty( $current_screen->taxonomy ) &&
			isset( $current_screen->post_type ) &&
			'product' === $current_screen->post_type
		) {
			// Editing a product attribute.
			if ( 0 === strpos( $current_screen->taxonomy, 'pa_' ) ) {
				return 'product_page_product_attribute-edit';
			}

			// Editing a product taxonomy term.
			if ( ! empty( $_GET['tag_ID'] ) ) {
				return $current_screen->taxonomy;
			}
		}

		// Pages with default tab values.
		$pages_with_tabs = apply_filters(
			'wc_admin_pages_with_tabs',
			array(
				'wc-reports'  => 'orders',
				'wc-settings' => 'general',
				'wc-status'   => 'status',
				'wc-addons'   => 'browse-extensions',
			)
		);

		// Tabs that have sections as well.
		$tabs_with_sections = apply_filters(
			'wc_admin_page_tab_sections',
			array(
				'products'          => array( '', 'inventory', 'downloadable' ),
				'shipping'          => array( '', 'options', 'classes' ),
				'checkout'          => array( 'bacs', 'cheque', 'cod', 'paypal' ),
				'email'             => array(
					'wc_email_new_order',
					'wc_email_cancelled_order',
					'wc_email_failed_order',
					'wc_email_customer_on_hold_order',
					'wc_email_customer_processing_order',
					'wc_email_customer_completed_order',
					'wc_email_customer_refunded_order',
					'wc_email_customer_invoice',
					'wc_email_customer_note',
					'wc_email_customer_reset_password',
					'wc_email_customer_new_account',
				),
				'advanced'          => array(
					'',
					'keys',
					'webhooks',
					'legacy_api',
					'woocommerce_com',
				),
				'browse-extensions' => array( 'helper' ),
			)
		);

		if ( ! empty( $_GET['page'] ) ) {
			if ( in_array( $_GET['page'], array_keys( $pages_with_tabs ) ) ) { // WPCS: sanitization ok.
				if ( ! empty( $_GET['tab'] ) ) {
					$tab = wc_clean( wp_unslash( $_GET['tab'] ) );
				} else {
					$tab = $pages_with_tabs[ $_GET['page'] ]; // WPCS: sanitization ok.
				}

				$screen_pieces[] = $tab;

				if ( ! empty( $_GET['section'] ) ) {
					if (
						isset( $tabs_with_sections[ $tab ] ) &&
						in_array( $_GET['section'], array_keys( $tabs_with_sections[ $tab ] ) ) // WPCS: sanitization ok.
					) {
						$screen_pieces[] = wc_clean( wp_unslash( $_GET['section'] ) );
					}
				}

				// Editing a shipping zone.
				if ( ( 'shipping' === $tab ) && isset( $_GET['zone_id'] ) ) {
					$screen_pieces[] = 'edit_zone';
				}
			}
		}

		return implode( '-', $screen_pieces );
	}

	/**
	 * Returns the path from an ID.
	 *
	 * @param  string $id  ID to get path for.
	 * @return string Path for the given ID, or the ID on lookup miss.
	 */
	public function get_path_from_id( $id ) {
		if ( isset( $this->pages[ $id ] ) && isset( $this->pages[ $id ]['path'] ) ) {
			return $this->pages[ $id ]['path'];
		}
		return $id;
	}

	/**
	 * Returns true if we are on a page connected to this controller.
	 *
	 * @return boolean
	 */
	public function is_connected_page() {
		$current_page = $this->get_current_page();

		if ( false === $current_page ) {
			return false;
		}

		return ( isset( $current_page['js_page'] ) ? ! $current_page['js_page'] : true );
	}

	/**
	 * Returns true if we are on a page registed with this controller.
	 *
	 * @return boolean
	 */
	public function is_registered_page() {
		$current_page = $this->get_current_page();

		if ( false === $current_page ) {
			return false;
		}

		return ( isset( $current_page['js_page'] ) && $current_page['js_page'] );
	}

	/**
	 * Adds a JS powered page to wc-admin.
	 *
	 * @param array $options {
	 *   Array describing the page.
	 *
	 *   @type string      id           Id to reference the page.
	 *   @type string      title        Page title. Used in menus and breadcrumbs.
	 *   @type string|null parent       Parent ID. Null for new top level page.
	 *   @type string      path         Path for this page, full path in app context; ex /analytics/report
	 *   @type string      capability   Capability needed to access the page.
	 *   @type string      icon         Icon. Dashicons helper class, base64-encoded SVG, or 'none'.
	 *   @type int         position     Menu item position.
	 * }
	 */
	public function register_page( $options ) {
		$defaults = array(
			'id'         => null,
			'parent'     => null,
			'title'      => '',
			'capability' => 'manage_options',
			'path'       => '',
			'icon'       => '',
			'position'   => null,
			'js_page'    => true,
		);

		$options = wp_parse_args( $options, $defaults );

		if ( 0 !== strpos( $options['path'], self::PAGE_ROOT ) ) {
			$options['path'] = self::PAGE_ROOT . '#' . $options['path'];
		}

		if ( is_null( $options['parent'] ) ) {
			add_menu_page(
				$options['title'],
				$options['title'],
				$options['capability'],
				$options['path'],
				array( __CLASS__, 'page_wrapper' ),
				$options['icon'],
				$options['position']
			);
		} else {
			$parent_path = $this->get_path_from_id( $options['parent'] );
			// TODO: check for null path.
			add_submenu_page(
				$parent_path,
				$options['title'],
				$options['title'],
				$options['capability'],
				$options['path'],
				array( __CLASS__, 'page_wrapper' )
			);
		}

		$this->connect_page( $options );
	}

	/**
	 * Set up a div for the app to render into.
	 */
	public static function page_wrapper() {
		?>
		<div class="wrap">
			<div id="root"></div>
		</div>
		<?php
	}
}
