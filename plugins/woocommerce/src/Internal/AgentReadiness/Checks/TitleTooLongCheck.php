<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * Title over MAX_LENGTH characters, the limit in all three channel specs.
 *
 * @since 11.4.0
 */
class TitleTooLongCheck extends AbstractReadinessCheck {

	public const MAX_LENGTH = 150;

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'title_too_long';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products with titles over 150 characters', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'Google truncates titles over 150 characters; ChatGPT and Copilot cap titles at 150.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Shorten the title, keeping the brand and product type first.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return self::on_every_channel( 'title', self::EFFECT_GAPS );
	}

	/**
	 * Whether the product has this problem.
	 *
	 * @param \WC_Product $product Published product.
	 * @param ProductScan $scan    Catalog-wide lookups.
	 */
	public function fails( \WC_Product $product, ProductScan $scan ): bool {
		return mb_strlen( $product->get_name() ) > self::MAX_LENGTH;
	}
}
