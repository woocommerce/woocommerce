<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

/**
 * Enum-style class for the assignable POS staff presets.
 *
 * Presets are UI-curated bundles of `woocommerce_pos_*` capabilities (Cashier / Manager /
 * Admin); see Capabilities::capabilities_for_preset(). Kept under the POS
 * namespace rather than the shared `Enums` namespace because presets are a
 * UI-level grouping that may change as the granular-caps model matures, not a
 * stable store-wide vocabulary.
 *
 * Constant class rather than a native PHP enum so it stays within WooCommerce's
 * PHP 7.4 minimum.
 *
 * @since 11.0.0
 * @internal
 */
final class POSPreset {
	/**
	 * Cashier preset.
	 *
	 * @var string
	 */
	public const CASHIER = 'pos_cashier';

	/**
	 * Manager preset.
	 *
	 * @var string
	 */
	public const MANAGER = 'pos_manager';

	/**
	 * Admin preset.
	 *
	 * @var string
	 */
	public const ADMIN = 'pos_admin';

	/**
	 * All assignable presets, in ascending capability order.
	 *
	 * @return string[]
	 *
	 * @since 11.0.0
	 */
	public static function get_all(): array {
		return array(
			self::CASHIER,
			self::MANAGER,
			self::ADMIN,
		);
	}
}
