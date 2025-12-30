<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\Utils;

use WP_Block;

/**
 * Utility class for variation data loading configuration.
 *
 * @internal
 */
class VariationDataUtils {
	/**
	 * Option name for the site-level lazy load setting.
	 */
	const OPTION_NAME = 'woocommerce_blocks_lazy_load_variations';

	/**
	 * Check if lazy loading of variation data is enabled.
	 *
	 * Checks in order:
	 * 1. Block context (from parent block attribute)
	 * 2. Site-level option (defaults to 'no' for existing sites)
	 *
	 * @param WP_Block|null $block The block instance to check context from.
	 * @return bool Whether lazy loading is enabled.
	 */
	public static function is_enabled( ?WP_Block $block = null ): bool {
		// Check block context first (set by parent block).
		if ( $block && isset( $block->context['woocommerce/lazyLoadVariations'] ) ) {
			return (bool) $block->context['woocommerce/lazyLoadVariations'];
		}

		// Fall back to site-level option.
		// Default is 'no' for backward compatibility with existing sites.
		// New installs get 'yes' set via woocommerce_newly_installed hook.
		return get_option( self::OPTION_NAME, 'no' ) === 'yes';
	}

	/**
	 * Enable lazy loading for newly installed sites.
	 *
	 * This is called via the woocommerce_newly_installed hook to enable
	 * lazy loading by default for new WooCommerce installations.
	 *
	 * @return void
	 */
	public static function enable_for_new_install(): void {
		update_option( self::OPTION_NAME, 'yes' );
	}
}
