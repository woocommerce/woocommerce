<?php

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Utilities\ArrayUtil;
use Automattic\WooCommerce\Utilities\StringUtil;
use Exception;

/**
 * Class with methods for handling order coupons.
 */
class CouponsController {

	/**
	 * WC_DateTime fields in order item `coupon_data`.
	 *
	 * @var array
	 */
	protected $datetime_fields = array( 'date_created', 'date_modified', 'date_expires' );

	/**
	 * Non-boolean fields in order item `coupon_data` that default to empty.
	 *
	 * @var array
	 */
	protected $default_empty_fields = array(
		'description'                 => '',
		'email_restrictions'          => array(),
		'excluded_product_ids'        => array(),
		'excluded_product_categories' => array(),
		'limit_usage_to_x_items'      => 0,
		'maximum_amount'              => '',
		'meta_data'                   => array(),
		'minimum_amount'              => '',
		'product_categories'          => array(),
		'product_ids'                 => array(),
		'usage_count'                 => 0,
		'usage_limit'                 => 0,
		'usage_limit_per_user'        => 0,
	);

	/**
	 * Add order discount via Ajax.
	 *
	 * @throws Exception If order or coupon is invalid.
	 */
	public function add_coupon_discount_via_ajax(): void {
		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$response = array();

		try {
			$order = $this->add_coupon_discount( $_POST );

			ob_start();
			include __DIR__ . '/../../../includes/admin/meta-boxes/views/html-order-items.php';
			$response['html'] = ob_get_clean();
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

		// wp_send_json_success must be outside the try block not to break phpunit tests.
		wp_send_json_success( $response );
	}

	/**
	 * Add order discount programmatically.
	 *
	 * @param array $post_variables Contents of the $_POST array that would be passed in an Ajax call.
	 * @return object The retrieved order object.
	 * @throws Exception Invalid order or coupon.
	 */
	public function add_coupon_discount( array $post_variables ): object {
		$order_id           = isset( $post_variables['order_id'] ) ? absint( $post_variables['order_id'] ) : 0;
		$order              = wc_get_order( $order_id );
		$calculate_tax_args = array(
			'country'  => isset( $post_variables['country'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['country'] ) ) ) : '',
			'state'    => isset( $post_variables['state'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['state'] ) ) ) : '',
			'postcode' => isset( $post_variables['postcode'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['postcode'] ) ) ) : '',
			'city'     => isset( $post_variables['city'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['city'] ) ) ) : '',
		);

		if ( ! $order ) {
			throw new Exception( __( 'Invalid order', 'woocommerce' ) );
		}

		$coupon = ArrayUtil::get_value_or_default( $post_variables, 'coupon' );
		if ( StringUtil::is_null_or_whitespace( $coupon ) ) {
			throw new Exception( __( 'Invalid coupon', 'woocommerce' ) );
		}

		// Add user ID and/or email so validation for coupon limits works.
		$user_id_arg    = isset( $post_variables['user_id'] ) ? absint( $post_variables['user_id'] ) : 0;
		$user_email_arg = isset( $post_variables['user_email'] ) ? sanitize_email( wp_unslash( $post_variables['user_email'] ) ) : '';

		if ( $user_id_arg ) {
			$order->set_customer_id( $user_id_arg );
		}
		if ( $user_email_arg ) {
			$order->set_billing_email( $user_email_arg );
		}

		$order->calculate_taxes( $calculate_tax_args );
		$order->calculate_totals( false );

		$result = $order->apply_coupon( wc_format_coupon_code( wp_unslash( $coupon ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( is_wp_error( $result ) ) {
			throw new Exception( html_entity_decode( wp_strip_all_tags( $result->get_error_message() ) ) );
		}

		return $order;
	}

	/**
	 * Dehydrate order coupon_data meta.
	 *
	 * @since 7.6.0
	 * @param \WC_Meta_Data $meta Coupon data meta object.
	 * @return \WC_Meta_Data
	 */
	public function dehydrate_coupon_data( \WC_Meta_Data $meta ): \WC_Meta_Data {
		$coupon_data = $meta->value;
		// Convert WC_DateTime fields to strings.
		foreach ( $this->datetime_fields as $key ) {
			if ( empty( $coupon_data[ $key ] ) ) {
				unset( $coupon_data[ $key ] );
			} elseif ( is_a( $coupon_data[ $key ], \WC_DateTime::class ) ) {
				$coupon_data[ $key ] = $coupon_data[ $key ]->date( 'Y-m-dTH:i:sO' );
			}
		}

		// Reduce meta data.
		if ( ! empty( $coupon_data['meta_data'] ) ) {
			foreach ( $coupon_data['meta_data'] as $key => $meta_object ) {
				if ( is_a( $meta_object, \WC_Meta_Data::class ) ) {
					$coupon_data['meta_data'][ $key ] = $meta_object->get_data();
				}
			}
		}

		// Remove non-boolean empty fields that default to falsey.
		foreach ( array_keys( $this->default_empty_fields ) as $key ) {
			if ( empty( $coupon_data[ $key ] ) ) {
				unset( $coupon_data[ $key ] );
			}
		}

		$meta->value = $coupon_data;
		return $meta;
	}

	/**
	 * Hydrate order coupon_data meta array.
	 *
	 * @since 7.6.0
	 * @param array $meta Coupon data meta.
	 * @return array
	 */
	public function hydrate_coupon_data( array $meta ): array {
		// Convert WC_DateTime fields.
		foreach ( $this->datetime_fields as $key ) {
			if ( ! isset( $meta[ $key ] ) ) {
				$meta[ $key ] = null;
			} elseif ( is_string( $meta[ $key ] ) ) {
				try {
					$meta[ $key ] = new \WC_DateTime( $meta[ $key ] );
				} catch ( Exception $e ) {
					$meta[ $key ] = null;
				}
			}
		}

		// Convert WC_Meta_Data array.
		if ( ! empty( $meta['meta_data'] ) ) {
			foreach ( $meta['meta_data'] as $key => $meta_array ) {
				if ( is_array( $meta_array ) ) {
					$coupon_data['meta_data'][ $key ] = new \WC_Meta_Data( $meta_array );
				}
			}
		}

		// Add non-boolean empty fields that default to falsey.
		$meta = array_merge( $this->default_empty_fields, $meta );

		return $meta;
	}

	/**
	 * Get the order coupon default non-boolean falsey fields.
	 *
	 * @since 7.6.0
	 * @return array|string[]
	 */
	public function get_default_empty_fields(): array {
		return $this->default_empty_fields;
	}
}
