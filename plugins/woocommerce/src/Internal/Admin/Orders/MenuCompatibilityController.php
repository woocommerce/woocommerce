<?php
namespace Automattic\WooCommerce\Internal\Admin\Orders;

/**
 * Handles hook and screen ID backwards compatibility when the Orders menu is promoted to top-level.
 *
 * When the Orders menu moves from being a submenu under "WooCommerce" to a top-level menu item,
 * WordPress generates different hook names and screen IDs. For example:
 * - Submenu hook: `woocommerce_page_wc-orders`
 * - Top-level hook: `toplevel_page_wc-orders`
 *
 * This class ensures plugins and extensions that hook into the original submenu-based hook names
 * continue to work correctly after the menu is promoted to top-level.
 *
 * @since 10.5.0
 */
class MenuCompatibilityController {

	/**
	 * Hook prefixes to map for backwards compatibility.
	 *
	 * @var array
	 */
	private const HOOK_PREFIXES = array(
		'load-',
		'admin_print_styles-',
		'admin_print_scripts-',
		'admin_head-',
		'admin_footer-',
		'admin_print_footer_scripts-',
	);

	/**
	 * Register backwards compatibility hooks for the top-level Orders menu.
	 *
	 * When Orders becomes a top-level menu, WordPress uses different hook names
	 * (e.g., `toplevel_page_wc-orders` instead of `woocommerce_page_wc-orders`).
	 * This method ensures plugins hooked to the original submenu-based names still fire.
	 *
	 * Also modifies the screen ID and JavaScript `adminpage` variable so plugins
	 * checking these values continue to work.
	 *
	 * @since 10.5.0
	 *
	 * @param array $hook_mappings Array mapping actual hooks to expected hooks
	 *                             (e.g., 'toplevel_page_wc-orders' => 'woocommerce_page_wc-orders').
	 */
	public function register_hook_compatibility( array $hook_mappings ): void {
		foreach ( $hook_mappings as $actual_hook => $expected_hook ) {
			$this->register_prefixed_hooks( $actual_hook, $expected_hook );
			$this->register_base_hook( $actual_hook, $expected_hook );
		}

		$this->register_screen_id_compatibility( $hook_mappings );
	}

	/**
	 * Register compatibility hooks for each prefix.
	 *
	 * @param string $actual_hook   The actual hook suffix.
	 * @param string $expected_hook The expected hook suffix.
	 */
	private function register_prefixed_hooks( string $actual_hook, string $expected_hook ): void {
		foreach ( self::HOOK_PREFIXES as $prefix ) {
			add_action(
				"{$prefix}{$actual_hook}",
				function () use ( $expected_hook, $prefix ) {
					$expected_full_hook = "{$prefix}{$expected_hook}";
					// Only fire if we're not already in the expected hook (prevent infinite loops).
					if ( ! doing_action( $expected_full_hook ) ) {
						/**
						 * Fires compatibility hooks for the orders page.
						 *
						 * @since 10.5.0
						 */
						do_action( $expected_full_hook ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- WordPress core uses hyphens for some hook patterns.
					}
				},
				1
			);
		}
	}

	/**
	 * Register the base hook (without prefix) compatibility.
	 *
	 * @param string $actual_hook   The actual hook suffix.
	 * @param string $expected_hook The expected hook suffix.
	 */
	private function register_base_hook( string $actual_hook, string $expected_hook ): void {
		add_action(
			$actual_hook,
			function () use ( $expected_hook ) {
				if ( ! doing_action( $expected_hook ) ) {
					/**
					 * Fires for the orders page hook.
					 *
					 * @since 10.5.0
					 */
					do_action( $expected_hook );
				}
			},
			1
		);
	}

	/**
	 * Register screen ID compatibility to rewrite screen IDs and update the JavaScript adminpage variable.
	 *
	 * @param array $hook_mappings Array of actual_hook => expected_hook mappings.
	 */
	private function register_screen_id_compatibility( array $hook_mappings ): void {
		add_action(
			'current_screen',
			function ( $screen ) use ( $hook_mappings ) {
				if ( ! is_object( $screen ) || ! property_exists( $screen, 'id' ) ) {
					return;
				}

				if ( isset( $hook_mappings[ $screen->id ] ) ) {
					// Store the original ID and base in case something needs them.
					$screen->original_id   = $screen->id;
					$screen->original_base = $screen->base;
					// Change the screen ID and base to what plugins expect.
					$screen->id   = $hook_mappings[ $screen->original_id ];
					$screen->base = $hook_mappings[ $screen->original_id ];

					$this->update_adminpage_js_variable( $screen->id );
				}
			},
			1
		);
	}

	/**
	 * Update JavaScript adminpage variable to match the expected screen ID.
	 *
	 * WordPress sets this in admin-header.php from the sanitized hook suffix.
	 * We need to update it so JavaScript code that relies on this variable works correctly.
	 *
	 * @param string $screen_id The expected screen ID.
	 */
	private function update_adminpage_js_variable( string $screen_id ): void {
		$adminpage = preg_replace( '/[^a-z0-9_-]+/i', '-', $screen_id );
		add_action(
			'admin_head',
			function () use ( $adminpage ) {
				printf(
					'<script>window.adminpage = %s;</script>' . "\n",
					wp_json_encode( $adminpage )
				);
			},
			1
		);
	}
}
