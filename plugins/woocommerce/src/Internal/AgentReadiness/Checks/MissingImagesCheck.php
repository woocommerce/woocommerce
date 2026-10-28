<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * No featured image, or the featured image no longer exists.
 *
 * @since 11.4.0
 */
class MissingImagesCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'missing_images';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products with no main image', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'Every channel\'s spec requires an image link, so the product can\'t be listed without one.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Add a main product image.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::CHATGPT => array(
				'field'  => 'image_url',
				'effect' => self::EFFECT_NOT_LISTABLE,
			),
			FeedChannels::GOOGLE  => array(
				'field'  => 'image_link',
				'effect' => self::EFFECT_NOT_LISTABLE,
			),
			FeedChannels::COPILOT => array(
				'field'  => 'imageLink',
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
		return 0 === ProductFields::image_id( $product );
	}
}
