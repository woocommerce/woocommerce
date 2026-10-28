<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * Description and short description are both empty once shortcodes, HTML and
 * entities are removed.
 *
 * @see https://support.google.com/merchants/answer/6324468
 *
 * @since 11.4.0
 */
class MissingProductTextCheck extends AbstractReadinessCheck {

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'missing_product_text';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products with no description', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'ChatGPT\'s spec requires a description, so the product can\'t be listed there without one. Google and Copilot list it with a warning.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Write a description that says what the product is and who it is for.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::CHATGPT => array(
				'field'  => 'description',
				'effect' => self::EFFECT_NOT_LISTABLE,
			),
			FeedChannels::GOOGLE  => array(
				'field'  => 'description',
				'effect' => self::EFFECT_GAPS,
			),
			FeedChannels::COPILOT => array(
				'field'  => 'description',
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
		return '' === ProductFields::text( $product );
	}
}
