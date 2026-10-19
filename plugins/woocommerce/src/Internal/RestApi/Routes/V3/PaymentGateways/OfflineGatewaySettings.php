<?php
/**
 * Editable v3 fields for the Core offline gateways.
 *
 * @package WooCommerce\Internal\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V3\PaymentGateways;

use WC_Gateway_BACS;
use WC_Payment_Gateway;
use WC_REST_Controller;
use WP_Error;
use WP_REST_Request;

/**
 * Keeps the editable values compatible with the existing gateway settings store.
 *
 * @internal
 */
final class OfflineGatewaySettings {
	/** Core gateway IDs. */
	private const GATEWAYS = array( 'bacs', 'cheque', 'cod' );
	/** Settings also exposed as editable top-level fields. */
	private const FIELDS = array( 'instructions', 'enable_for_methods', 'enable_for_virtual' );
	/** Stored bank account fields. */
	private const ACCOUNT_FIELDS = array( 'account_name', 'account_number', 'bank_name', 'sort_code', 'iban', 'bic' );

	/**
	 * Describe the optional fields without changing the legacy settings schema.
	 *
	 * @since 11.3.0
	 * @return array
	 */
	public function get_schema(): array {
		$account                 = array_fill_keys( self::ACCOUNT_FIELDS, array( 'type' => 'string' ) );
		$account['country_code'] = array(
			'type'    => 'string',
			'pattern' => '^([A-Z]{2})?$',
		);
		$fields                  = array(
			'instructions'       => array(
				'description' => __( 'Customer instructions for a Core offline gateway.', 'woocommerce' ),
				'type'        => 'string',
			),
			'enable_for_methods' => array(
				'description' => __( 'Shipping methods allowed for a Core offline gateway.', 'woocommerce' ),
				'type'        => 'array',
				'items'       => array( 'type' => 'string' ),
			),
			'enable_for_virtual' => array(
				'description' => __( 'Whether a Core offline gateway accepts virtual orders.', 'woocommerce' ),
				'type'        => 'boolean',
			),
			'account_details'    => array(
				'description' => __( 'Bank accounts for direct bank transfer. Non-empty accounts require a name and any country-specific routing code.', 'woocommerce' ),
				'type'        => 'array',
				'items'       => array(
					'type'                 => 'object',
					'properties'           => $account,
					'additionalProperties' => false,
				),
			),
		);
		foreach ( $fields as &$field ) {
			$field['context'] = array( 'view', 'edit' );
		}
		return $fields;
	}

