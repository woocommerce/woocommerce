<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Admin_Meta_Boxes;
use WC_Order;
use WP_Error;

/**
 * Shows regulatory fields on the admin order screen, in their own column next to Billing and Shipping, and lets the merchant edit them.
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
	 * Output a column next to Billing and Shipping, built the same way: the values, and inputs behind the Edit link.
	 *
	 * Called by the order data meta box. Outputs nothing when the order has no regulatory fields.
	 *
	 * @param WC_Order $order Order being shown.
	 */
	public function render_column( WC_Order $order ): void {
		$fields = $this->get_order_fields( $order );

		if ( empty( $fields ) ) {
			return;
		}

		echo '<div class="order_data_column order_data_column_regulatory">';
		echo '<h3>' . esc_html__( 'Additional required information', 'woocommerce' ) . ' <a href="#" class="edit_address">' . esc_html__( 'Edit', 'woocommerce' ) . '</a></h3>';

		echo '<div class="address">';
		foreach ( $fields as $field ) {
			if ( '' === $field['value'] ) {
				echo '<p class="none_set"><strong>' . esc_html( $field['label'] ) . ':</strong> ' . esc_html__( 'Not provided.', 'woocommerce' ) . '</p>';
			} else {
				echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . esc_html( $field['value'] ) . '</p>';
			}
		}
		echo '</div>';

		echo '<div class="edit_address">';
		foreach ( $fields as $field ) {
			woocommerce_wp_text_input( $field, $order );
		}
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Hand the fields to the order screen's own save routine, so the values are saved with the rest of the order.
	 *
	 * The fields are added only while the order is being saved. They are not billing details and are not shown with them.
	 *
	 * @internal
	 *
	 * @param mixed $fields Billing fields of the order screen.
	 * @param mixed $order  Order being saved.
	 * @return mixed
	 */
	public function handle_woocommerce_admin_billing_fields( $fields, $order = null ) {
		if ( ! is_array( $fields ) || ! $order instanceof WC_Order || ! doing_action( 'woocommerce_process_shop_order_meta' ) ) {
			return $fields;
		}

		foreach ( $this->get_order_fields( $order ) as $input_id => $field ) {
			$fields[ $input_id ] = array_merge( $field, array( 'update_callback' => array( $this, 'update_order_value' ) ) );
		}

		return $fields;
	}

	/**
	 * Get the inputs for an order: the fields that apply to it, and any field it already has a value for.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, array<string, string>> Input definitions keyed by input ID, which is the field's meta key.
	 */
	private function get_order_fields( WC_Order $order ): array {
		$display_values = $this->regulatory_fields->get_order_display_values( $order );
		$all_fields     = $this->regulatory_fields->get_all_fields();
		$field_ids      = array_unique( array_merge( array_keys( $this->regulatory_fields->get_fields_for_order( $order ) ), array_keys( $display_values ) ) );
		$inputs         = array();

		foreach ( $field_ids as $field_id ) {
			$inputs[ RegulatoryFields::META_PREFIX . $field_id ] = array(
				'id'            => RegulatoryFields::META_PREFIX . $field_id,
				'label'         => $all_fields[ $field_id ]['label'],
				'value'         => $display_values[ $field_id ]['value'] ?? '',
				'type'          => 'text',
				'wrapper_class' => 'form-field-wide',
			);
		}

		return $inputs;
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
