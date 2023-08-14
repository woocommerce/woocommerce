<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

use Automattic\WooCommerce\Internal\Traits\SourceAttributionMeta;
use WC_Order;

/**
 * Class SourceAttribution
 *
 * @since x.x.x
 */
class SourceAttribution {

	use SourceAttributionMeta;

	/**
	 * SourceAttribution constructor.
	 */
	public function __construct() {
		/**
		 * Filter the fields to show in the source data metabox.
		 *
		 * @since x.x.x
		 *
		 * @param string[] $fields The fields to show.
		 */
		$this->fields = (array) apply_filters( 'wc_order_source_attribution_tracking_fields', $this->default_fields );
		$this->set_field_prefix();
	}

	/**
	 * Output the source data metabox for the order.
	 *
	 * @since x.x.x
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

		include dirname( WC_PLUGIN_FILE ) . '/templates/order/source-data-fields.php';
	}
}
