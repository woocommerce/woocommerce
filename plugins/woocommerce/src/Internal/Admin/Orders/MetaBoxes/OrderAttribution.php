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

		// If we don't have any meta to show, return.
		if ( empty( $meta ) ) {
			esc_html_e( 'No order source data available.', 'woocommerce' );
			return;
		}

		$this->format_meta_data( $meta );

		$template_data = array(
			'meta'             => $meta,
			// Only show more details toggle if there is more than just the origin.
			'has_more_details' => array( 'origin' ) !== array_keys( $meta ),
		);
		wc_get_template( 'order/attribution-data-fields.php', $template_data );
	}
}
