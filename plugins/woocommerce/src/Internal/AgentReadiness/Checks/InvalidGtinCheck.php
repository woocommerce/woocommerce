<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * A GTIN on the product or its variations is not 8, 12, 13 or 14 digits, or
 * its check digit fails.
 *
 * @since 11.4.0
 */
class InvalidGtinCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'invalid_gtin';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products with an invalid GTIN', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'Google\'s spec requires a submitted GTIN to be valid, so the product can\'t be listed there with a wrong one.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Correct the GTIN; it must be 8, 12, 13 or 14 digits with a valid check digit. Convert an ISBN-10 to its ISBN-13.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::CHATGPT => array(
				'field'  => 'gtin',
				'effect' => self::EFFECT_GAPS,
			),
			FeedChannels::GOOGLE  => array(
				'field'  => 'gtin',
				'effect' => self::EFFECT_NOT_LISTABLE,
			),
			FeedChannels::COPILOT => array(
				'field'  => 'gtin',
				'effect' => self::EFFECT_GAPS,
			),
		);
	}

	/**
	 * Whether the product has this problem.
	 *
	 * @param \WC_Product $product Published product.
	 * @param ProductScan $scan    Catalog-wide lookups.
	 */
	public function fails( \WC_Product $product, ProductScan $scan ): bool {
		if ( ! ProductFields::needs_identifiers( $product ) ) {
			return false;
		}
		foreach ( ProductFields::gtins( $product ) as $gtin ) {
			if ( ! ProductFields::is_valid_gtin( $gtin ) ) {
				return true;
			}
		}
		return false;
	}
}
