<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * A variable product has no attributes used for variations, or a published
 * variation has one set to "any".
 *
 * @since 11.4.0
 */
class VariationAttributesNotSetCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'variation_attributes_not_set';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Variable products with variations not tied to an option', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'A variation set to "any" can\'t be listed as a distinct item with its own color or size.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Set the options on each variation.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::CHATGPT => array(
				'field'  => 'variant_dict',
				'effect' => self::EFFECT_GAPS,
			),
			FeedChannels::GOOGLE  => array(
				'field'  => 'color, size',
				'effect' => self::EFFECT_GAPS,
			),
			FeedChannels::COPILOT => array(
				'field'  => 'color, sizes',
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
		if ( ! $product instanceof \WC_Product_Variable ) {
			return false;
		}

		$has_variation_attribute = false;
		foreach ( $product->get_attributes() as $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute && $attribute->get_variation() ) {
				$has_variation_attribute = true;
				break;
			}
		}
		if ( ! $has_variation_attribute ) {
			return true;
		}

		foreach ( ProductFields::variations( $product ) as $variation ) {
			foreach ( $variation->get_attributes() as $value ) {
				if ( '' === (string) $value ) {
					return true;
				}
			}
		}
		return false;
	}
}
