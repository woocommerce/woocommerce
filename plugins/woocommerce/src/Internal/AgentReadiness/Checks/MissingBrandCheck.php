<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * No brand term and no brand attribute. Gift cards and virtual or
 * downloadable products are not asked for one. A brand set only in Google for
 * WooCommerce reaches Google, so Google is left out for those products.
 *
 * @since 11.4.0
 */
class MissingBrandCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'missing_brand';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products with no brand', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'ChatGPT\'s spec requires a brand, so the product can\'t be listed there without one. Google and Copilot list it with a warning.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Add a brand. Google allows an empty brand only when the product truly has none.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::CHATGPT => array(
				'field'  => 'brand',
				'effect' => self::EFFECT_NOT_LISTABLE,
			),
			FeedChannels::GOOGLE  => array(
				'field'  => 'brand',
				'effect' => self::EFFECT_GAPS,
			),
			FeedChannels::COPILOT => array(
				'field'  => 'brand',
				'effect' => self::EFFECT_GAPS,
			),
		);
	}

	/**
	 * Drop Google when Google for WooCommerce sends its own brand.
	 *
	 * @param \WC_Product                                      $product  Product that fails the rule.
	 * @param array<string, array{field:string,effect:string}> $channels The rule's channel map.
	 * @return array<string, array{field:string,effect:string}>
	 */
	public function channels_for( \WC_Product $product, array $channels ): array {
		if ( '' !== ProductFields::google_feed_brand( $product ) ) {
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
		return ProductFields::needs_identifiers( $product ) && '' === ProductFields::brand( $product );
	}
}
