<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * No positive regular or active price. A variable product fails when any
 * published variation has neither; it can't be listed only when none has one,
 * since the priced variations still list. Bundles, composites and grouped
 * products are priced from their items and are not checked.
 *
 * @since 11.4.0
 */
class MissingRegularPriceCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'missing_regular_price';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products with no price', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'Every channel\'s spec requires a price, and Google rejects a product without one. A variable product still lists its priced variations.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Set a price on the product, or on each variation.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return self::on_every_channel( 'price', self::EFFECT_NOT_LISTABLE );
	}

	/**
	 * Whether the product has this problem.
	 *
	 * @param \WC_Product $product Published product.
	 * @param ProductScan $scan    Catalog-wide lookups.
	 */
	public function fails( \WC_Product $product, ProductScan $scan ): bool {
		if ( ! ProductFields::has_own_price( $product ) ) {
			return false;
		}
		$items = $product instanceof \WC_Product_Variable ? ProductFields::variations( $product ) : array( $product );
		if ( empty( $items ) ) {
			return true;
		}
		foreach ( $items as $item ) {
			if ( ! self::has_positive_price( $item ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * A gap, not a missing listing, for a variable product with a priced variation.
	 *
	 * @param \WC_Product                                      $product  Product that fails the rule.
	 * @param array<string, array{field:string,effect:string}> $channels The rule's channel map.
	 * @return array<string, array{field:string,effect:string}>
	 */
	public function channels_for( \WC_Product $product, array $channels ): array {
		foreach ( ProductFields::variations( $product ) as $variation ) {
			if ( self::has_positive_price( $variation ) ) {
				foreach ( $channels as $channel => $spec ) {
					$channels[ $channel ]['effect'] = self::EFFECT_GAPS;
				}
				break;
			}
		}
		return $channels;
	}

	/**
	 * Whether the product has a positive regular price or a positive active
	 * price. An import that stored only the selling price still has a price.
	 *
	 * @param \WC_Product $product Product or variation.
	 */
	private static function has_positive_price( \WC_Product $product ): bool {
		foreach ( array( $product->get_regular_price( 'edit' ), $product->get_price( 'edit' ) ) as $price ) {
			if ( '' !== (string) $price && (float) $price > 0 ) {
				return true;
			}
		}
		return false;
	}
}
