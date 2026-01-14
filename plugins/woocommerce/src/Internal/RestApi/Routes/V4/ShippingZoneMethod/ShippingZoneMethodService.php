<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod;

use WP_Error;
use WC_Cache_Helper;
use WC_Shipping_Method;

/**
 * A service class to manage shipping zones methods.
 */
class ShippingZoneMethodService {

	/**
	 * Update settings of a shipping method.
	 *
	 * Validates and saves shipping method settings. Settings vary by method type
	 * (e.g., flat_rate has 'cost', free_shipping has 'requires' and 'min_amount').
	 *
	 * @param WC_Shipping_Method $method   Shipping method instance to update.
	 * @param array              $settings Settings to update as key-value pairs (e.g., ['title' => 'Express', 'cost' => '10']).
	 *                                     Available settings depend on the specific shipping method type.
	 * @return WC_Shipping_Method|\WP_Error Updated method object on success, WP_Error on validation failure.
	 */
	public function update_shipping_method_settings( $method, $settings ) {
		if ( ! is_array( $settings ) ) {
			return new \WP_Error(
				'woocommerce_rest_shipping_method_invalid_settings',
				__( 'Settings must be an array.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$method->init_instance_settings();
		$instance_settings = $method->instance_settings;

		/**
		 * Transform setting keys to WooCommerce's expected format.
		 *
		 * WC_Settings_API::get_field_value() expects prefixed keys (e.g., 'woocommerce_flat_rate_1_title').
		 * Transform clean keys ('title') to prefixed keys before validation.
		 */
		$post_data = array();
		foreach ( $settings as $key => $value ) {
			$field_key               = $method->get_field_key( $key );
			$post_data[ $field_key ] = $value;
		}

		$form_fields = $method->get_instance_form_fields();
		foreach ( $settings as $key => $value ) {
			if ( isset( $form_fields[ $key ] ) ) {
				try {
					$instance_settings[ $key ] = $method->get_field_value( $key, $form_fields[ $key ], $post_data );
				} catch ( \Exception $e ) {
					return new \WP_Error(
						'woocommerce_rest_shipping_method_invalid_setting',
						$e->getMessage(),
						array( 'status' => 400 )
					);
				}
			}
		}

		/**
		 * Filter the instance settings values before saving.
		 *
		 * @since 9.4.0
		 * @param array              $instance_settings Instance settings.
		 * @param WC_Shipping_Method $method            Shipping method instance.
		 */
		$filtered_settings = apply_filters( 'woocommerce_shipping_' . $method->id . '_instance_settings_values', $instance_settings, $method );
		$result            = update_option( $method->get_instance_option_key(), $filtered_settings );

		if ( $result ) {
			$method->instance_settings = $instance_settings;
		}

		return $method;
	}

	/**
	 * Update a shipping method's properties.
	 *
	 * Updates settings, enabled status, and/or sort order for a shipping method instance.
	 *
	 * @since 9.4.0
	 *
	 * @param WC_Shipping_Method $method      Shipping method instance to update.
	 * @param int                $instance_id Method instance ID from the database.
	 * @param array              $data        {
	 *     Method properties to update. All parameters are optional.
	 *
	 *     @type array $settings Settings to update (key-value pairs). See update_shipping_method_settings().
	 *     @type bool  $enabled  Whether the shipping method is enabled.
	 *     @type int   $order    Sort order for displaying methods.
	 * }
	 * @param int|null           $zone_id    Zone ID. Optional, but required for firing the status toggle hook.
	 * @return WC_Shipping_Method|\WP_Error Updated method object on success, WP_Error on failure.
	 */
	public function update_shipping_zone_method( $method, $instance_id, $data, $zone_id = null ) {
		global $wpdb;

		$data = wp_parse_args(
			$data,
			array(
				'settings' => null,
				'enabled'  => null,
				'order'    => null,
			)
		);

		$updates         = array();
		$formats         = array();
		$enabled_changed = false;

		if ( ! is_null( $data['settings'] ) ) {
			$result = $this->update_shipping_method_settings( $method, $data['settings'] );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		if ( ! is_null( $data['enabled'] ) ) {
			$updates['is_enabled'] = wc_string_to_bool( $data['enabled'] ) ? 1 : 0;
			$formats[]             = '%d';
			$method->enabled       = wc_string_to_bool( $data['enabled'] ) ? 'yes' : 'no';
			$enabled_changed       = true;
		}

		if ( ! is_null( $data['order'] ) ) {
			$updates['method_order'] = absint( $data['order'] );
			$formats[]               = '%d';
			$method->method_order    = absint( $data['order'] );
		}

		if ( empty( $updates ) ) {
			return $method;
		}

		$result = $wpdb->update(
			"{$wpdb->prefix}woocommerce_shipping_zone_methods",
			$updates,
			array( 'instance_id' => $instance_id ),
			$formats,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Could not update shipping method.', 'woocommerce' )
			);
		}

		if ( $enabled_changed && null !== $zone_id ) {
			/**
			 * Fires when a shipping method's enabled status is toggled.
			 *
			 * @since 3.0.0
			 * @param int    $instance_id Instance ID of the shipping method.
			 * @param string $method_id   Shipping method ID (e.g., 'flat_rate').
			 * @param int    $zone_id     Zone ID.
			 * @param bool   $is_enabled  Whether the method is enabled.
			 */
			do_action(
				'woocommerce_shipping_zone_method_status_toggled',
				$instance_id,
				$method->id,
				$zone_id,
				(bool) $updates['is_enabled']
			);
		}

		WC_Cache_Helper::get_transient_version( 'shipping', true );
		return $method;
	}
}
