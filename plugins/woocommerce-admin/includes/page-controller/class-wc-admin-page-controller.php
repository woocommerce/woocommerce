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
	 * Determine the current page ID, if it was registered with this controller.
	 */
	public function determine_current_page() {
		$current_url = '';

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$current_url = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		$current_path     = wp_parse_url( $current_url, PHP_URL_PATH );
		$current_query    = wp_parse_url( $current_url, PHP_URL_QUERY );
		$current_fragment = wp_parse_url( $current_url, PHP_URL_FRAGMENT );

		foreach ( $this->pages as $page ) {
			$page_url      = admin_url( 'admin.php?page=' . $page['path'] ); // See: menu_page_url().
			$page_path     = wp_parse_url( $page_url, PHP_URL_PATH );
			$page_query    = wp_parse_url( $page_url, PHP_URL_QUERY );
			$page_fragment = wp_parse_url( $page_url, PHP_URL_FRAGMENT );

			if (
				$page_path === $current_path &&
				$page_query === $current_query &&
				$page_fragment === $current_fragment
			) {
				$this->current_page = $page['id'];
				return;
			}
		}

		$this->current_page = false;
	}

	/**
	 * Get the current page ID.
	 *
	 * @return string|boolean Current page ID or false if not registered with this controller.
	 */
	public function get_current_page() {
		if ( is_null( $this->current_page ) ) {
			$this->determine_current_page();
		}

		return $this->current_page;
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
	 * Returns true if we are on a page registed with this controller.
	 *
	 * @return boolean
	 */
	public function is_registered_page() {
		$current_page = $this->get_current_page();
		return ! empty( $current_page );
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
		);

		$options = wp_parse_args( $options, $defaults );

		if ( 0 !== strpos( $options['path'], self::PAGE_ROOT ) ) {
			$options['path'] = self::PAGE_ROOT . '#' . $options['path'];
		}

		// TODO: check for null ID, or collision.
		$this->pages[ $options['id'] ] = $options;

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
