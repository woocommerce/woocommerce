<?php
/**
 * Admin menu icons from the WordPress icon registry.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin;

/**
 * Registers the WooCommerce icon collection and picks the admin menu icon for the running WordPress version.
 *
 * WordPress 7.2 renders registered icon names (e.g. 'core/chart-bar') as inline SVG in the admin menu.
 * Older versions keep the Dashicons and base64 SVGs WooCommerce has always used.
 *
 * TODO(WOOPLUG-7938): prototype helper, Ján to decide on DI, naming and tests.
 *
 * @internal
 */
final class MenuIcons {

	/**
	 * Icon collection slug for WooCommerce icons.
	 */
	public const COLLECTION = 'woocommerce';

	/**
	 * SVG markup of the icons registered in the WooCommerce collection, keyed by unqualified icon name.
	 *
	 * TODO(WOOPLUG-7938): `megaphone` is copied from @wordpress/icons because Core does not register it in the `core`
	 * collection. Switch to `core/megaphone` if Core adds it, and drop the copy.
	 * TODO(WOOPLUG-7938): `logo` is the W mark scaled to 18px wide on the 24px grid. Needs design sign-off.
	 */
	private const ICONS = array(
		'logo'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19.22 7.03c-0.9 0 -1.49 0.29 -2.01 1.28L14.82 12.82V8.81c0 -1.19 -0.57 -1.78 -1.61 -1.78s-1.49 0.36 -2.01 1.36L8.93 12.82V8.86c0 -1.28 -0.52 -1.82 -1.8 -1.82H4.53C3.54 7.03 3 7.49 3 8.33s0.52 1.34 1.49 1.34h1.07v5.05c0 1.42 0.96 2.26 2.35 2.26S9.92 16.44 10.61 15.16l1.51 -2.83v2.39c0 1.4 0.92 2.26 2.33 2.26s1.93 -0.48 2.72 -1.82l3.48 -5.87c0.75 -1.28 0.23 -2.26 -1.45 -2.26C19.2 7.03 19.2 7.03 19.22 7.03z"/></svg>',
		'megaphone' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="fill: none" stroke="currentColor" stroke-width="1.5"><path d="M13.4999 14.0178C13.4903 15.6665 12.1509 17 10.5 17C8.84315 17 7.5 15.6569 7.5 14C7.5 13.6412 7.56297 13.2972 7.67847 12.9783M7.67847 12.9783L13.4999 14.0178L19 15V7L5 10V12.5L7.67847 12.9783Z" /></svg>',
	);

	/**
	 * Whether the admin menu accepts registered icon names. `wp_is_icon_name()` was added together with that support in
	 * WordPress 7.2. `wp_get_icon()` alone is not enough, it already exists in 7.1.
	 *
	 * @return bool
	 */
	public static function is_supported(): bool {
		return function_exists( 'wp_is_icon_name' ) && function_exists( 'wp_register_icon_collection' );
	}

	/**
	 * Returns the registered icon name when the admin menu supports it, otherwise the fallback.
	 *
	 * @param string $icon_name Namespaced icon name, e.g. 'core/chart-bar' or 'woocommerce/megaphone'.
	 * @param string $fallback  Icon used on older WordPress versions: a Dashicons class or a base64 SVG data URI.
	 * @return string
	 */
	public static function get( string $icon_name, string $fallback ): string {
		return self::is_supported() ? $icon_name : $fallback;
	}

	/**
	 * Registers the WooCommerce icon collection and its icons.
	 *
	 * @internal
	 */
	public static function register(): void {
		// Checked inline rather than via is_supported() so static analysis sees the guard.
		if ( ! function_exists( 'wp_is_icon_name' ) || ! function_exists( 'wp_register_icon_collection' ) || ! function_exists( 'wp_register_icon' ) ) {
			return;
		}

		wp_register_icon_collection(
			self::COLLECTION,
			array(
				'label'  => __( 'WooCommerce', 'woocommerce' ),
				'public' => false,
			)
		);

		$labels = array(
			'logo'      => __( 'WooCommerce', 'woocommerce' ),
			'megaphone' => __( 'Megaphone', 'woocommerce' ),
		);

		foreach ( self::ICONS as $name => $content ) {
			wp_register_icon(
				self::COLLECTION . '/' . $name,
				array(
					'label'   => $labels[ $name ],
					'content' => $content,
				)
			);
		}
	}
}
