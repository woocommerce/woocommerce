<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use RuntimeException;
use Throwable;
use WC_Order;

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

	private const LOGGER_SOURCE = 'order-withdrawal';

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

		if ( self::ACTION_CONFIRM === $action ) {
			if ( ! $this->submit_order_withdrawal( $data ) ) {
				return new OrderWithdrawalFormState( 'review', $data, $errors );
			}

			$screen = 'confirmation';
		} else {
			$screen = 'review';
		}

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

	/**
	 * Submit a validated order withdrawal request.
	 *
	 * @param array<string,string> $data Form data.
	 */
	private function submit_order_withdrawal( array $data ): bool {
		try {
			$matched_order = $this->get_matching_order( $data );

			if ( $matched_order instanceof WC_Order ) {
				try {
					if ( ! $this->add_order_withdrawal_note( $matched_order, $data ) ) {
						$this->log_order_note_error( $matched_order );
					}
				} catch ( Throwable $e ) {
					$this->log_order_note_error( $matched_order, $e );
				}
			}

			$this->send_order_withdrawal_emails( $data, $matched_order );
			return true;
		} catch ( Throwable $e ) {
			$this->log_submission_error( $e );
			wc_add_notice( __( 'We could not submit your withdrawal request. Please try again or contact us if the problem continues.', 'woocommerce' ), 'error' );

			return false;
		}
	}

	/**
	 * Get an order only when every submitted order-identifying field matches.
	 *
	 * @param array<string,string> $data Form data.
	 */
	private function get_matching_order( array $data ): ?WC_Order {
		$order_number = $this->normalize_order_number( $data[ self::FIELD_ORDER_NUMBER ] );

		if ( '' === $order_number || ! ctype_digit( $order_number ) ) {
			return null;
		}

		$order = wc_get_order( absint( $order_number ) );

		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		if ( ! $this->order_matches_form_data( $order, $data ) ) {
			return null;
		}

		return $order;
	}

	/**
	 * Whether a candidate order exactly matches the submitted identifying data.
	 *
	 * @param WC_Order             $order Candidate order.
	 * @param array<string,string> $data  Form data.
	 */
	private function order_matches_form_data( WC_Order $order, array $data ): bool {
		return $this->normalize_order_number( (string) $order->get_order_number() ) === $this->normalize_order_number( $data[ self::FIELD_ORDER_NUMBER ] )
			&& $this->text_values_match( $order->get_billing_first_name( 'edit' ), $data[ self::FIELD_FIRST_NAME ] )
			&& $this->text_values_match( $order->get_billing_last_name( 'edit' ), $data[ self::FIELD_LAST_NAME ] )
			&& $this->text_values_match( $order->get_billing_email( 'edit' ), $data[ self::FIELD_EMAIL ] );
	}

	/**
	 * Normalize a submitted order number for lookup and comparison.
	 *
	 * @param string $order_number Order number.
	 */
	private function normalize_order_number( string $order_number ): string {
		$order_number = trim( $order_number );

		if ( 0 === strpos( $order_number, '#' ) ) {
			$order_number = trim( substr( $order_number, 1 ) );
		}

		return $order_number;
	}

	/**
	 * Compare submitted text values for identity while ignoring casing and surrounding spaces.
	 *
	 * @param string $stored_value    Stored order value.
	 * @param string $submitted_value Submitted form value.
	 */
	private function text_values_match( string $stored_value, string $submitted_value ): bool {
		return 0 === strcasecmp( trim( $stored_value ), trim( $submitted_value ) );
	}

	/**
	 * Add the withdrawal request note to a matched order.
	 *
	 * @param WC_Order             $order Matched order.
	 * @param array<string,string> $data  Form data.
	 */
	private function add_order_withdrawal_note( WC_Order $order, array $data ): bool {
		$note = sprintf(
			/* translators: 1: customer name, 2: customer email address. */
			__( 'Order withdrawal requested by %1$s (%2$s).', 'woocommerce' ),
			$this->get_customer_name( $data ),
			$data[ self::FIELD_EMAIL ]
		);

		if ( self::WITHDRAWAL_TYPE_SPECIFIC === $data[ self::FIELD_WITHDRAWAL_TYPE ] ) {
			$note .= "\n\n" . sprintf(
				/* translators: %s: items the customer listed for partial withdrawal. */
				__( 'Items requested for withdrawal: %s', 'woocommerce' ),
				$data[ self::FIELD_ADDITIONAL_DETAILS ]
			);
		}

		return (bool) $order->add_order_note( $note, 0, false, array( 'note_group' => OrderNoteGroup::ORDER_UPDATE ) );
	}

	/**
	 * Send customer and merchant order withdrawal emails.
	 *
	 * @param array<string,string> $data          Form data.
	 * @param WC_Order|null        $matched_order Matched order, if found.
	 * @throws RuntimeException When either email cannot be sent.
	 */
	private function send_order_withdrawal_emails( array $data, ?WC_Order $matched_order ): void {
		$submitted_at  = time();
		$customer_sent = $this->send_customer_order_withdrawal_email( $data, $submitted_at );
		$merchant_sent = $this->send_merchant_order_withdrawal_email( $data, $matched_order, $submitted_at );

		if ( ! $customer_sent || ! $merchant_sent ) {
			throw new RuntimeException(
				sprintf(
					'Order withdrawal notification email failed. Customer email sent: %s. Merchant email sent: %s.',
					$customer_sent ? 'yes' : 'no',
					$merchant_sent ? 'yes' : 'no'
				)
			);
		}
	}

	/**
	 * Send the customer order withdrawal acknowledgement email.
	 *
	 * @param array<string,string> $data         Form data.
	 * @param int                  $submitted_at Unix timestamp for the submission.
	 */
	private function send_customer_order_withdrawal_email( array $data, int $submitted_at ): bool {
		$subject = __( 'We received your withdrawal request', 'woocommerce' );
		$heading = __( 'We received your withdrawal request', 'woocommerce' );
		$body    = '<p>' . esc_html__( 'We have received your request to withdraw from the order below.', 'woocommerce' ) . '</p>';
		$body   .= $this->get_email_details_html( $data, $submitted_at );
		$body   .= '<p>' . esc_html__( 'We will review your request and contact you about next steps, including any refund due.', 'woocommerce' ) . '</p>';

		return wc_mail(
			$data[ self::FIELD_EMAIL ],
			$subject,
			$this->wrap_email_message( $heading, $body )
		);
	}

	/**
	 * Send the merchant order withdrawal notification email.
	 *
	 * @param array<string,string> $data          Form data.
	 * @param WC_Order|null        $matched_order Matched order, if found.
	 * @param int                  $submitted_at  Unix timestamp for the submission.
	 * @throws RuntimeException When no merchant recipient is configured.
	 */
	private function send_merchant_order_withdrawal_email( array $data, ?WC_Order $matched_order, int $submitted_at ): bool {
		$recipient = sanitize_email( (string) get_option( 'admin_email' ) );

		if ( '' === $recipient || ! is_email( $recipient ) ) {
			throw new RuntimeException( 'No valid merchant email recipient is configured for order withdrawal notifications.' );
		}

		$subject = sprintf(
			/* translators: %s: order number. */
			__( 'Order withdrawal request for order %s', 'woocommerce' ),
			$data[ self::FIELD_ORDER_NUMBER ]
		);
		$heading = __( 'Order withdrawal request received', 'woocommerce' );
		$body    = '<p>' . esc_html__( 'A customer submitted an order withdrawal request.', 'woocommerce' ) . '</p>';

		if ( $matched_order instanceof WC_Order ) {
			$body .= '<p>' . esc_html__( 'WooCommerce matched this request to an order and added an order note.', 'woocommerce' ) . '</p>';
		} else {
			$body .= '<p>' . esc_html__( 'WooCommerce could not match this request to an order automatically, so no order note was added.', 'woocommerce' ) . '</p>';
		}

		$body .= $this->get_email_details_html( $data, $submitted_at );

		if ( $matched_order instanceof WC_Order ) {
			$body .= sprintf(
				'<p>%s</p>',
				sprintf(
					/* translators: %d: order ID. */
					esc_html__( 'Matched order ID: %d', 'woocommerce' ),
					$matched_order->get_id()
				)
			);
		}

		return wc_mail(
			$recipient,
			$subject,
			$this->wrap_email_message( $heading, $body ),
			$this->get_merchant_email_headers( $data )
		);
	}

	/**
	 * Get merchant email headers.
	 *
	 * @param array<string,string> $data Form data.
	 * @return string
	 */
	private function get_merchant_email_headers( array $data ): string {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$name    = $this->get_customer_name( $data );
		$email   = $data[ self::FIELD_EMAIL ];

		if ( '' !== $name && is_email( $email ) ) {
			$headers[] = sprintf( 'Reply-To: %1$s <%2$s>', $name, $email );
		}

		return implode( "\r\n", $headers );
	}

	/**
	 * Wrap an email body in the WooCommerce email template.
	 *
	 * @param string $heading Email heading.
	 * @param string $body    Email body.
	 */
	private function wrap_email_message( string $heading, string $body ): string {
		return WC()->mailer()->wrap_message( $heading, $body );
	}

	/**
	 * Get the email details list.
	 *
	 * @param array<string,string> $data         Form data.
	 * @param int                  $submitted_at Unix timestamp for the submission.
	 */
	private function get_email_details_html( array $data, int $submitted_at ): string {
		$date_format        = (string) get_option( 'date_format' );
		$time_format        = (string) get_option( 'time_format' );
		$additional_details = '' === $data[ self::FIELD_ADDITIONAL_DETAILS ] ? __( 'None provided', 'woocommerce' ) : $data[ self::FIELD_ADDITIONAL_DETAILS ];
		$submitted_at_text  = wp_date( trim( $date_format . ' ' . $time_format ), $submitted_at );

		if ( false === $submitted_at_text ) {
			$submitted_at_text = '';
		}

		$rows = array(
			__( 'Submitted', 'woocommerce' )          => $submitted_at_text,
			__( 'Name', 'woocommerce' )               => $this->get_customer_name( $data ),
			__( 'Email address', 'woocommerce' )      => $data[ self::FIELD_EMAIL ],
			__( 'Order number', 'woocommerce' )       => $data[ self::FIELD_ORDER_NUMBER ],
			__( 'Withdrawing', 'woocommerce' )        => $this->get_withdrawal_type_label( $data[ self::FIELD_WITHDRAWAL_TYPE ] ),
			__( 'Additional details', 'woocommerce' ) => $additional_details,
		);

		$html = '<ul>';

		foreach ( $rows as $label => $value ) {
			$html .= sprintf(
				'<li><strong>%1$s:</strong> %2$s</li>',
				esc_html( $label ),
				nl2br( esc_html( $value ) )
			);
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Get the customer's full name for display.
	 *
	 * @param array<string,string> $data Form data.
	 */
	private function get_customer_name( array $data ): string {
		return trim( $data[ self::FIELD_FIRST_NAME ] . ' ' . $data[ self::FIELD_LAST_NAME ] );
	}

	/**
	 * Get the label for a withdrawal type value.
	 *
	 * @param string $withdrawal_type Withdrawal type value.
	 */
	private function get_withdrawal_type_label( string $withdrawal_type ): string {
		$options = array(
			self::WITHDRAWAL_TYPE_FULL     => __( 'The full order', 'woocommerce' ),
			self::WITHDRAWAL_TYPE_SPECIFIC => __( 'Specific items only', 'woocommerce' ),
		);

		return $options[ $withdrawal_type ] ?? '';
	}

	/**
	 * Log a submission failure.
	 *
	 * @param Throwable $e Submission error.
	 */
	private function log_submission_error( Throwable $e ): void {
		wc_get_logger()->warning(
			sprintf( 'Order withdrawal submission failed: %s', $e->getMessage() ),
			array( 'source' => self::LOGGER_SOURCE )
		);
	}

	/**
	 * Log an order note failure without failing the submission.
	 *
	 * @param WC_Order       $order Matched order.
	 * @param Throwable|null $e     Order note error.
	 */
	private function log_order_note_error( WC_Order $order, ?Throwable $e = null ): void {
		$message = sprintf( 'Order withdrawal note could not be added to order %d.', $order->get_id() );

		if ( $e instanceof Throwable ) {
			$message .= sprintf( ' Error: %s', $e->getMessage() );
		}

		wc_get_logger()->warning(
			$message,
			array( 'source' => self::LOGGER_SOURCE )
		);
	}
}
