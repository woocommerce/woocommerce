<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\WooCommerce\Vendor\Detection\MobileDetect;
use Automattic\WooCommerce\Admin\API\Reports\Controller as ReportsController;
use Exception;
use WC_Meta_Data;
use WC_Order;
use WP_Post;

/**
 * Trait OrderAttributionMeta
 *
 * @since 8.5.0
 *
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 */
trait OrderAttributionMeta {

	/** @var string[] */
	private $default_fields = array(
		// main fields.
		'type',
		'url',

		// utm fields.
		'utm_campaign',
		'utm_source',
		'utm_medium',
		'utm_content',
		'utm_id',
		'utm_term',

		// additional fields.
		'session_entry',
		'session_start_time',
		'session_pages',
		'session_count',
		'user_agent',
	);

	/** @var array */
	private $fields = array();

	/** @var string */
	private $field_prefix = '';

	/**
	 * Get the device type based on the other meta fields.
	 *
	 * @param array $values The meta values.
	 *
	 * @return string The device type.
	 */
	protected function get_device_type( array $values ): string {
		$detector = new MobileDetect( array(), $values['user_agent'] );

		if ( $detector->isMobile() ) {
			return 'Mobile';
		} elseif ( $detector->isTablet() ) {
			return 'Tablet';
		} else {
			return 'Desktop';
		}
	}

	/**
	 * Set the meta fields and the field prefix.
	 *
	 * @return void
	 */
	private function set_fields_and_prefix() {
		/**
		 * Filter the fields to show in the source data metabox.
		 *
		 * @since 8.5.0
		 *
		 * @param string[] $fields The fields to show.
		 */
		$this->fields = (array) apply_filters( 'wc_order_attribution_tracking_fields', $this->default_fields );
		$this->set_field_prefix();
	}

	/**
	 * Set the meta prefix for our fields.
	 *
	 * @return void
	 */
	private function set_field_prefix(): void {
		/**
		 * Filter the prefix for the meta fields.
		 *
		 * @since 8.5.0
		 *
		 * @param string $prefix The prefix for the meta fields.
		 */
		$prefix = (string) apply_filters(
			'wc_order_attribution_tracking_field_prefix',
			'wc_order_attribution_'
		);

		// Remove leading and trailing underscores.
		$prefix = trim( $prefix, '_' );

		// Ensure the prefix ends with _, and set the prefix.
		$this->field_prefix = "{$prefix}_";
	}

	/**
	 * Filter an order's meta data to only the keys that we care about.
	 *
	 * Sets the origin value based on the source type.
	 *
	 * @param WC_Meta_Data[] $meta The meta data.
	 *
	 * @return array
	 */
	private function filter_meta_data( array $meta ): array {
		$return = array();
		$prefix = $this->get_meta_prefixed_field( '' );

		foreach ( $meta as $item ) {
			if ( str_starts_with( $item->key, $prefix ) ) {
				$return[ $this->unprefix_meta_field( $item->key ) ] = $item->value;
			}
		}

		// Determine the device type from the user agent.
		if ( ! array_key_exists( 'device_type', $return ) && array_key_exists( 'user_agent', $return ) ) {
			$return['device_type'] = $this->get_device_type( $return );
		}

		// Determine the origin based on source type and referrer.
		$source_type      = $return['type'] ?? '';
		$source           = $return['utm_source'] ?? '';
		$return['origin'] = $this->get_origin_label( $source_type, $source, true );

		return $return;
	}

	/**
	 * Get the field name with the appropriate prefix.
	 *
	 * @param string $field Field name.
	 *
	 * @return string The prefixed field name.
	 */
	private function get_prefixed_field( $field ): string {
		return "{$this->field_prefix}{$field}";
	}

	/**
	 * Get the field name with the meta prefix.
	 *
	 * @param string $field The field name.
	 *
	 * @return string The prefixed field name.
	 */
	private function get_meta_prefixed_field( string $field ): string {
		// Map some of the fields to the correct meta name.
		if ( 'type' === $field ) {
			$field = 'source_type';
		} elseif ( 'url' === $field ) {
			$field = 'referrer';
		}

		return "_{$this->get_prefixed_field( $field )}";
	}

	/**
	 * Remove the meta prefix from the field name.
	 *
	 * @param string $field The prefixed field.
	 *
	 * @return string
	 */
	private function unprefix_meta_field( string $field ): string {
		$return = str_replace( "_{$this->field_prefix}", '', $field );

		// Map some of the fields to the correct meta name.
		if ( 'source_type' === $return ) {
			$return = 'type';
		} elseif ( 'referrer' === $return ) {
			$return = 'url';
		}

		return $return;
	}

	/**
	 * Get the order object with HPOS compatibility.
	 *
	 * @param WC_Order|WP_Post|int $post_or_order The post ID or object.
	 *
	 * @return WC_Order The order object
	 * @throws Exception When the order isn't found.
	 */
	private function get_hpos_order_object( $post_or_order ) {
		// If we've already got an order object, just return it.
		if ( $post_or_order instanceof WC_Order ) {
			return $post_or_order;
		}

		// If we have a post ID, get the post object.
		if ( is_numeric( $post_or_order ) ) {
			$post_or_order = wc_get_order( $post_or_order );
		}

		// Throw an exception if we don't have an order object.
		if ( ! $post_or_order instanceof WC_Order ) {
			throw new Exception( __( 'Order not found.', 'woocommerce' ) );
		}

		return $post_or_order;
	}


	/**
	 * Map posted, prefixed values to fields.
	 * Used for the classic forms.
	 *
	 * @param array $raw_values The raw values from the POST form.
	 *
	 * @return array
	 */
	private function get_unprefixed_fields( array $raw_values = array() ): array {
		$values = array();

		// Look through each field in POST data.
		foreach ( $this->fields as $field ) {
			$values[ $field ] = $raw_values[ $this->get_prefixed_field( $field ) ] ?? '(none)';
		}

		return $values;
	}