	/**
	 * Identify requests using the new contract.
	 *
	 * @since 11.3.0
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool
	 */
	public function has_fields( WP_REST_Request $request ): bool {
		foreach ( array_merge( self::FIELDS, array( 'account_details' ) ) as $field ) {
			if ( $request->has_param( $field ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Read values from the existing storage without migrating it.
	 *
	 * @since 11.3.0
	 * @param WC_Payment_Gateway $gateway Gateway.
	 * @return array
	 */
	public function get_values( WC_Payment_Gateway $gateway ): array {
		if ( ! in_array( $gateway->id, self::GATEWAYS, true ) ) {
			return array();
		}
		$instructions = $gateway->get_option( 'instructions', '' );
		$methods      = $gateway->get_option( 'enable_for_methods', array() );
		$values       = array(
			'instructions'       => is_string( $instructions ) ? $instructions : '',
			'enable_for_methods' => is_array( $methods ) ? array_values( $methods ) : array(),
			'enable_for_virtual' => 'yes' === $gateway->get_option( 'enable_for_virtual', 'yes' ),
		);
		if ( 'bacs' === $gateway->id ) {
			$accounts                  = get_option( 'woocommerce_bacs_accounts', array() );
			$values['account_details'] = is_array( $accounts ) ? $accounts : array();
		}
		return $values;
	}

	/**
	 * Validate the whole request before writing either option.
	 *
	 * Legacy-only requests continue through the existing controller unchanged.
	 *
	 * @since 11.3.0
	 * @param WC_Payment_Gateway $gateway Gateway.
	 * @param WP_REST_Request    $request Request.
	 * @param WC_REST_Controller $controller Existing setting validators.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return true|WP_Error
	 */
	public function update( WC_Payment_Gateway $gateway, WP_REST_Request $request, WC_REST_Controller $controller ) {
		if ( ! in_array( $gateway->id, self::GATEWAYS, true ) || ( $request->has_param( 'account_details' ) && 'bacs' !== $gateway->id ) ) {
			return $this->invalid( __( 'These fields are not available for this gateway.', 'woocommerce' ) );
		}
		$gateway->init_form_fields();
		$fields   = $gateway->get_form_fields();
		$legacy   = $request->get_param( 'settings' ) ?? array();
		$settings = $gateway->settings;
		if ( ! is_array( $legacy ) ) {
			return $this->invalid( __( 'Invalid gateway settings.', 'woocommerce' ) );
		}
		foreach ( $fields as $key => $field ) {
			if ( isset( $legacy[ $key ] ) ) {
				$value = $this->validate( $controller, $legacy[ $key ], $field );
				if ( is_wp_error( $value ) ) {
					return $value;
				}
				$settings[ $key ] = $value;
			}
		}
		foreach ( self::FIELDS as $key ) {
			if ( ! $request->has_param( $key ) ) {
				continue;
			}
			if ( ! isset( $fields[ $key ] ) ) {
				return $this->invalid( __( 'The gateway does not support this setting.', 'woocommerce' ) );
			}
			$value = $request->get_param( $key );
			if ( 'enable_for_virtual' === $key ) {
				$value = wc_bool_to_string( $value );
			}
			$value = $this->validate( $controller, $value, $fields[ $key ] );
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			if ( isset( $legacy[ $key ] ) && $settings[ $key ] !== $value ) {
				return $this->invalid( sprintf( /* translators: %s: setting name. */ __( 'Conflicting values for %s.', 'woocommerce' ), $key ) );
			}
			$settings[ $key ] = $value;
		}
		foreach ( array( 'title', 'description' ) as $key ) {
			if ( isset( $request[ $key ] ) ) {
				$settings[ $key ] = $controller->validate_setting_text_field( $request[ $key ], $fields[ $key ] ?? array() );
			}
		}
		if ( isset( $request['enabled'] ) ) {
			$settings['enabled'] = wc_bool_to_string( $request['enabled'] );
		}
		$accounts = null;
		if ( $request->has_param( 'account_details' ) ) {
			$accounts = $this->clean_accounts( $request['account_details'] );
			if ( is_wp_error( $accounts ) ) {
				return $accounts;
			}
		}
		$original          = $gateway->settings;
		$gateway->settings = $settings;
		$this->refresh_gateway( $gateway );
		/**
		 * Filter settings saved through the editable Core offline fields.
		 *
		 * @since 11.3.0
		 * @param array $settings Gateway settings.
		 * @param WC_Payment_Gateway $gateway Gateway being saved.
		 */
		$filtered = apply_filters( 'woocommerce_gateway_' . $gateway->id . '_settings_values', $settings, $gateway );
		if ( ! is_array( $filtered ) ) {
			$gateway->settings = $original;
			$this->refresh_gateway( $gateway );
			return $this->invalid( __( 'Invalid filtered gateway settings.', 'woocommerce' ) );
		}
		$gateway->settings = $filtered;
		if ( ! update_option( $gateway->get_option_key(), $filtered ) && get_option( $gateway->get_option_key() ) !== $filtered ) {
			$gateway->settings = $original;
			$this->refresh_gateway( $gateway );
			return new WP_Error( 'woocommerce_rest_cannot_update', __( 'Could not save gateway settings.', 'woocommerce' ), array( 'status' => 500 ) );
		}
		if ( null !== $accounts && ! update_option( 'woocommerce_bacs_accounts', $accounts ) && get_option( 'woocommerce_bacs_accounts' ) !== $accounts ) {
			return new WP_Error( 'woocommerce_rest_cannot_update', __( 'Could not save bank accounts.', 'woocommerce' ), array( 'status' => 500 ) );
		}
		if ( null !== $accounts && $gateway instanceof WC_Gateway_BACS ) {
			$gateway->account_details = $accounts;
		}
		if ( isset( $request['order'] ) ) {
			$order                 = (array) get_option( 'woocommerce_gateway_order' );
			$order[ $gateway->id ] = absint( $request['order'] );
			update_option( 'woocommerce_gateway_order', $order );
			$gateway->order = absint( $request['order'] );
		}
		$this->refresh_gateway( $gateway );
		return true;
	}

	/**
	 * Keep the gateway object consistent with the settings passed to existing hooks.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway.
	 */
	private function refresh_gateway( WC_Payment_Gateway $gateway ): void {
		$gateway->enabled     = $gateway->get_option( 'enabled', 'no' );
		$gateway->title       = $gateway->get_option( 'title', '' );
		$gateway->description = $gateway->get_option( 'description', '' );
	}

	/**
	 * Use the existing controller's validators, including extension overrides.
	 *
	 * @param WC_REST_Controller $controller Controller.
	 * @param mixed              $value Input.
	 * @param array              $field Field definition.
	 * @return mixed|WP_Error
	 */
	private function validate( WC_REST_Controller $controller, $value, array $field ) {
		$type   = isset( $field['type'] ) && is_string( $field['type'] ) ? $field['type'] : 'text';
		$method = 'validate_setting_' . $type . '_field';
		if ( ! is_callable( array( $controller, $method ) ) ) {
			$method = 'validate_setting_text_field';
		}
		if ( in_array( $method, array( 'validate_setting_text_field', 'validate_setting_textarea_field', 'validate_setting_checkbox_field' ), true ) && ! is_scalar( $value ) && null !== $value ) {
			return $this->invalid( __( 'Invalid setting value.', 'woocommerce' ) );
		}
		return $controller->$method( $value, $field );
	}

	/**
	 * Sanitize accounts while preserving order and optional country metadata.
	 *
	 * @param mixed $accounts Submitted accounts.
	 * @return array|WP_Error
	 */
	private function clean_accounts( $accounts ) {
		if ( ! is_array( $accounts ) ) {
			return $this->invalid( __( 'Bank accounts must be an array.', 'woocommerce' ) );
		}
		$clean = array();
		foreach ( $accounts as $account ) {
			if ( ! is_array( $account ) ) {
				return $this->invalid( __( 'Invalid bank account.', 'woocommerce' ) );
			}
			$row = array();
			foreach ( self::ACCOUNT_FIELDS as $key ) {
				if ( isset( $account[ $key ] ) && ! is_scalar( $account[ $key ] ) ) {
					return $this->invalid( __( 'Invalid bank account field.', 'woocommerce' ) );
				}
				$row[ $key ] = sanitize_text_field( $account[ $key ] ?? '' );
			}
			if ( ! array_filter(
				$row,
				static function ( $value ) {
					return '' !== $value; }
			) ) {
				continue;
			}
			if ( isset( $account['country_code'] ) && '' !== $account['country_code'] ) {
				if ( ! is_string( $account['country_code'] ) || ! preg_match( '/^[A-Z]{2}$/', $account['country_code'] ) ) {
					return $this->invalid( __( 'Invalid bank account country.', 'woocommerce' ) );
				}
				$row['country_code'] = sanitize_text_field( $account['country_code'] );
			}
			if ( '' === $row['account_name'] ) {
				return $this->invalid( __( 'Each bank account requires an account name.', 'woocommerce' ) );
			}
			$country = $row['country_code'] ?? wc_get_base_location()['country'];
			// Keep this list aligned with shouldDisplaySortCode() in the Core bank account editor.
			if ( in_array( $country, array( 'AU', 'CA', 'IN', 'IT', 'NZ', 'SE', 'US', 'ZA', 'GB', 'IE' ), true ) && '' === $row['sort_code'] ) {
				return $this->invalid( __( 'A sort code or routing number is required for this bank account country.', 'woocommerce' ) );
			}
			$clean[] = $row;
		}
		return $clean;
	}

	/**
	 * Return a validation error before persistence.
	 *
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	private function invalid( string $message ): WP_Error {
		return new WP_Error( 'rest_invalid_param', $message, array( 'status' => 400 ) );
	}
}
