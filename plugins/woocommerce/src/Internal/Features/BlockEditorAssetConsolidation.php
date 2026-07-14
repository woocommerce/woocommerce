<?php
/**
 * BlockEditorAssetConsolidation class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Features;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Feature gate for consolidated WooCommerce block editor assets.
 */
final class BlockEditorAssetConsolidation {

	/**
	 * Feature Name.
	 */
	public const FEATURE_NAME = 'block_editor_asset_consolidation';

	/**
	 * Option that stores whether the feature is enabled.
	 */
	public const OPTION_NAME = 'woocommerce_feature_block_editor_asset_consolidation_enabled';

	/**
	 * Check whether consolidated block editor assets are enabled.
	 *
	 * @return bool True when consolidated block editor assets are enabled.
	 *
	 * @since 11.1.0
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_NAME );
	}
}
