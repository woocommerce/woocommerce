<?php
/**
 * WooCommerce admin menu icons.
 *
 * @package WooCommerce\Internal\Admin
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin;

/**
 * Uses registered SVG icons in the WordPress 7.2 admin menu.
 *
 * @internal
 */
final class MenuIcons {

	/**
	 * Icons not provided by the WordPress admin icon collection.
	 *
	 * The logo uses an 18px mark on a 24px grid. The megaphone comes from @wordpress/icons.
	 */
	private const ICONS = array(
		'logo'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19.22 7.03c-0.9 0 -1.49 0.29 -2.01 1.28L14.82 12.82V8.81c0 -1.19 -0.57 -1.78 -1.61 -1.78s-1.49 0.36 -2.01 1.36L8.93 12.82V8.86c0 -1.28 -0.52 -1.82 -1.8 -1.82H4.53C3.54 7.03 3 7.49 3 8.33s0.52 1.34 1.49 1.34h1.07v5.05c0 1.42 0.96 2.26 2.35 2.26S9.92 16.44 10.61 15.16l1.51 -2.83v2.39c0 1.4 0.92 2.26 2.33 2.26s1.93 -0.48 2.72 -1.82l3.48 -5.87c0.75 -1.28 0.23 -2.26 -1.45 -2.26C19.2 7.03 19.2 7.03 19.22 7.03z"/></svg>',
		'megaphone' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="fill: none" stroke="currentColor" stroke-width="1.5"><path d="M13.4999 14.0178C13.4903 15.6665 12.1509 17 10.5 17C8.84315 17 7.5 15.6569 7.5 14C7.5 13.6412 7.56297 13.2972 7.67847 12.9783M7.67847 12.9783L13.4999 14.0178L19 15V7L5 10V12.5L7.67847 12.9783Z"/></svg>',
	);

	/**
	 * Attach admin menu hooks.
	 *
	 * @internal
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'handle_admin_menu_registration' ), 0 );
		add_action( 'admin_menu', array( $this, 'handle_admin_menu' ), 30 );
	}

	/**
	 * Register WooCommerce's private icon collection before menu registration.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_admin_menu_registration() {
		// Keep explicit API checks until the WordPress PHPStan stubs include the icon API.
		if ( ! $this->is_supported() || ! function_exists( 'wp_register_icon_collection' ) || ! function_exists( 'wp_register_icon' ) || ! class_exists( '\WP_Icon_Collections_Registry' ) || ! class_exists( '\WP_Icons_Registry' ) ) {
			return;
		}

		if ( ! \WP_Icon_Collections_Registry::get_instance()->is_registered( 'woocommerce' ) ) {
			wp_register_icon_collection(
				'woocommerce',
				array(
					'label'  => __( 'WooCommerce', 'woocommerce' ),
					'public' => false,
				)
			);
		}

		$labels = array(
			'logo'      => __( 'WooCommerce', 'woocommerce' ),
			'megaphone' => __( 'Megaphone', 'woocommerce' ),
		);
		foreach ( self::ICONS as $name => $content ) {
			if ( ! \WP_Icons_Registry::get_instance()->is_registered( 'woocommerce/' . $name ) ) {
				wp_register_icon(
					'woocommerce/' . $name,
					array(
						'label'   => $labels[ $name ],
						'content' => $content,
					)
				);
			}
		}
	}

	/**
	 * Return a registered menu icon, retaining the legacy icon on older WordPress versions.
	 *
	 * @since 11.3.0
	 *
	 * @param string $icon_name Namespaced icon name.
	 * @param string $fallback  Legacy Dashicon class or SVG data URI.
	 * @return string
	 */
	public function get_icon( string $icon_name, string $fallback ): string {
		return $this->is_supported() && class_exists( '\WP_Icons_Registry' ) && \WP_Icons_Registry::get_instance()->is_registered( $icon_name ) ? $icon_name : $fallback;
	}

	/**
	 * Update post type menu icons without changing their REST API metadata or extension overrides.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_admin_menu() {
		global $menu;

		if ( ! $this->is_supported() || ! is_array( $menu ) ) {
			return;
		}

		$icons = array(
			'edit.php?post_type=product'    => array( 'dashicons-archive', 'core-admin/tag' ),
			'edit.php?post_type=shop_order' => array( 'dashicons-admin-post', 'core-admin/receipt' ),
		);
		foreach ( $menu as $index => $item ) {
			if ( isset( $item[2], $item[6], $icons[ $item[2] ] ) && $icons[ $item[2] ][0] === $item[6] ) {
				$menu[ $index ][6] = $this->get_icon( $icons[ $item[2] ][1], $item[6] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WordPress reads the $menu global when rendering the admin menu.
			}
		}
	}

	/**
	 * Check menu support as well as API availability, since Gutenberg can provide APIs on older WordPress.
	 *
	 * @return bool
	 */
	private function is_supported(): bool {
		return version_compare( get_bloginfo( 'version' ), '7.2-alpha', '>=' ) && function_exists( 'wp_is_valid_icon_name' );
	}
}
