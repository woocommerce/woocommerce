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
	 * Whether the feature is enabled, read once per request. Null until the first read.
	 *
	 * @var bool|null
	 */
	private static ?bool $enabled = null;

	/**
	 * Check whether unified block editor assets are enabled.
	 *
	 * Every WooCommerce block asks this, so the value is read once per request.
	 *
	 * @return bool True when unified block editor assets are enabled.
	 *
	 * @since 11.1.0
	 */
	public static function is_enabled(): bool {
		if ( null === self::$enabled ) {
			self::$enabled = FeaturesUtil::feature_is_enabled( self::FEATURE_NAME );
		}

		return self::$enabled;
	}
}
