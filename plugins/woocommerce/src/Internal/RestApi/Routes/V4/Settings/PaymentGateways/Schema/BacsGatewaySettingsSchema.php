<?php
/**
 * BacsGatewaySettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

defined( 'ABSPATH' ) || exit;

use WC_Payment_Gateway;
use WP_Error;

/**
 * BacsGatewaySettingsSchema class.
 *
 * Extends AbstractPaymentGatewaySettingsSchema to handle BACS-specific settings
 * with design-aligned field labels and descriptions.
 */
class BacsGatewaySettingsSchema extends AbstractPaymentGatewaySettingsSchema {

	/**
	 * Get custom groups for the BACS gateway.
	 *
	 * Provides design-aligned labels and descriptions, and separates
	 * account details into its own group. Derives fields from the gateway's
	 * form_fields to preserve any extension-injected settings.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array Custom group structure.
	 */
	protected function get_custom_groups_for_gateway( WC_Payment_Gateway $gateway ): array {
		// Design-aligned overrides for core fields.
		$core_field_overrides = array(
			'enabled'      => array(
				'label' => __( 'Enable/Disable', 'woocommerce' ),
				'type'  => 'checkbox',
				'desc'  => __( 'Enable Direct bank transfer at checkout', 'woocommerce' ),
			),
			'title'        => array(
				'label' => __( 'Checkout label', 'woocommerce' ),
				'type'  => 'text',
				'desc'  => __( 'Shown to customers on the payment methods list at checkout.', 'woocommerce' ),
			),
			'description'  => array(
				'label' => __( 'Checkout instructions', 'woocommerce' ),
				'type'  => 'text',
				'desc'  => __( 'Shown below the checkout label.', 'woocommerce' ),
			),
			'order'        => array(
				'label' => __( 'Order', 'woocommerce' ),
				'type'  => 'number',
				'desc'  => __( 'Determines the display order of payment gateways during checkout.', 'woocommerce' ),
			),
			'instructions' => array(
				'label' => __( 'Order confirmation instructions', 'woocommerce' ),
				'type'  => 'text',
				'desc'  => __( 'Shown on the order confirmation page and in order emails.', 'woocommerce' ),
			),
		);

		// account_details is handled in a separate group, skip it in the main fields.
		$fields = $this->build_fields_from_form_fields( $gateway, $core_field_overrides, array( 'account_details' ) );

		$settings_group = array(
			'title'       => __( 'Direct bank transfer settings', 'woocommerce' ),
			'description' => __( 'Manage how Direct bank transfer appears at checkout and in order emails.', 'woocommerce' ),
			'order'       => 1,
			'fields'      => $fields,
		);

		$field = $gateway->form_fields['account_details'] ?? array();

		$account_details_group = array(
			'title'       => __( 'Bank account details', 'woocommerce' ),
			'description' => __( 'Manage the bank accounts customers can use to pay by bank transfer.', 'woocommerce' ),
			'order'       => 2,
			'fields'      => array(
				array(
					'id'    => 'account_details',
					'label' => $field['title'] ?? __( 'Account details', 'woocommerce' ),
					'type'  => 'array',
					'desc'  => $field['description'] ?? __( 'Bank account details for direct bank transfer.', 'woocommerce' ),
				),
			),
		);

		return array(
			'settings'        => $settings_group,
			'account_details' => $account_details_group,
		);
	}

	/**
	 * Get values for BACS-specific special fields.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array
	 */
	protected function get_special_field_values( WC_Payment_Gateway $gateway ): array {
		return array(
			'account_details' => get_option( 'woocommerce_bacs_accounts', array() ),
		);
	}

	/**
	 * Check if a field is a special field for BACS.
	 *
	 * @param string $field_id Field ID.
	 * @return bool
	 */
	public function is_special_field( string $field_id ): bool {
		return 'account_details' === $field_id;
	}

	/**
	 * Validate and sanitize BACS special fields.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @param array              $values  Special field values.
	 * @return array|WP_Error Validated values or error.
	 */
	public function validate_and_sanitize_special_fields( WC_Payment_Gateway $gateway, array $values ) {
		$validated = array();

		foreach ( $values as $field_id => $value ) {
			if ( 'account_details' === $field_id ) {
				$validated[ $field_id ] = $this->validate_bacs_accounts( $value );
				if ( is_wp_error( $validated[ $field_id ] ) ) {
					return $validated[ $field_id ];
				}
			}
		}

		return $validated;
	}

	/**
	 * Update BACS special fields in database.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @param array              $values  Validated special field values.
	 * @return void
	 */
	public function update_special_fields( WC_Payment_Gateway $gateway, array $values ): void {
		foreach ( $values as $field_id => $value ) {
			if ( 'account_details' === $field_id ) {
				update_option( 'woocommerce_bacs_accounts', $value );
			}
		}
	}

	/**
	 * Validate BACS account details array.
	 *
	 * @param mixed $value Account details value.
	 * @return array|WP_Error Validated accounts or error.
	 */
	private function validate_bacs_accounts( $value ) {
		if ( ! is_array( $value ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Account details must be an array.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$validated_accounts = array();
		$valid_fields       = array( 'account_name', 'account_number', 'sort_code', 'bank_name', 'iban', 'bic' );

		foreach ( $value as $index => $account ) {
			if ( ! is_array( $account ) ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf(
						/* translators: %d: account index */
						__( 'Account at index %d must be an object.', 'woocommerce' ),
						$index
					),
					array( 'status' => 400 )
				);
			}

			$validated_account = array();

			// Sanitize each field.
			foreach ( $valid_fields as $field ) {
				$validated_account[ $field ] = isset( $account[ $field ] )
					? sanitize_text_field( $account[ $field ] )
					: '';
			}

			// Only add if at least one field is filled.
			if ( array_filter( $validated_account ) ) {
				$validated_accounts[] = $validated_account;
			}
		}

		return $validated_accounts;
	}
}
