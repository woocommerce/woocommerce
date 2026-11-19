<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;
use WP_Error;

/**
 * Collects regulatory fields on the shortcode checkout.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class ClassicCheckout implements RegisterHooksInterface {

	/**
	 * Prefix of a field's input name and element ID in the checkout form.
	 */
	private const INPUT_PREFIX = 'regulatory_';

	/**
	 * Key the submitted values are kept under in the posted checkout data.
	 */
	private const POSTED_DATA_KEY = 'regulatory_fields';

	/**
	 * CSS class of the element that wraps the fields. The checkout script replaces this element when the address changes.
	 */
	private const WRAPPER_CLASS = 'woocommerce-regulatory-fields';

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
		add_action( 'woocommerce_checkout_after_customer_details', array( $this, 'handle_woocommerce_checkout_after_customer_details' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'handle_woocommerce_update_order_review_fragments' ) );
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'handle_woocommerce_checkout_posted_data' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'handle_woocommerce_after_checkout_validation' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'handle_woocommerce_checkout_create_order' ), 10, 2 );
	}

	/**
	 * Output the fields below the billing and shipping forms.
	 *
	 * @internal
	 */
	public function handle_woocommerce_checkout_after_customer_details(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from woocommerce_form_field(), which escapes its output.
		echo $this->get_fields_html( array() );
	}

	/**
	 * Re-render the fields when the checkout refreshes after an address or shipping method change.
	 *
	 * @internal
	 *
	 * @param mixed $fragments HTML fragments keyed by the selector each one replaces.
	 * @return mixed
	 */
	public function handle_woocommerce_update_order_review_fragments( $fragments ) {
		if ( ! is_array( $fragments ) ) {
			return $fragments;
		}

		$form_data = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked by WC_AJAX::update_order_review(); values are sanitized in get_fields_html().
		wp_parse_str( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ? wp_unslash( $_POST['post_data'] ) : '', $form_data );

		$fragments[ '.' . self::WRAPPER_CLASS ] = $this->get_fields_html( $form_data );

		return $fragments;
	}

	/**
	 * Add the submitted values to the posted checkout data.
	 *
	 * @internal
	 *
	 * @param mixed $data Posted checkout data.
	 * @return mixed
	 */
	public function handle_woocommerce_checkout_posted_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$data[ self::POSTED_DATA_KEY ] = array();

		foreach ( array_keys( $this->regulatory_fields->get_all_fields() ) as $field_id ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked by WC_Checkout::process_checkout() before the posted data is read.
			$value = wc_clean( wp_unslash( $_POST[ self::INPUT_PREFIX . $field_id ] ?? '' ) );

			$data[ self::POSTED_DATA_KEY ][ $field_id ] = is_string( $value ) ? $value : '';
		}

		return $data;
	}

	/**
	 * Check the submitted values for the fields that apply to the cart.
	 *
	 * @internal
	 *
	 * @param mixed $data   Posted checkout data.
	 * @param mixed $errors Validation errors.
	 */
	public function handle_woocommerce_after_checkout_validation( $data, $errors ): void {
		if ( ! is_array( $data ) || ! $errors instanceof WP_Error ) {
			return;
		}

		$field_errors = new WP_Error();
		$this->regulatory_fields->get_valid_values( $this->regulatory_fields->get_fields_for_cart(), $data[ self::POSTED_DATA_KEY ] ?? array(), $field_errors );

		foreach ( $field_errors->get_error_codes() as $field_id ) {
			$errors->add( self::INPUT_PREFIX . $field_id, $field_errors->get_error_message( $field_id ), array( 'id' => self::INPUT_PREFIX . $field_id ) );
		}
	}

	/**
	 * Store the submitted values on the order being created.
	 *
	 * @internal
	 *
	 * @param mixed $order Order being created.
	 * @param mixed $data  Posted checkout data.
	 */
	public function handle_woocommerce_checkout_create_order( $order, $data ): void {
		if ( ! $order instanceof WC_Order || ! is_array( $data ) ) {
			return;
		}

		$values = $this->regulatory_fields->get_valid_values( $this->regulatory_fields->get_fields_for_cart(), $data[ self::POSTED_DATA_KEY ] ?? array(), new WP_Error() );

		$this->regulatory_fields->set_order_values( $order, $values );
	}

	/**
	 * Build the wrapper element and the fields that apply to the cart.
	 *
	 * The wrapper is always present, so the checkout script has an element to replace once fields start to apply.
	 *
	 * @param array<string|int, mixed> $form_data Checkout form values, used to keep what the shopper already typed.
	 * @return string
	 */
	private function get_fields_html( array $form_data ): string {
		$html = '';

		foreach ( $this->regulatory_fields->get_fields_for_cart() as $field_id => $field ) {
			$value = $form_data[ self::INPUT_PREFIX . $field_id ] ?? '';

			$html .= woocommerce_form_field(
				self::INPUT_PREFIX . $field_id,
				array(
					'type'     => 'text',
					'label'    => $field['label'],
					'required' => $field['required'],
					'class'    => array( 'form-row-wide' ),
					'return'   => true,
				),
				is_string( $value ) ? sanitize_text_field( $value ) : ''
			);
		}

		return '<div class="' . esc_attr( self::WRAPPER_CLASS ) . '">' . $html . '</div>';
	}
}
