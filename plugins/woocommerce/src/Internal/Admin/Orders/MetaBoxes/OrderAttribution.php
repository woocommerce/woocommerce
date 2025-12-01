<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

use Automattic\WooCommerce\Internal\Traits\OrderAttributionMeta;
use WC_Order;

/**
 * Class OrderAttribution
 *
 * @since 8.5.0
 */
class OrderAttribution {

	use OrderAttributionMeta;

	/**
	 * OrderAttribution constructor.
	 */
	public function __construct() {
		$this->set_fields_and_prefix();
	}

	/**
	 * Format the meta data for display.
	 *
	 * @since 8.5.0
	 *
	 * @param array $meta The array of meta data to format.
	 *
	 * @return void
	 */
	public function format_meta_data( array &$meta ) {

		if ( array_key_exists( 'device_type', $meta ) ) {

			switch ( $meta['device_type'] ) {
				case 'Mobile':
					$meta['device_type'] = __( 'Mobile', 'woocommerce' );
					break;
				case 'Tablet':
					$meta['device_type'] = __( 'Tablet', 'woocommerce' );
					break;
				case 'Desktop':
					$meta['device_type'] = __( 'Desktop', 'woocommerce' );
					break;

				default:
					$meta['device_type'] = __( 'Unknown', 'woocommerce' );
					break;
			}
		}

	}

	/**
	 * Output the attribution data metabox for the order.
	 *
	 * @since 8.5.0
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	public function output( WC_Order $order ) {
		$meta = $this->filter_meta_data( $order->get_meta_data() );

		$this->format_meta_data( $meta );

		// No more details if there is only the origin value - this is for unknown source types.
		$has_more_details = array( 'origin' ) !== array_keys( $meta );

		// For direct, web admin, mobile app or pos orders, also don't show more details.
		$simple_sources = array( 'typein', 'admin', 'mobile_app', 'pos' );
		if ( isset( $meta['source_type'] ) && in_array( $meta['source_type'], $simple_sources, true ) ) {
			$has_more_details = false;
		}

		$template_data = array(
			'meta'             => $meta,
			'has_more_details' => $has_more_details,
		);
		wc_get_template( 'order/attribution-details.php', $template_data );
	}
}
