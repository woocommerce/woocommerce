<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * A published variation of a variable product has no image of its own.
 *
 * @since 11.4.0
 */
class MissingVariationImagesCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'missing_variation_images';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Variable products with variations missing images', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'Each variation lists as its own item; without its own image it shows the parent image, which may not match the color or finish.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Add an image to each variation.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::CHATGPT => array(
				'field'  => 'image_url',
				'effect' => self::EFFECT_GAPS,
			),
			FeedChannels::GOOGLE  => array(
				'field'  => 'image_link',
				'effect' => self::EFFECT_GAPS,
			),
			FeedChannels::COPILOT => array(
				'field'  => 'imageLink',
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
		foreach ( ProductFields::variations( $product ) as $variation ) {
			if ( (int) $variation->get_image_id( 'edit' ) <= 0 ) {
				return true;
			}
		}
		return false;
	}
}
