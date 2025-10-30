<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Shipping;

use WC_Shipping_Zones;
use WC_Shipping_Zone;
use WP_Error;
use WP_Http;
use WC_Cache_Helper;

/**
 * A service class to manage shipping zones and their methods & locations.
 *
 * @internal
 */
class ShippingService {

	/**
	 * Get all shipping zones sorted by order.
	 *
	 * @return array Array of shipping zones sorted by zone_order.
	 */
	public function get_sorted_shipping_zones() {
		// Get all zones including "Rest of the World".
		$zones             = WC_Shipping_Zones::get_zones();
		$rest_of_the_world = WC_Shipping_Zones::get_zone_by( 'zone_id', 0 );

		// Add "Rest of the World" zone at the end with same structure as get_zones().
		$rest_data                            = $rest_of_the_world->get_data();
		$rest_data['zone_id']                 = $rest_of_the_world->get_id();
		$rest_data['formatted_zone_location'] = array();
		$rest_data['shipping_methods']        = $rest_of_the_world->get_shipping_methods( false, 'admin' );
		$zones[0]                             = $rest_data;

		// Sort zones by order.
		uasort(
			$zones,
			function ( $a, $b ) {
				return $a['zone_order'] <=> $b['zone_order'];
			}
		);

		return $zones;
	}

	/**
	 * Create a shipping zone from REST API request.
	 *
	 * @param array $params Request parameters.
	 * @return WC_Shipping_Zone|WP_Error True on success, WP_Error on failure.
	 */
	public function create_shipping_zone( $params ) {
		$zone   = new WC_Shipping_Zone( null );
		$result = $this->update_shipping_zone( $zone, $params );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return $zone;
	}

	/**
	 * Update zone details from REST API request.
	 *
	 * @param WC_Shipping_Zone $zone zone to be updated.
	 * @param array            $params Request parameters.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function update_shipping_zone( $zone, $params ) {

		// Prevent updating "Rest of the World" zone name, order, or locations.
		if ( 0 === $zone->get_id() ) {
			if ( isset( $params['name'] ) && ! is_null( $params['name'] ) ) {
				return new WP_Error(
					'woocommerce_rest_cannot_edit_zone',
					__( 'Cannot change name of "Rest of the World" zone.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			if ( isset( $params['order'] ) && ! is_null( $params['order'] ) ) {
				return new WP_Error(
					'woocommerce_rest_cannot_edit_zone',
					__( 'Cannot change order of "Rest of the World" zone.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			if ( isset( $params['locations'] ) && ! is_null( $params['locations'] ) ) {
				return new WP_Error(
					'woocommerce_rest_cannot_edit_zone',
					__( 'Cannot change locations of "Rest of the World" zone.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
		}

		// Set zone name if provided.
		if ( isset( $params['name'] ) && ! is_null( $params['name'] ) ) {
			$name = trim( $params['name'] );
			if ( '' === $name ) {
				return new WP_Error(
					'woocommerce_rest_invalid_zone_name',
					__( 'Zone name cannot be empty.', 'woocommerce' ),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
			$zone->set_zone_name( $name );
		}

		// Set zone order if provided.
		if ( isset( $params['order'] ) && ! is_null( $params['order'] ) ) {
			$zone->set_zone_order( $params['order'] );
		}

		// Set locations if provided.
		if ( isset( $params['locations'] ) && ! is_null( $params['locations'] ) ) {
			$raw_locations = $params['locations'];
			$locations     = array();

			foreach ( (array) $raw_locations as $raw_location ) {
				if ( empty( $raw_location['code'] ) ) {
					continue;
				}

				$type = ! empty( $raw_location['type'] ) ? $raw_location['type'] : 'country';

				// Normalize 'country:state' to 'state' for v4 API backward compatibility.
				if ( 'country:state' === $type ) {
					$type = 'state';
				}

				if ( ! $zone->is_valid_location_type( $type ) ) {
					continue;
				}

				$locations[] = array(
					'code' => $raw_location['code'],
					'type' => $type,
				);
			}

			$zone->set_locations( $locations );
		}

		// Save the zone.
		$zone->save();

		return true;
	}

	/**
	 * Update settings of a shipping method from REST API request.
	 *
	 * This function handles validation and saving of shipping method settings from REST API requests.
	 *
	 * @param \WC_Shipping_Method $method Zone object that contains this method.
	 * @param array               $settings Settings to update (key-value pairs with clean field names, e.g., ['title' => 'Express', 'cost' => '10']).
	 * @return true|\WP_Error True on success, WP_Error on validation failure.
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
		 * Key Transformation Explanation:
		 *
		 * The get_field_value() method (from WC_Settings_API) was designed for admin forms
		 * where POST data has prefixed keys like 'woocommerce_flat_rate_1_title'.
		 *
		 * Internally, get_field_value() does this:
		 *   $field_key = $this->get_field_key($key);  // e.g., 'woocommerce_flat_rate_1_title'
		 *   $value = $post_data[$field_key];          // Looks for the PREFIXED key
		 *
		 * Since REST API sends clean JSON keys (e.g., 'title', 'cost'), we must transform
		 * them to prefixed keys before passing to get_field_value(), or it will return null.
		 *
		 * Example:
		 *   REST API sends: ['title' => 'Express']
		 *   We transform to: ['woocommerce_flat_rate_1_title' => 'Express']
		 *   Then get_field_value('title', ...) finds the value at 'woocommerce_flat_rate_1_title'
		 */
		$post_data = array();
		foreach ( $settings as $key => $value ) {
			$field_key               = $method->get_field_key( $key );
			$post_data[ $field_key ] = $value;
		}

		// Validate and sanitize each field using get_field_value().
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

		// Save to database.
		/**
		 * Filter the instance settings values before saving.
		 *
		 * @since 9.4.0
		 * @param array                $instance_settings Instance settings.
		 * @param WC_Shipping_Method   $method            Shipping method instance.
		 */
		$filtered_settings = apply_filters( 'woocommerce_shipping_' . $method->id . '_instance_settings_values', $instance_settings, $method );
		$result            = update_option( $method->get_instance_option_key(), $filtered_settings );

		if ( $result ) {
			$method->instance_settings = $instance_settings;
		}

		return $result;
	}

	/**
	 * Update shipping method from REST API request.
	 *
	 * Handles updating settings, enabled status, and order from REST API requests.
	 * This method can be used by any API version (v2, v3, v4) for consistent behavior.
	 *
	 * @since 9.4.0
	 * @param \WC_Shipping_Method $method Shipping method instance.
	 * @param int                 $instance_id Method instance ID.
	 * @param array               $data Request data containing 'settings', 'enabled', and/or 'order'.
	 * @return true|\WP_Error True on success, WP_Error on validation failure.
	 */
	public function update_shipping_zone_method( $method, $instance_id, $data ) {
		global $wpdb;

		$updates = array();
		$formats = array();

		// Update settings if present.
		if ( isset( $data['settings'] ) ) {
			$result = $this->update_shipping_method_settings( $method, $data['settings'] );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		if ( isset( $data['enabled'] ) ) {
				$updates['is_enabled'] = wc_string_to_bool( $data['enabled'] ) ? 1 : 0;
				$formats[]             = '%d';
		}

		if ( isset( $data['order'] ) ) {
			$updates['method_order'] = absint( $data['order'] );
			$formats[]               = '%d';
		}

		if ( empty( $updates ) ) {
			return true;
		}

		// Single UPDATE query for both fields.
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

		WC_Cache_Helper::get_transient_version( 'shipping', true );
		return true;
	}
}
