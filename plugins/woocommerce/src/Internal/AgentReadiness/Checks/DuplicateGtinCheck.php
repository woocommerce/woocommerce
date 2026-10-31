<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * A GTIN on the product or its variations is also on another published
 * product. Only Google's spec addresses this.
 *
 * @see https://support.google.com/merchants/answer/6324461
 *
 * @since 11.4.0
 */
class DuplicateGtinCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'duplicate_gtin';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products sharing a GTIN with another product', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'Google asks for one GTIN per product and variant; a shared one can merge different products into one item.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Give each product its own GTIN.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::GOOGLE => array(
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
			if ( $scan->is_shared_gtin( $gtin ) ) {
				return true;
			}
		}
		return false;
	}
}
