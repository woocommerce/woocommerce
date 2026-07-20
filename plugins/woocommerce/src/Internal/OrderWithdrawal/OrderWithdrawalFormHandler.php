<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

/**
 * Prepares order withdrawal form data for rendering and review.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalFormHandler {

	public const NONCE_ACTION   = 'woocommerce_order_withdrawal';
	public const NONCE_FIELD    = 'woocommerce-order-withdrawal-nonce';
	public const ACTION_FIELD   = 'order_withdrawal_action';
	public const ACTION_REVIEW  = 'review';
	public const ACTION_CONFIRM = 'confirm';
	public const ACTION_EDIT    = 'edit';

	private const FIELD_PREFIX                  = 'order_withdrawal_';
	private const FIELD_FIRST_NAME              = 'first_name';
	private const FIELD_LAST_NAME               = 'last_name';
	private const FIELD_EMAIL                   = 'email';
	private const FIELD_EMAIL_CONFIRMATION      = 'email_confirmation';
	private const FIELD_ORDER_NUMBER            = 'order_number';
	private const FIELD_WITHDRAWAL_TYPE         = 'withdrawal_type';
	private const FIELD_ADDITIONAL_DETAILS      = 'additional_details';
	private const WITHDRAWAL_TYPE_FULL_ORDER    = 'full_order';
	private const WITHDRAWAL_TYPE_SPECIFIC_ONLY = 'specific_items_only';

	/**
	 * Get the current view state for the order withdrawal page.
	 *
	 * @return array{screen:string,data:array<string,string>,errors:array<string,string>}
	 *
	 * @since 11.1.0
	 */
	public function get_view_data(): array {
		$data   = $this->get_default_form_data();
		$errors = array();
		$screen = 'form';

		if ( ! $this->is_post_request() ) {
			return array(
				'screen' => $screen,
				'data'   => $data,
				'errors' => $errors,
			);
		}

		if ( ! $this->has_valid_nonce() ) {
			wc_add_notice( __( 'We could not verify your request. Please try again.', 'woocommerce' ), 'error' );
			return array(
				'screen' => $screen,
				'data'   => $data,
				'errors' => $errors,
			);
		}

		$data   = $this->get_posted_form_data();
		$action = $this->get_posted_action();

		if ( self::ACTION_EDIT === $action ) {
			return array(
				'screen' => $screen,
				'data'   => $data,
				'errors' => $errors,
			);
		}

		$errors = $this->validate_form_data( $data );

		if ( ! empty( $errors ) ) {
			$this->add_validation_notices( $errors );

			return array(
				'screen' => $screen,
				'data'   => $data,
				'errors' => $errors,
			);
		}

		$screen = self::ACTION_CONFIRM === $action ? 'confirmation' : 'review';

		return array(
			'screen' => $screen,
			'data'   => $data,
			'errors' => $errors,
		);
	}

	/**
	 * Get form field definitions prepared for the template.
	 *
	 * @param array<string,string> $errors Validation errors keyed by field.
	 * @return array<string,array<string,mixed>>
	 *
	 * @since 11.1.0
	 */
	public function get_prepared_form_fields( array $errors ): array {
		$fields = array();

		foreach ( $this->get_form_field_schema() as $field_key => $field ) {
			$field['name']        = $this->get_field_name( $field_key );
			$field['id']          = $this->get_field_name( $field_key );
			$field['input_class'] = array( 'woocommerce-Input', 'woocommerce-Input--' . (string) $field['type'] );

			if ( isset( $errors[ $field_key ] ) ) {
				$field['class'][]                                = 'woocommerce-invalid';
				$field['custom_attributes']['aria-invalid']      = 'true';
				$field['custom_attributes']['aria-errormessage'] = $this->get_field_name( $field_key ) . '_error';
			}

			if ( self::FIELD_ADDITIONAL_DETAILS === $field_key ) {
				$field['custom_attributes']['rows'] = '5';
			}

			$fields[ $field_key ] = $field;
		}

		return $fields;
	}

	/**
	 * Get hidden fields for review actions.
	 *
	 * @param array<string,string> $data Form data.
	 * @return array<int,array{name:string,value:string}>
	 *
	 * @since 11.1.0
	 */
	public function get_hidden_fields( array $data ): array {
		$hidden_fields = array();

		foreach ( $data as $field_key => $value ) {
			$hidden_fields[] = array(
				'name'  => $this->get_field_name( $field_key ),
				'value' => $value,
			);
		}

		return $hidden_fields;
	}

	/**
	 * Get rows for the review screen.
	 *
	 * @param array<string,string> $data Form data.
	 * @return array<int,array{label:string,value:string}>
	 *
	 * @since 11.1.0
	 */
	public function get_review_rows( array $data ): array {
		return array(
			array(
				'label' => __( 'Name', 'woocommerce' ),
				'value' => $this->get_customer_name( $data ),
			),
			array(
				'label' => __( 'Email address', 'woocommerce' ),
				'value' => $data[ self::FIELD_EMAIL ],
			),
			array(
				'label' => __( 'Order number', 'woocommerce' ),
				'value' => $data[ self::FIELD_ORDER_NUMBER ],
			),
			array(
				'label' => __( 'Withdrawing', 'woocommerce' ),
				'value' => $this->get_withdrawal_type_label( $data[ self::FIELD_WITHDRAWAL_TYPE ] ),
			),
			array(
				'label' => __( 'Additional details', 'woocommerce' ),
				'value' => '' === $data[ self::FIELD_ADDITIONAL_DETAILS ] ? __( 'None provided', 'woocommerce' ) : $data[ self::FIELD_ADDITIONAL_DETAILS ],
			),
		);
	}

	/**
	 * Get the available withdrawal type options.
	 *
	 * @return array<string,string>
	 *
	 * @since 11.1.0
	 */
	public function get_withdrawal_type_options(): array {
		return array(
			self::WITHDRAWAL_TYPE_FULL_ORDER    => __( 'The full order', 'woocommerce' ),
			self::WITHDRAWAL_TYPE_SPECIFIC_ONLY => __( 'Specific items only', 'woocommerce' ),
		);
	}

	/**
	 * Get the default form data.
	 *
	 * @return array<string,string>
	 */
	private function get_default_form_data(): array {
		$data = array();

		foreach ( $this->get_form_field_schema() as $field_key => $field ) {
			$data[ $field_key ] = (string) ( $field['default'] ?? '' );
		}

		return $data;
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
		$data = array();

		foreach ( $this->get_form_field_schema() as $field_key => $field ) {
			$field_name = $this->get_field_name( $field_key );
			$value      = 'textarea' === ( $field['type'] ?? '' ) ? $this->get_posted_textarea_value( $field_name ) : $this->get_posted_text_value( $field_name );

			if ( 'email' === ( $field['validation'] ?? '' ) ) {
				$value = sanitize_email( $value );
			}

			$data[ $field_key ] = $value;
		}

		return $data;
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

		if ( ! array_key_exists( $data[ self::FIELD_WITHDRAWAL_TYPE ], $this->get_withdrawal_type_options() ) ) {
			$errors[ self::FIELD_WITHDRAWAL_TYPE ] = __( 'Choose what you want to withdraw.', 'woocommerce' );
		}

		if ( self::WITHDRAWAL_TYPE_SPECIFIC_ONLY === $data[ self::FIELD_WITHDRAWAL_TYPE ] && '' === $data[ self::FIELD_ADDITIONAL_DETAILS ] ) {
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
			wc_add_notice( $message, 'error', array( 'id' => $this->get_field_name( $field_key ) ) );
		}
	}

	/**
	 * Get the form field definitions for the template.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_form_field_schema(): array {
		return array(
			self::FIELD_FIRST_NAME         => array(
				'default'      => '',
				'label'        => __( 'First name', 'woocommerce' ),
				'type'         => 'text',
				'class'        => array( 'woocommerce-form-row', 'woocommerce-form-row--first', 'form-row-first' ),
				'autocomplete' => 'given-name',
				'required'     => true,
			),
			self::FIELD_LAST_NAME          => array(
				'default'      => '',
				'label'        => __( 'Last name', 'woocommerce' ),
				'type'         => 'text',
				'class'        => array( 'woocommerce-form-row', 'woocommerce-form-row--last', 'form-row-last' ),
				'autocomplete' => 'family-name',
				'required'     => true,
			),
			self::FIELD_EMAIL              => array(
				'default'      => '',
				'label'        => __( 'Email address', 'woocommerce' ),
				'type'         => 'email',
				'class'        => array( 'woocommerce-form-row', 'woocommerce-form-row--wide', 'form-row-wide' ),
				'autocomplete' => 'email',
				'description'  => __( 'We\'ll send the acknowledgment of your withdrawal to this address.', 'woocommerce' ),
				'required'     => true,
				'validation'   => 'email',
			),
			self::FIELD_EMAIL_CONFIRMATION => array(
				'default'      => '',
				'label'        => __( 'Confirm email address', 'woocommerce' ),
				'type'         => 'email',
				'class'        => array( 'woocommerce-form-row', 'woocommerce-form-row--wide', 'form-row-wide' ),
				'autocomplete' => 'email',
				'required'     => true,
				'validation'   => 'email',
			),
			self::FIELD_ORDER_NUMBER       => array(
				'default'     => '',
				'label'       => __( 'Order number', 'woocommerce' ),
				'type'        => 'text',
				'class'       => array( 'woocommerce-form-row', 'woocommerce-form-row--wide', 'form-row-wide' ),
				'description' => __( 'It\'s in your order confirmation email, for example 1234.', 'woocommerce' ),
				'required'    => true,
			),
			self::FIELD_WITHDRAWAL_TYPE    => array(
				'default'  => self::WITHDRAWAL_TYPE_FULL_ORDER,
				'label'    => __( 'What do you want to withdraw?', 'woocommerce' ),
				'type'     => 'radio',
				'required' => true,
			),
			self::FIELD_ADDITIONAL_DETAILS => array(
				'default'     => '',
				'label'       => __( 'Additional details', 'woocommerce' ),
				'type'        => 'textarea',
				'class'       => array( 'woocommerce-form-row', 'woocommerce-form-row--wide', 'form-row-wide' ),
				'description' => __( 'No reason needed. If you selected specific items, list them here.', 'woocommerce' ),
				'required'    => false,
			),
		);
	}

	/**
	 * Get the posted name for a form field key.
	 *
	 * @param string $field_key Field key.
	 */
	private function get_field_name( string $field_key ): string {
		return self::FIELD_PREFIX . $field_key;
	}

	/**
	 * Get the label for a withdrawal type value.
	 *
	 * @param string $withdrawal_type Withdrawal type value.
	 */
	private function get_withdrawal_type_label( string $withdrawal_type ): string {
		$options = $this->get_withdrawal_type_options();

		return $options[ $withdrawal_type ] ?? '';
	}

	/**
	 * Get the customer's full name for display.
	 *
	 * @param array<string,string> $data Form data.
	 */
	private function get_customer_name( array $data ): string {
		return trim( $data[ self::FIELD_FIRST_NAME ] . ' ' . $data[ self::FIELD_LAST_NAME ] );
	}
}
