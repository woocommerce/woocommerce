<?php
/**
 * BlockEditorUnifiedAssets class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Features;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Feature gate for unified WooCommerce block editor assets.
 */
final class BlockEditorUnifiedAssets {

	/**
	 * Feature Name.
	 */
	public const FEATURE_NAME = 'block_editor_unified_assets';

	/**
	 * Option that stores whether the feature is enabled.
	 */
	public const OPTION_NAME = 'woocommerce_feature_block_editor_unified_assets_enabled';

	/**
	 * Check whether unified block editor assets are enabled.
	 *
	 * @return bool True when unified block editor assets are enabled.
	 *
	 * @since 11.1.0
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_NAME );
	}
}
