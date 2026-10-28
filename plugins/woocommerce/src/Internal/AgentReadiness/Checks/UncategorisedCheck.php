<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * No category other than the default product category.
 *
 * @since 11.4.0
 */
class UncategorisedCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'uncategorised';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products with only the default category', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'Agents narrow searches by category first; an uncategorised product is harder to find.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Assign a category.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::CHATGPT => array(
				'field'  => 'product_category',
				'effect' => self::EFFECT_GAPS,
			),
			FeedChannels::GOOGLE  => array(
				'field'  => 'product_type',
				'effect' => self::EFFECT_GAPS,
			),
			FeedChannels::COPILOT => array(
				'field'  => 'productType',
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
		$default = (int) get_option( 'default_product_cat', 0 );
		return empty( array_diff( array_map( 'intval', $product->get_category_ids() ), array( $default ) ) );
	}
}
