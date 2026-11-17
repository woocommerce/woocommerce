<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * A product, or one of its published variations, without its own GTIN. Each
 * variation is listed as its own item, so it needs its own GTIN: the parent's
 * identifies a different item, and a variable parent needs none, even when
 * none of its variations is published. Gift cards
 * and virtual or downloadable products or variations are not asked for one.
 * Google accepts a brand and MPN instead, so Google is left out when the
 * product has both.
 *
 * @since 11.4.0
 */
class MissingGtinCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'missing_gtin';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products missing a GTIN', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'Channels match products to searches and to other sellers by GTIN; without one the product is harder to find. Each variation lists as its own item, so it needs its own GTIN.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Add the GTIN (UPC, EAN or ISBN) from the packaging or supplier to the product, or to each variation of a variable product; never make one up.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return self::on_every_channel( 'gtin', self::EFFECT_GAPS );
	}

	/**
	 * Drop Google when the product has a brand and an MPN.
	 *
	 * @param \WC_Product                                      $product  Product that fails the rule.
	 * @param array<string, array{field:string,effect:string}> $channels The rule's channel map.
	 * @return array<string, array{field:string,effect:string}>
	 */
	public function channels_for( \WC_Product $product, array $channels ): array {
		$brand = ProductFields::brand( $product );
		if ( '' === $brand ) {
			$brand = ProductFields::google_feed_brand( $product );
		}
		if ( '' !== $brand && '' !== ProductFields::mpn( $product ) ) {
			unset( $channels[ FeedChannels::GOOGLE ] );
		}
		return $channels;
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
		// A variable product with no published variation lists nothing, so it needs no GTIN.
		$items = $product instanceof \WC_Product_Variable ? ProductFields::variations( $product ) : array( $product );
		foreach ( $items as $item ) {
			if ( ProductFields::needs_identifiers( $item ) && '' === ProductFields::normalise_gtin( (string) $item->get_global_unique_id() ) ) {
				return true;
			}
		}
		return false;
	}
}
