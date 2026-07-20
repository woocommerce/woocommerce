<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Registers the order withdrawal endpoint and related feature-gated UI.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalController implements RegisterHooksInterface {

	private const FEATURE_ID      = 'order_withdrawal';
	private const ENDPOINT_KEY    = 'order-withdrawal';
	private const ENDPOINT_SLUG   = 'withdraw-order';
	private const ENDPOINT_OPTION = 'woocommerce_myaccount_order_withdrawal_endpoint';
	private const NONCE_ACTION    = 'woocommerce_order_withdrawal';
	private const NONCE_FIELD     = 'woocommerce-order-withdrawal-nonce';
	private const FIELD_PREFIX    = 'order_withdrawal_';
	private const ACTION_FIELD    = 'order_withdrawal_action';
	private const ACTION_REVIEW   = 'review';
	private const ACTION_CONFIRM  = 'confirm';
	private const ACTION_EDIT     = 'edit';

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
	 * Register hooks.
	 */
	public function register(): void {
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'maybe_flush_rewrite_rules' ), 10, 1 );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_var' ), 10, 1 );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT_KEY . '_title', array( $this, 'get_endpoint_title' ), 10, 1 );
		add_filter( 'woocommerce_settings_pages', array( $this, 'add_endpoint_setting' ), 10, 1 );
		add_action( 'woocommerce_account_' . self::ENDPOINT_KEY . '_endpoint', array( $this, 'render_view' ) );
	}

	/**
	 * Whether order withdrawal is enabled.
	 */
	public function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}

	/**
	 * Whether the current My Account request is for the public order withdrawal endpoint.
	 */
	public function is_endpoint_request(): bool {
		global $wp;

		return $this->is_enabled()
			&& isset( $wp->query_vars[ self::ENDPOINT_KEY ] )
			&& self::ENDPOINT_KEY === WC()->query->get_current_endpoint();
	}

	/**
	 * Queue a rewrite rules flush when the feature is toggled.
	 *
	 * @param string $feature_id Feature being toggled.
	 */
	public function maybe_flush_rewrite_rules( string $feature_id ): void {
		if ( self::FEATURE_ID === $feature_id ) {
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		}
	}

	/**
	 * Register the order withdrawal query var.
	 *
	 * @param array $query_vars Existing query vars keyed by endpoint key.
	 */
	public function add_query_var( $query_vars ): array {
		if ( ! is_array( $query_vars ) ) {
			return array();
		}

		if ( $this->is_enabled() ) {
			$query_vars[ self::ENDPOINT_KEY ] = (string) get_option( self::ENDPOINT_OPTION, self::ENDPOINT_SLUG );
		}

		return $query_vars;
	}

	/**
	 * Order withdrawal endpoint page title.
	 *
	 * @param string $title Default title.
	 */
	public function get_endpoint_title( $title ): string {
		return __( 'Withdraw from contract', 'woocommerce' );
	}

	/**
	 * Add the endpoint setting when the feature is enabled.
	 *
	 * @param array $settings Page settings.
	 */
	public function add_endpoint_setting( $settings ): array {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		if ( ! $this->is_enabled() ) {
			return $settings;
		}

		$endpoint_setting = array(
			'title'    => __( 'Order withdrawal', 'woocommerce' ),
			'desc'     => __( 'Endpoint for the order withdrawal page.', 'woocommerce' ),
			'id'       => self::ENDPOINT_OPTION,
			'type'     => 'text',
			'default'  => self::ENDPOINT_SLUG,
			'desc_tip' => true,
		);

		$new_settings = array();
		$added        = false;

		foreach ( $settings as $setting ) {
			if ( is_array( $setting ) && self::ENDPOINT_OPTION === ( $setting['id'] ?? '' ) ) {
				return $settings;
			}

			if (
				! $added &&
				is_array( $setting ) &&
				'sectionend' === ( $setting['type'] ?? '' ) &&
				'account_endpoint_options' === ( $setting['id'] ?? '' )
			) {
				$new_settings[] = $endpoint_setting;
				$added          = true;
			}

			$new_settings[] = $setting;
		}

		if ( ! $added ) {
			$new_settings[] = $endpoint_setting;
		}

		return $new_settings;
	}

	/**
	 * Render the order withdrawal view.
	 */
	public function render_view(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		wc_get_template( 'myaccount/form-order-withdrawal.php', $this->get_template_args() );
	}

	/**
	 * Get template arguments for the order withdrawal form.
	 *
	 * @return array<string,mixed>
	 */
	private function get_template_args(): array {
		$view_data = $this->get_view_data();
		$data      = $view_data['data'];

		return array(
			'screen'                  => $view_data['screen'],
			'data'                    => $data,
			'errors'                  => $view_data['errors'],
			'fields'                  => $this->get_form_fields(),
			'withdrawal_type_options' => $this->get_withdrawal_type_options(),
			'hidden_fields'           => $this->get_hidden_fields( $data ),
			'review_rows'             => $this->get_review_rows( $data ),
			'nonce_action'            => self::NONCE_ACTION,
			'nonce_field'             => self::NONCE_FIELD,
			'action_field'            => self::ACTION_FIELD,
			'action_review'           => self::ACTION_REVIEW,
			'action_confirm'          => self::ACTION_CONFIRM,
			'action_edit'             => self::ACTION_EDIT,
			'shop_url'                => $this->get_shop_url(),
		);
	}

	/**
	 * Get the current view state for the order withdrawal page.
	 *
	 * @return array{screen:string,data:array<string,string>,errors:array<string,string>}
	 */
	private function get_view_data(): array {
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

		$data = $this->get_posted_form_data();

		if ( ! $this->has_valid_nonce() ) {
			wc_add_notice( __( 'We could not verify your request. Please try again.', 'woocommerce' ), 'error' );
			return array(
				'screen' => $screen,
				'data'   => $data,
				'errors' => $errors,
			);
		}

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

		if ( self::ACTION_CONFIRM === $action ) {
			$screen = 'confirmation';
		} else {
			$screen = 'review';
		}

		return array(
			'screen' => $screen,
			'data'   => $data,
			'errors' => $errors,
		);
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
			self::FIELD_WITHDRAWAL_TYPE    => self::WITHDRAWAL_TYPE_FULL_ORDER,
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
		$data = $this->get_default_form_data();

		$data[ self::FIELD_FIRST_NAME ]         = $this->get_posted_text_value( $this->get_field_name( self::FIELD_FIRST_NAME ) );
		$data[ self::FIELD_LAST_NAME ]          = $this->get_posted_text_value( $this->get_field_name( self::FIELD_LAST_NAME ) );
		$data[ self::FIELD_EMAIL ]              = sanitize_email( $this->get_posted_text_value( $this->get_field_name( self::FIELD_EMAIL ) ) );
		$data[ self::FIELD_EMAIL_CONFIRMATION ] = sanitize_email( $this->get_posted_text_value( $this->get_field_name( self::FIELD_EMAIL_CONFIRMATION ) ) );
		$data[ self::FIELD_ORDER_NUMBER ]       = $this->get_posted_text_value( $this->get_field_name( self::FIELD_ORDER_NUMBER ) );
		$data[ self::FIELD_WITHDRAWAL_TYPE ]    = $this->get_posted_text_value( $this->get_field_name( self::FIELD_WITHDRAWAL_TYPE ) );
		$data[ self::FIELD_ADDITIONAL_DETAILS ] = $this->get_posted_textarea_value( $this->get_field_name( self::FIELD_ADDITIONAL_DETAILS ) );

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
	private function get_form_fields(): array {
		return array(
			self::FIELD_FIRST_NAME         => array(
				'name'         => $this->get_field_name( self::FIELD_FIRST_NAME ),
				'id'           => $this->get_field_name( self::FIELD_FIRST_NAME ),
				'label'        => __( 'First name', 'woocommerce' ),
				'type'         => 'text',
				'classes'      => array( 'woocommerce-form-row--first', 'form-row-first' ),
				'autocomplete' => 'given-name',
				'required'     => true,
			),
			self::FIELD_LAST_NAME          => array(
				'name'         => $this->get_field_name( self::FIELD_LAST_NAME ),
				'id'           => $this->get_field_name( self::FIELD_LAST_NAME ),
				'label'        => __( 'Last name', 'woocommerce' ),
				'type'         => 'text',
				'classes'      => array( 'woocommerce-form-row--last', 'form-row-last' ),
				'autocomplete' => 'family-name',
				'required'     => true,
			),
			self::FIELD_EMAIL              => array(
				'name'         => $this->get_field_name( self::FIELD_EMAIL ),
				'id'           => $this->get_field_name( self::FIELD_EMAIL ),
				'label'        => __( 'Email address', 'woocommerce' ),
				'type'         => 'email',
				'classes'      => array( 'woocommerce-form-row--wide', 'form-row-wide' ),
				'autocomplete' => 'email',
				'description'  => __( 'We\'ll send the acknowledgment of your withdrawal to this address.', 'woocommerce' ),
				'required'     => true,
			),
			self::FIELD_EMAIL_CONFIRMATION => array(
				'name'         => $this->get_field_name( self::FIELD_EMAIL_CONFIRMATION ),
				'id'           => $this->get_field_name( self::FIELD_EMAIL_CONFIRMATION ),
				'label'        => __( 'Confirm email address', 'woocommerce' ),
				'type'         => 'email',
				'classes'      => array( 'woocommerce-form-row--wide', 'form-row-wide' ),
				'autocomplete' => 'email',
				'required'     => true,
			),
			self::FIELD_ORDER_NUMBER       => array(
				'name'        => $this->get_field_name( self::FIELD_ORDER_NUMBER ),
				'id'          => $this->get_field_name( self::FIELD_ORDER_NUMBER ),
				'label'       => __( 'Order number', 'woocommerce' ),
				'type'        => 'text',
				'classes'     => array( 'woocommerce-form-row--wide', 'form-row-wide' ),
				'description' => __( 'It\'s in your order confirmation email, for example 1234.', 'woocommerce' ),
				'required'    => true,
			),
			self::FIELD_WITHDRAWAL_TYPE    => array(
				'name'     => $this->get_field_name( self::FIELD_WITHDRAWAL_TYPE ),
				'id'       => $this->get_field_name( self::FIELD_WITHDRAWAL_TYPE ),
				'label'    => __( 'What do you want to withdraw?', 'woocommerce' ),
				'required' => true,
			),
			self::FIELD_ADDITIONAL_DETAILS => array(
				'name'        => $this->get_field_name( self::FIELD_ADDITIONAL_DETAILS ),
				'id'          => $this->get_field_name( self::FIELD_ADDITIONAL_DETAILS ),
				'label'       => __( 'Additional details', 'woocommerce' ),
				'description' => __( 'No reason needed. If you selected specific items, list them here.', 'woocommerce' ),
				'required'    => false,
			),
		);
	}

	/**
	 * Get hidden fields for review actions.
	 *
	 * @param array<string,string> $data Form data.
	 * @return array<int,array{name:string,value:string}>
	 */
	private function get_hidden_fields( array $data ): array {
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
	 */
	private function get_review_rows( array $data ): array {
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
	 * Get the posted name for a form field key.
	 *
	 * @param string $field_key Field key.
	 */
	private function get_field_name( string $field_key ): string {
		return self::FIELD_PREFIX . $field_key;
	}

	/**
	 * Get the available withdrawal type options.
	 *
	 * @return array<string,string>
	 */
	private function get_withdrawal_type_options(): array {
		return array(
			self::WITHDRAWAL_TYPE_FULL_ORDER    => __( 'The full order', 'woocommerce' ),
			self::WITHDRAWAL_TYPE_SPECIFIC_ONLY => __( 'Specific items only', 'woocommerce' ),
		);
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

	/**
	 * Get a safe shop URL for the confirmation link.
	 */
	private function get_shop_url(): string {
		$shop_url = wc_get_page_permalink( 'shop' );

		return $shop_url ? $shop_url : home_url( '/' );
	}
}
