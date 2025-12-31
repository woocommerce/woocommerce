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
	 * 2. Site-level option (defaults to 'yes' for new installs)
	 *
	 * Existing sites have the option set to 'no' during update via
	 * wc_update_1000_disable_lazy_load_variations() in wc-update-functions.php.
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
		// Default is 'yes' (enabled) for new installs.
		// Existing sites get 'no' set during update for backward compatibility.
		return get_option( self::OPTION_NAME, 'yes' ) === 'yes';
	}
}
