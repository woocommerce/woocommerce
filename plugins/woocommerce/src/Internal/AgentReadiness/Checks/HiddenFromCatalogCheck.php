<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * Catalog visibility keeps the product out of the shop catalog ("Search
 * results only" or "Hidden"). Often deliberate, so it is reported, never fixed.
 * ChatGPT's is_eligible_search assumes a feed maps visibility the same way.
 *
 * @since 11.4.0
 */
class HiddenFromCatalogCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'hidden_from_catalog';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products hidden from the shop catalog', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'A catalog for shopping agents leaves out products hidden from the shop.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Check whether these products should stay hidden.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::CHATGPT => array(
				'field'  => 'is_eligible_search',
				'effect' => self::EFFECT_NOT_LISTABLE,
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
		return in_array( $product->get_catalog_visibility(), array( 'search', 'hidden' ), true );
	}
}
