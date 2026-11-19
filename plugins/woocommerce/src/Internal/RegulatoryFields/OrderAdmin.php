<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Admin_Meta_Boxes;
use WC_Order;
use WP_Error;

/**
 * Shows regulatory fields on the admin order screen, below the billing details, and lets the merchant edit them.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class OrderAdmin implements RegisterHooksInterface {

	/**
	 * The regulatory fields service.
	 *
	 * @var RegulatoryFields
	 */
	private RegulatoryFields $regulatory_fields;

	/**
	 * Initialize the class with its dependencies.
	 *
	 * @internal
	 *
	 * @param RegulatoryFields $regulatory_fields The regulatory fields service.
	 */
	final public function init( RegulatoryFields $regulatory_fields ): void {
		$this->regulatory_fields = $regulatory_fields;
	}

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'handle_woocommerce_admin_billing_fields' ), 10, 2 );
	}

	/**
	 * Add the fields that apply to the order, and any field it already has a value for, to the billing details.
	 *
	 * @internal
	 *
	 * @param mixed $fields Billing fields of the order screen.
	 * @param mixed $order  Order being shown.
	 * @return mixed
	 */
	public function handle_woocommerce_admin_billing_fields( $fields, $order = null ) {
		if ( ! is_array( $fields ) || ! $order instanceof WC_Order ) {
			return $fields;
		}

		$display_values = $this->regulatory_fields->get_order_display_values( $order );
		$all_fields     = $this->regulatory_fields->get_all_fields();
		$field_ids      = array_unique( array_merge( array_keys( $this->regulatory_fields->get_fields_for_order( $order ) ), array_keys( $display_values ) ) );

		foreach ( $field_ids as $field_id ) {
			$fields[ RegulatoryFields::META_PREFIX . $field_id ] = array(
				'id'              => RegulatoryFields::META_PREFIX . $field_id,
				'label'           => $all_fields[ $field_id ]['label'],
				'value'           => $display_values[ $field_id ]['value'] ?? '',
				'type'            => 'text',
				'show'            => true,
				'wrapper_class'   => 'form-field-wide',
				'update_callback' => array( $this, 'update_order_value' ),
			);
		}

		return $fields;
	}

	/**
	 * Store a value the merchant entered on the order screen. An empty value clears the field.
	 *
	 * A value that is not valid is not stored, and the merchant sees an error.
	 *
	 * @internal
	 *
	 * @param mixed $input_id ID of the input, which is the field's meta key.
	 * @param mixed $value    Submitted value.
	 * @param mixed $order    Order being saved.
	 */
	public function update_order_value( $input_id, $value, $order ): void {
		if ( ! is_string( $input_id ) || ! $order instanceof WC_Order ) {
			return;
		}

		$field_id = substr( $input_id, strlen( RegulatoryFields::META_PREFIX ) );
		$field    = $this->regulatory_fields->get_all_fields()[ $field_id ] ?? null;

		if ( null === $field ) {
			return;
		}

		$errors = new WP_Error();
		$values = $this->regulatory_fields->get_valid_values( array( $field_id => array_merge( $field, array( 'required' => false ) ) ), array( $field_id => $value ), $errors );

		if ( $errors->has_errors() ) {
			WC_Admin_Meta_Boxes::add_error( $field['label'] . ': ' . $errors->get_error_message() );
			return;
		}

		if ( isset( $values[ $field_id ] ) ) {
			$order->update_meta_data( $input_id, $values[ $field_id ] );
		} else {
			$order->delete_meta_data( $input_id );
		}
	}
}
