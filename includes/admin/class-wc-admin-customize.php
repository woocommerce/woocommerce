<?php
/**
 * Setup customize items.
 *
 * @package WooCommerce/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Customize Class.
 */
class WC_Admin_Customize {

	/**
	 * Singleton instance.
	 *
	 * @var WC_Admin_Customize|null
	 */
	protected static $instance = null;

	/**
	 * Return singleston instance.
	 *
	 * @static
	 * @return WC_Admin_Customize
	 */
	final public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Singleton. Prevent clone.
	 */
	final public function __clone() {
		trigger_error( 'Singleton. No cloning allowed!', E_USER_ERROR ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
	}

	/**
	 * Singleton. Prevent serialization.
	 */
	final public function __wakeup() {
		trigger_error( 'Singleton. No serialization allowed!', E_USER_ERROR ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
	}

	/**
	 * Singleton. Prevent construct.
	 */
	final private function __construct() {}

	/**
	 * Hook into WP actions/filters.
	 */
	public function init() {
		// Include custom items to customizer nav menu settings.
		add_filter( 'customize_nav_menu_available_item_types', array( $this, 'register_customize_nav_menu_item_types' ) );
		add_filter( 'customize_nav_menu_available_items', array( $this, 'register_customize_nav_menu_items' ), 10, 4 );
	}

	/**
	 * Register customize new nav menu item types.
	 * This will register WooCommerce account endpoints as a nav menu item type.
	 *
	 * @since  3.1.0
	 * @param  array $item_types Menu item types.
	 * @return array
	 */
	public function register_customize_nav_menu_item_types( $item_types ) {
		$item_types[] = array(
			'title'      => __( 'WooCommerce Endpoints', 'woocommerce' ),
			'type_label' => __( 'WooCommerce Endpoint', 'woocommerce' ),
			'type'       => 'woocommerce_nav',
			'object'     => 'woocommerce_endpoint',
		);

		return $item_types;
	}

	/**
	 * Register account endpoints to customize nav menu items.
	 *
	 * @since  3.1.0
	 * @param  array   $items  List of nav menu items.
	 * @param  string  $type   Nav menu type.
	 * @param  string  $object Nav menu object.
	 * @param  integer $page   Page number.
	 * @return array
	 */
	public function register_customize_nav_menu_items( $items = array(), $type = '', $object = '', $page = 0 ) {
		if ( 'woocommerce_endpoint' !== $object ) {
			return $items;
		}

		// Don't allow pagination since all items are loaded at once.
		if ( 0 < $page ) {
			return $items;
		}

		// Get items from account menu.
		$endpoints = wc_get_account_menu_items();

		// Remove dashboard item.
		if ( isset( $endpoints['dashboard'] ) ) {
			unset( $endpoints['dashboard'] );
		}

		// Include missing lost password.
		$endpoints['lost-password'] = __( 'Lost password', 'woocommerce' );

		$endpoints = apply_filters( 'woocommerce_custom_nav_menu_items', $endpoints );

		foreach ( $endpoints as $endpoint => $title ) {
			$items[] = array(
				'id'         => $endpoint,
				'title'      => $title,
				'type_label' => __( 'Custom Link', 'woocommerce' ),
				'url'        => esc_url_raw( wc_get_account_endpoint_url( $endpoint ) ),
			);
		}

		return $items;
	}
}