	/**
	 * Map submitted values to meta values.
	 *
	 * @param array $raw_values The raw (unprefixed) values from the submitted data.
	 *
	 * @return array
	 */
	private function get_source_values( array $raw_values = array() ): array {
		$values = array();

		// Look through each field in given data.
		foreach ( $this->fields as $field ) {
			$value = sanitize_text_field( wp_unslash( $raw_values[ $field ] ) );
			if ( '(none)' === $value ) {
				continue;
			}

			$values[ $field ] = $value;
		}

		// Set the device type if possible using the user agent.
		if ( array_key_exists( 'user_agent', $values ) && ! empty( $values['user_agent'] ) ) {
			$values['device_type'] = $this->get_device_type( $values );
		}

		return $values;
	}

	/**
	 * Get the label for the Order origin with placeholder where appropriate. Can be
	 * translated (for DB / display) or untranslated (for Tracks).
	 *
	 * @param string $source_type The source type.
	 * @param string $source      The source.
	 * @param bool   $translated  Whether the label should be translated.
	 *
	 * @return string
	 */
	private function get_origin_label( string $source_type, string $source, bool $translated = true ): string {
		// Set up the label based on the source type.
		switch ( $source_type ) {
			case 'utm':
				$label = $translated ?
					/* translators: %s is the source value */
					__( 'Source: %s', 'woocommerce' )
					: 'Source: %s';
				break;
			case 'organic':
				$label = $translated ?
					/* translators: %s is the source value */
					__( 'Organic: %s', 'woocommerce' )
					: 'Organic: %s';
				break;
			case 'referral':
				$label = $translated ?
					/* translators: %s is the source value */
					__( 'Referral: %s', 'woocommerce' )
					: 'Referral: %s';
				break;
			case 'typein':
				$label  = '';
				$source = $translated ?
					__( 'Direct', 'woocommerce' )
					: 'Direct';
				break;
			case 'admin':
				$label  = '';
				$source = $translated ?
					__( 'Web admin', 'woocommerce' )
					: 'Web admin';
				break;

			default:
				$label  = '';
				$source = __( 'Unknown', 'woocommerce' );
				break;
		}

		/**
		 * Filter the formatted source for the order origin.
		 *
		 * @since 8.5.0
		 *
		 * @param string $formatted_source The formatted source.
		 * @param string $source           The source.
		 */
		$formatted_source = apply_filters(
			'wc_order_attribution_origin_formatted_source',
			ucfirst( trim( $source, '()' ) ),
			$source
		);

		/**
		 * Filter the label for the order origin.
		 *
		 * This label should have a %s placeholder for the formatted source to be inserted
		 * via sprintf().
		 *
		 * @since 8.5.0
		 *
		 * @param string $label            The label for the order origin.
		 * @param string $source_type      The source type.
		 * @param string $source           The source.
		 * @param string $formatted_source The formatted source.
		 */
		$label = (string) apply_filters(
			'wc_order_attribution_origin_label',
			$label,
			$source_type,
			$source,
			$formatted_source
		);

		if ( false === strpos( $label, '%' ) ) {
			return $formatted_source;
		}

		return sprintf( $label, $formatted_source );
	}

	/**
	 * Get the description for the order attribution field.
	 *
	 * @param string $field The field name.
	 *
	 * @return string
	 */
	private function get_field_description( string $field ): string {
		/* translators: %s is the field name */
		$description = sprintf( __( 'Order attribution field: %s', 'woocommerce' ), $field );

		/**
		 * Filter the description for the order attribution field.
		 *
		 * @since 8.5.0
		 *
		 * @param string $description The description for the order attribution field.
		 * @param string $field       The field name.
		 */
		return (string) apply_filters( 'wc_order_attribution_field_description', $description, $field );
	}

	/**
	 * Get the order history for the customer (data matches Customers report).
	 *
	 * @param mixed $customer_identifier The customer ID or billing email.
	 *
	 * @return array Order count, total spend, and average spend per order.
	 */
	private function get_customer_history( $customer_identifier ): array {
		/*
		 * Exclude the statuses that aren't valid for the Customers report.
		 * 'checkout-draft' is the checkout block's draft order status. `any` is added by V2 Orders REST.
		 * @see /Automattic/WooCommerce/Admin/API/Report/DataStore::get_excluded_report_order_statuses()
		 */
		$all_order_statuses = ReportsController::get_order_statuses();
		$excluded_statuses  = array( 'pending', 'failed', 'cancelled', 'auto-draft', 'trash', 'checkout-draft', 'any' );

		// Get the valid customer orders.
		$args = array(
			'limit'  => - 1,
			'return' => 'objects',
			'status' => array_diff( $all_order_statuses, $excluded_statuses ),
			'type'   => 'shop_order',
		);

		// If the customer_identifier is a valid ID, use it. Otherwise, use the billing email.
		if ( is_numeric( $customer_identifier ) && $customer_identifier > 0 ) {
			$args['customer_id'] = $customer_identifier;
		} else {
			$args['billing_email'] = $customer_identifier;
			$args['customer_id']   = 0;
		}

		$orders = wc_get_orders( $args );

		// Populate the order_count and total_spent variables with the valid orders.
		$order_count = count( $orders );
		$total_spent = 0;
		foreach ( $orders as $order ) {
			$total_spent += $order->get_total() - $order->get_total_refunded();
		}

		return array(
			'order_count'   => $order_count,
			'total_spent'   => $total_spent,
			'average_spent' => $order_count ? $total_spent / $order_count : 0,
		);
	}
}
