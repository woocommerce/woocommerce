<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

/**
 * Processes order withdrawal form requests.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalFormProcessor {

	public const NONCE_ACTION   = 'woocommerce_order_withdrawal';
	public const NONCE_FIELD    = 'woocommerce-order-withdrawal-nonce';
	public const ACTION_FIELD   = 'order_withdrawal_action';
	public const ACTION_REVIEW  = 'review';
	public const ACTION_CONFIRM = 'confirm';
	public const ACTION_EDIT    = 'edit';

	public const FIELD_PREFIX             = 'order_withdrawal_';
	public const FIELD_FIRST_NAME         = 'first_name';
	public const FIELD_LAST_NAME          = 'last_name';
	public const FIELD_EMAIL              = 'email';
	public const FIELD_EMAIL_CONFIRMATION = 'email_confirmation';
	public const FIELD_ORDER_NUMBER       = 'order_number';
	public const FIELD_WITHDRAWAL_TYPE    = 'withdrawal_type';
	public const FIELD_ADDITIONAL_DETAILS = 'additional_details';
	public const WITHDRAWAL_TYPE_FULL     = 'full_order';
	public const WITHDRAWAL_TYPE_SPECIFIC = 'specific_items_only';

	/**
	 * Process the current order withdrawal request.
	 *
	 * @since 11.1.0
	 */
	public function process_current_request(): OrderWithdrawalFormState {
		$data   = $this->get_default_form_data();
		$errors = array();
		$screen = 'form';

		if ( ! $this->is_post_request() ) {
			return new OrderWithdrawalFormState( $screen, $data, $errors );
		}

		if ( ! $this->has_valid_nonce() ) {
			wc_add_notice( __( 'We could not verify your request. Please try again.', 'woocommerce' ), 'error' );
			return new OrderWithdrawalFormState( $screen, $data, $errors );
		}

		$data   = $this->get_posted_form_data();
		$action = $this->get_posted_action();

		if ( self::ACTION_EDIT === $action ) {
			return new OrderWithdrawalFormState( $screen, $data, $errors );
		}

		$errors = $this->validate_form_data( $data );

		if ( ! empty( $errors ) ) {
			$this->add_validation_notices( $errors );
			return new OrderWithdrawalFormState( $screen, $data, $errors );
		}

		$screen = self::ACTION_CONFIRM === $action ? 'confirmation' : 'review';

		return new OrderWithdrawalFormState( $screen, $data, $errors );
	}

	/**
	 * Get the posted name for a form field key.
	 *
	 * @param string $field_key Field key.
	 *
	 * @since 11.1.0
	 */
	public static function get_field_name( string $field_key ): string {
		return self::FIELD_PREFIX . $field_key;
	}

	/**
	 * Get the default form data.
	 *
	 * @return array<string,string>
	 */
	private function get_default_form_data(): array {
		return array(
			self::FIELD_FIRST_NAME         => '',
			self::FIELD_LAST_NAME          => '',
			self::FIELD_EMAIL              => '',
			self::FIELD_EMAIL_CONFIRMATION => '',
			self::FIELD_ORDER_NUMBER       => '',
			self::FIELD_WITHDRAWAL_TYPE    => self::WITHDRAWAL_TYPE_FULL,
			self::FIELD_ADDITIONAL_DETAILS => '',
		);
	}

	/**
	 * Whether the current request is a form post.
	 */
	private function is_post_request(): bool {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

		return 'POST' === strtoupper( $request_method );
	}

	/**
	 * Get the submitted form action.
	 */
	private function get_posted_action(): string {
		$action = $this->get_posted_text_value( self::ACTION_FIELD );

		if ( in_array( $action, array( self::ACTION_REVIEW, self::ACTION_CONFIRM, self::ACTION_EDIT ), true ) ) {
			return $action;
		}

		return self::ACTION_REVIEW;
	}

	/**
	 * Verify the order withdrawal form nonce.
	 */
	private function has_valid_nonce(): bool {
		$nonce_value = $this->get_posted_text_value( self::NONCE_FIELD );

		return '' !== $nonce_value && (bool) wp_verify_nonce( $nonce_value, self::NONCE_ACTION );
	}

	/**
	 * Get sanitized submitted form data.
	 *
	 * @return array<string,string>
	 */
	private function get_posted_form_data(): array {
		return array(
			self::FIELD_FIRST_NAME         => $this->get_posted_text_value( self::get_field_name( self::FIELD_FIRST_NAME ) ),
			self::FIELD_LAST_NAME          => $this->get_posted_text_value( self::get_field_name( self::FIELD_LAST_NAME ) ),
			self::FIELD_EMAIL              => sanitize_email( $this->get_posted_text_value( self::get_field_name( self::FIELD_EMAIL ) ) ),
			self::FIELD_EMAIL_CONFIRMATION => sanitize_email( $this->get_posted_text_value( self::get_field_name( self::FIELD_EMAIL_CONFIRMATION ) ) ),
			self::FIELD_ORDER_NUMBER       => $this->get_posted_text_value( self::get_field_name( self::FIELD_ORDER_NUMBER ) ),
			self::FIELD_WITHDRAWAL_TYPE    => $this->get_posted_text_value( self::get_field_name( self::FIELD_WITHDRAWAL_TYPE ) ),
			self::FIELD_ADDITIONAL_DETAILS => $this->get_posted_textarea_value( self::get_field_name( self::FIELD_ADDITIONAL_DETAILS ) ),
		);
	}

	/**
	 * Get a sanitized text value from the current POST request.
	 *
	 * @param string $field_name Field name.
	 */
	private function get_posted_text_value( string $field_name ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification happens before submitted data is used.
		if ( ! isset( $_POST[ $field_name ] ) || ! is_scalar( $_POST[ $field_name ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $_POST[ $field_name ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Get a sanitized textarea value from the current POST request.
	 *
	 * @param string $field_name Field name.
	 */
	private function get_posted_textarea_value( string $field_name ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification happens before submitted data is used.
		if ( ! isset( $_POST[ $field_name ] ) || ! is_scalar( $_POST[ $field_name ] ) ) {
			return '';
		}

		return sanitize_textarea_field( wp_unslash( (string) $_POST[ $field_name ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Validate the form data.
	 *
	 * @param array<string,string> $data Form data.
	 * @return array<string,string>
	 */
	private function validate_form_data( array $data ): array {
		$errors = array();

		if ( '' === $data[ self::FIELD_FIRST_NAME ] ) {
			$errors[ self::FIELD_FIRST_NAME ] = __( 'First name is a required field.', 'woocommerce' );
		}

		if ( '' === $data[ self::FIELD_LAST_NAME ] ) {
			$errors[ self::FIELD_LAST_NAME ] = __( 'Last name is a required field.', 'woocommerce' );
		}

		if ( '' === $data[ self::FIELD_EMAIL ] || ! is_email( $data[ self::FIELD_EMAIL ] ) ) {
			$errors[ self::FIELD_EMAIL ] = __( 'Enter a valid email address.', 'woocommerce' );
		}

		if ( '' === $data[ self::FIELD_EMAIL_CONFIRMATION ] ) {
			$errors[ self::FIELD_EMAIL_CONFIRMATION ] = __( 'Confirm email address is a required field.', 'woocommerce' );
		} elseif ( 0 !== strcasecmp( $data[ self::FIELD_EMAIL ], $data[ self::FIELD_EMAIL_CONFIRMATION ] ) ) {
			$errors[ self::FIELD_EMAIL_CONFIRMATION ] = __( 'Email addresses do not match.', 'woocommerce' );
		}

		if ( '' === $data[ self::FIELD_ORDER_NUMBER ] ) {
			$errors[ self::FIELD_ORDER_NUMBER ] = __( 'Order number is a required field.', 'woocommerce' );
		}

		if ( ! in_array( $data[ self::FIELD_WITHDRAWAL_TYPE ], array( self::WITHDRAWAL_TYPE_FULL, self::WITHDRAWAL_TYPE_SPECIFIC ), true ) ) {
			$errors[ self::FIELD_WITHDRAWAL_TYPE ] = __( 'Choose what you want to withdraw.', 'woocommerce' );
		}

		if ( self::WITHDRAWAL_TYPE_SPECIFIC === $data[ self::FIELD_WITHDRAWAL_TYPE ] && '' === $data[ self::FIELD_ADDITIONAL_DETAILS ] ) {
			$errors[ self::FIELD_ADDITIONAL_DETAILS ] = __( 'List the specific items you want to withdraw.', 'woocommerce' );
		}

		return $errors;
	}

	/**
	 * Add form validation notices.
	 *
	 * @param array<string,string> $errors Validation errors keyed by field.
	 */
	private function add_validation_notices( array $errors ): void {
		foreach ( $errors as $field_key => $message ) {
			wc_add_notice( $message, 'error', array( 'id' => self::get_field_name( $field_key ) ) );
		}
	}
}
