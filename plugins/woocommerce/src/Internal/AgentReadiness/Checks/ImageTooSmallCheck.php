<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * The main image is under MIN_PIXELS on either side. An image with no recorded
 * size is not flagged. Google requires 500 x 500 from January 31, 2027; before
 * that it is a gap.
 *
 * @see https://support.google.com/merchants/answer/6324350
 *
 * @since 11.4.0
 */
class ImageTooSmallCheck extends AbstractReadinessCheck {

	public const MIN_PIXELS = 500;

	public const GOOGLE_REQUIRED_FROM = '2027-01-31';

	/**
	 * Unix time the Google effect is judged at, or null for now.
	 *
	 * @var int|null
	 */
	private ?int $now;

	/**
	 * Constructor.
	 *
	 * @param int|null $now Unix time to judge Google's deadline at; null for the current time.
	 */
	public function __construct( ?int $now = null ) {
		$this->now = $now;
	}

	/**
	 * Check id.
	 */
	public function id(): string {
		return 'image_too_small';
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return __( 'Products whose main image is under 500 x 500 pixels', 'woocommerce' );
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return __( 'Google\'s spec requires a main image of at least 500 x 500 pixels from January 31, 2027; a smaller one won\'t appear on Google then.', 'woocommerce' );
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return __( 'Upload a main image at least 500 x 500 pixels.', 'woocommerce' );
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return array(
			FeedChannels::GOOGLE => array(
				'field'  => 'image_link',
				'effect' => gmdate( 'Y-m-d', $this->now ?? time() ) < self::GOOGLE_REQUIRED_FROM ? self::EFFECT_GAPS : self::EFFECT_NOT_LISTABLE,
			),
		);
	}

	/**
	 * Whether the product has this problem: any image it lists with, for a variable product any
	 * variation's, is too small. Images without a recorded size are not judged.
	 *
	 * @param \WC_Product $product Published product.
	 * @param ProductScan $scan    Catalog-wide lookups.
	 */
	public function fails( \WC_Product $product, ProductScan $scan ): bool {
		foreach ( ProductFields::listing_image_ids( $product ) as $image_id ) {
			$metadata = wp_get_attachment_metadata( $image_id );
			if ( is_array( $metadata ) && ! empty( $metadata['width'] ) && ! empty( $metadata['height'] )
				&& ( (int) $metadata['width'] < self::MIN_PIXELS || (int) $metadata['height'] < self::MIN_PIXELS ) ) {
				return true;
			}
		}
		return false;
	}
}
