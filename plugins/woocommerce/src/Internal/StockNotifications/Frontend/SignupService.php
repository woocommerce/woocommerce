<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;

/**
 * A class for handling the business logic of the signup process.
 *
 * @internal
 */
class SignupService {

	// phpcs:disable
	public const SIGNUP_ALREADY_JOINED                        = 'already_joined';
	public const SIGNUP_ALREADY_JOINED_DOUBLE_OPT_IN          = 'already_joined_double_opt_in';
	public const SIGNUP_SUCCESS                               = 'success';
	public const SIGNUP_SUCCESS_ACCOUNT_CREATED               = 'success_account_created';
	public const SIGNUP_SUCCESS_ACCOUNT_CREATED_DOUBLE_OPT_IN = 'success_account_created_double_opt_in';
	public const SIGNUP_SUCCESS_DOUBLE_OPT_IN                 = 'success_double_opt_in';

	public const ERROR_FAILED           = 'failed_to_signup';
	public const ERROR_INVALID_REQUEST  = 'invalid_request';
	public const ERROR_INVALID_PRODUCT  = 'invalid_product';
	public const ERROR_REQUIRES_ACCOUNT = 'requires_account';
	public const ERROR_RATE_LIMITED     = 'rate_limited';
	public const ERROR_INVALID_USER     = 'invalid_user';
	public const ERROR_INVALID_EMAIL    = 'invalid_email';
	public const ERROR_INVALID_OPT_IN   = 'invalid_opt_in';
	// phpcs:enable

	/**
	 * Eligibility service.
	 *
	 * @var EligibilityService
	 */
	private EligibilityService $eligibility_service;

	/**
	 * Notification management service.
	 *
	 * @var NotificationManagementService
	 */
	private NotificationManagementService $notification_management_service;

	/**
	 * Init the service.
	 *
	 * @internal
	 *
	 * @param EligibilityService            $eligibility_service The eligibility service.
	 * @param NotificationManagementService $notification_management_service The notification management service.
	 */
	final public function init( EligibilityService $eligibility_service, NotificationManagementService $notification_management_service ) {
		$this->eligibility_service             = $eligibility_service;
		$this->notification_management_service = $notification_management_service;
	}

	/**
	 * Signup.
	 *
	 * @param int    $product_id The product ID.
	 * @param int    $user_id The user ID.
	 * @param string $user_email The user email.
	 * @param array  $posted_attributes The posted attributes (Optional).
	 * @return SignupResult|\WP_Error The signup result.
	 */
	public function signup( int $product_id, int $user_id, string $user_email, array $posted_attributes = array() ) {

		// Sanity checks.
		if ( ! Config::allows_signups() ) {
			return new \WP_Error( self::ERROR_FAILED );
		}

		if ( empty( $user_email ) && empty( $user_id ) ) {
			return new \WP_Error( self::ERROR_INVALID_REQUEST );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new \WP_Error( self::ERROR_INVALID_PRODUCT );
		}

		if ( ! $this->eligibility_service->is_product_eligible( $product ) ) {
			return new \WP_Error( self::ERROR_INVALID_PRODUCT );
		}

		if ( $this->eligibility_service->is_stock_status_eligible( $product->get_stock_status() ) ) {
			return new \WP_Error( self::ERROR_INVALID_REQUEST );
		}

		if ( ! $this->eligibility_service->product_allows_signups( $product ) ) {
			return new \WP_Error( self::ERROR_INVALID_PRODUCT );
		}

		$notification = $this->is_already_signed_up( $product_id, $user_id, $user_email, $posted_attributes );
		if ( $notification instanceof Notification ) {
			if ( NotificationStatus::ACTIVE === $notification->get_status() ) {
				return new SignupResult( self::SIGNUP_ALREADY_JOINED, $notification );
			}

			if ( NotificationStatus::PENDING === $notification->get_status() ) {
				if ( Config::requires_double_opt_in() ) {
					return new SignupResult( self::SIGNUP_ALREADY_JOINED_DOUBLE_OPT_IN, $notification );
				}

				// If the notification is pending and double opt-in is not required, skip and activate the notification.
				$notification->set_status( NotificationStatus::ACTIVE );
				$notification->save();

				/**
				 * Action: woocommerce_customer_stock_notifications_signup
				 *
				 * @since 10.2.0
				 *
				 * @param Notification $notification The notification.
				 */
				do_action( 'woocommerce_customer_stock_notifications_signup', $notification );
				return new SignupResult( self::SIGNUP_SUCCESS, $notification );
			}
		}

		$account_created = null;
		if ( empty( $user_id ) && Config::creates_account_on_signup() ) {
			$account_created = $this->create_customer( $user_email );
			$user_id         = $account_created ? $account_created : $user_id;
		}

		$notification = new Notification();
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_product_id( $product_id );
		$notification->set_user_id( $user_id );
		$notification->set_user_email( $user_email );

		if ( ! empty( $posted_attributes ) ) {
			$notification->update_meta_data( 'posted_attributes', $posted_attributes );
		}

		if ( Config::requires_double_opt_in() ) {
			$notification->set_status( NotificationStatus::PENDING );
		}

		$saved = $notification->save();
		if ( ! $saved ) {
			return new \WP_Error( self::ERROR_FAILED );
		}

		/**
		 * Action: woocommerce_customer_stock_notifications_signup
		 *
		 * @since 10.2.0
		 *
		 * @param Notification $notification The notification.
		 */
		do_action( 'woocommerce_customer_stock_notifications_signup', $notification );

		$signup_code = self::SIGNUP_SUCCESS;
		if ( Config::requires_double_opt_in() ) {
			$signup_code = $account_created
				? self::SIGNUP_SUCCESS_ACCOUNT_CREATED_DOUBLE_OPT_IN
				: self::SIGNUP_SUCCESS_DOUBLE_OPT_IN;
		} elseif ( $account_created ) {
			$signup_code = self::SIGNUP_SUCCESS_ACCOUNT_CREATED;
		}
		return new SignupResult( $signup_code, $notification );
	}

	/**
	 * Get the active notification for the request data.
	 *
	 * @param int    $product_id The product ID.
	 * @param int    $user_id The user ID.
	 * @param string $user_email The user email.
	 * @param array  $posted_attributes The posted attributes (Optional).
	 * @return Notification|null The notification, or null if it doesn't exist.
	 */
	public function is_already_signed_up( int $product_id, int $user_id, string $user_email, array $posted_attributes = array() ) {

		if ( empty( $product_id ) ) {
			return null;
		}

		if ( empty( $user_id ) && empty( $user_email ) ) {
			return null;
		}

		$found = false;
		if ( ! empty( $user_id ) ) {
			$found = NotificationQuery::notification_exists_by_user_id( $product_id, $user_id );
		} else {
			$found = NotificationQuery::notification_exists_by_email( $product_id, $user_email );
		}

		if ( ! $found ) {
			return null;
		}

		$query_args = array( 'product_id' => $product_id );
		if ( ! empty( $user_id ) ) {
			$query_args['user_id'] = $user_id;
		} else {
			$query_args['user_email'] = $user_email;
		}

		$query_args['return'] = 'ids';
		$query_args['limit']  = 1;
		if ( ! empty( $posted_attributes ) ) {
			// Hint: We need to compare the posted attributes with the stored attributes to handle variations with "any" attributes.
			$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'posted_attributes',
					'value'   => maybe_serialize( $posted_attributes ),
					'compare' => '=',
				),
			);
		}

		$ids = NotificationQuery::get_notifications( $query_args );
		if ( empty( $ids ) || ! is_numeric( $ids[0] ) ) {
			return null;
		}

		$notification = Factory::get_notification( $ids[0] );
		if ( ! $notification ) {
			return null;
		}

		return $notification;
	}

	/**
	 * Create a new customer.
	 *
	 * @param string $user_email The user email.
	 * @return int|null The user ID if the customer was created, null otherwise.
	 */
	private function create_customer( string $user_email ) {

		if ( empty( $user_email ) || ! is_email( $user_email ) ) {
			return null;
		}

		try {
			$username = wc_create_new_customer_username( $user_email );
			$username = sanitize_user( $username );
			if ( empty( $username ) || ! validate_username( $username ) ) {
				return null;
			}

			$password = 'yes' === get_option( 'woocommerce_registration_generate_password' ) ? '' : wp_generate_password();
			$user_id  = wc_create_new_customer( $user_email, $username, $password );
			if ( is_a( $user_id, 'WP_Error' ) ) {
				return null;
			}
		} catch ( \Throwable $e ) {
			return null;
		}

		return $user_id;
	}

	/**
	 * Parse the request data from a given source.
	 *
	 * @param array $source The source data, e.g. $_POST or $_REQUEST.
	 * @return array|\WP_Error {
	 *  The parsed request data, or a WP_Error if the request data is invalid.
	 *
	 *  @type int    $product_id The product ID.
	 *  @type int    $user_id The user ID.
	 *  @type string $user_email The user email.
	 *  @type array  $posted_attributes The posted attributes (Optional).
	 * }
	 */
	public function parse( array $source ) {

		$parsed_data = $this->parse_user_data( $source );
		if ( \is_wp_error( $parsed_data ) ) {
			return $parsed_data;
		}

		$product = $this->parse_product( $source );
		if ( \is_wp_error( $product ) ) {
			return $product;
		}

		$parsed_data['product_id'] = $product->get_id();
		if ( $product instanceof \WC_Product_Variation ) {
			$posted_attributes = $this->parse_posted_attributes( $source, $product );

			if ( ! empty( $posted_attributes ) ) {
				$parsed_data['posted_attributes'] = $posted_attributes;
			}
		}

		return $parsed_data;
	}

	/**
	 * Parse the user data from the source data.
	 *
	 * @param array $source The source data, e.g. $_POST or $_REQUEST.
	 * @return array|\WP_Error The parsed user data, or a WP_Error if the user data is invalid.
	 */
	private function parse_user_data( array $source ) {
		$data = array();

		$is_logged_in = \is_user_logged_in();
		if ( ! $is_logged_in && Config::requires_account() ) {
			return new \WP_Error( self::ERROR_REQUIRES_ACCOUNT );
		}

		// Check for valid privacy terms.
		if ( ! $is_logged_in && Config::creates_account_on_signup() && ! Config::requires_account() ) {
			$opt_in = isset( $source['wc_bis_opt_in'] ) ? wc_clean( wp_unslash( $source['wc_bis_opt_in'] ) ) : false;
			if ( 'on' !== $opt_in ) {
				return new \WP_Error( self::ERROR_INVALID_OPT_IN );
			}
		}

		if ( ! $is_logged_in ) {
			$email = isset( $source['wc_bis_email'] ) ? sanitize_email( wp_unslash( $source['wc_bis_email'] ) ) : false;
			if ( ! $email ) {
				return new \WP_Error( self::ERROR_INVALID_EMAIL );
			}

			if ( ! is_email( $email ) ) {
				return new \WP_Error( self::ERROR_INVALID_EMAIL );
			}

			$data['user_id']    = 0;
			$data['user_email'] = $email;

			// Check if user exists with this email.
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				$data['user_id'] = $user->ID;
			}
		} else {
			$user = wp_get_current_user();
			if ( ! $user ) {
				return new \WP_Error( self::ERROR_INVALID_USER );
			}

			$data['user_id']    = $user->ID;
			$data['user_email'] = $user->user_email;
		}

		return $data;
	}

	/**
	 * Parse the product from the source data.
	 *
	 * @param array $source The source data, e.g. $_POST or $_REQUEST.
	 * @return \WC_Product|\WP_Error The product, or a WP_Error if the product is invalid.
	 */
	private function parse_product( array $source ) {
		$product_id = isset( $source['wc_bis_product_id'] ) ? absint( wp_unslash( $source['wc_bis_product_id'] ) ) : false;
		if ( ! $product_id ) {
			return new \WP_Error( self::ERROR_INVALID_PRODUCT );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return new \WP_Error( self::ERROR_INVALID_PRODUCT );
		}

		if ( ! $this->eligibility_service->is_product_eligible( $product ) ) {
			return new \WP_Error( self::ERROR_INVALID_PRODUCT );
		}

		if ( ! $this->eligibility_service->product_allows_signups( $product ) ) {
			return new \WP_Error( self::ERROR_INVALID_PRODUCT );
		}

		return $product;
	}

	/**
	 * Parse variation attributes from source data.
	 *
	 * This method extracts attributes that are defined as 'any' in the variation and need to be
	 * explicitly specified during signup. These attributes cannot be retrieved directly from the variation
	 * since they are not fixed values.
	 *
	 * For example, if a t-shirt variation has 'any' size but a specific color, we need to capture
	 * the chosen size from the form submission while the color comes from the variation itself.
	 *
	 * @see \WC_Cart::add_to_cart() for similar attribute parsing logic.
	 *
	 * @param array       $source The source data, e.g. $_POST or $_REQUEST.
	 * @param \WC_Product $variation The variation.
	 * @return array The posted attributes.
	 */
	private function parse_posted_attributes( array $source, \WC_Product $variation ): array {

		if ( ! $variation instanceof \WC_Product_Variation ) {
			return array();
		}

		$product = wc_get_product( $variation->get_parent_id() );
		if ( ! $product ) {
			return array();
		}

		$posted_attributes = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute['is_variation'] ) {
				continue;
			}

			$attribute_key = 'attribute_' . sanitize_title( $attribute['name'] );
			if ( isset( $source[ $attribute_key ] ) ) {
				if ( $attribute['is_taxonomy'] ) {
					$value = sanitize_title( wp_unslash( $source[ $attribute_key ] ) );
				} else {
					$value = html_entity_decode( wc_clean( wp_unslash( $source[ $attribute_key ] ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
				}

				// Don't include if it's empty.
				if ( ! empty( $value ) || '0' === $value ) {
					$posted_attributes[ $attribute_key ] = $value;
				}
			}
		}

		$variation_attributes = $variation->get_variation_attributes();
		// Filter out 'any' variations, which are empty.
		$variation_attributes = array_filter( $variation_attributes );
		$diff                 = array_diff( $posted_attributes, $variation_attributes );

		// Return the posted attributes only if a variation with `any` attribute is detected.
		return ! empty( $diff ) ? $diff : array();
	}

	/**
	 * Get the error message for the error code.
	 *
	 * @param string $error_code The error code.
	 * @return string The error message.
	 */
	public function get_error_message( string $error_code ): string {
		switch ( $error_code ) {
			case self::ERROR_INVALID_PRODUCT:
				return wp_kses_post( __( 'Invalid product.', 'woocommerce' ) );
			case self::ERROR_INVALID_USER:
				return wp_kses_post( __( 'Invalid user.', 'woocommerce' ) );
			case self::ERROR_INVALID_EMAIL:
				return wp_kses_post( __( 'Invalid email address.', 'woocommerce' ) );
			case self::ERROR_INVALID_OPT_IN:
				return wp_kses_post( __( 'To proceed, please consent to the creation of a new account with your e-mail.', 'woocommerce' ) );
			case self::ERROR_RATE_LIMITED:
				return wp_kses_post( __( 'You have already signed up too many times. Please try again later.', 'woocommerce' ) );
			default:
				return wp_kses_post( __( 'Failed to sign up. Please try again.', 'woocommerce' ) );
		}
	}

	/**
	 * Get the signup user message for the signup code.
	 *
	 * @param string       $signup_code The signup code.
	 * @param Notification $notification The notification.
	 * @return string The signup user message.
	 */
	public function get_signup_user_message( string $signup_code, Notification $notification ): string {
		$message           = '';
		$has_action_button = false;
		switch ( $signup_code ) {

			case self::SIGNUP_SUCCESS:
				/* translators: Product name */
				$message = sprintf( esc_html__( 'You have successfully signed up! You will be notified when "%s" is back in stock.', 'woocommerce' ), $notification->get_product_name() );
				break;

			case self::SIGNUP_SUCCESS_DOUBLE_OPT_IN:
				$message = esc_html__( 'Thanks for signing up! Please complete the sign-up process by following the verification link sent to your e-mail.', 'woocommerce' );
				break;

			case self::SIGNUP_SUCCESS_ACCOUNT_CREATED:
				/* translators: Product name */
				$message = sprintf( esc_html__( 'You have successfully signed up and will be notified when "%s" is back in stock! Note that a new account has been created for you; please check your e-mail for details.', 'woocommerce' ), $notification->get_product_name() );
				break;

			case self::SIGNUP_SUCCESS_ACCOUNT_CREATED_DOUBLE_OPT_IN:
				$message = esc_html__( 'Thanks for signing up! An account has been created for you. Please complete the sign-up process by following the verification link sent to your e-mail.', 'woocommerce' );
				break;

			case self::SIGNUP_ALREADY_JOINED:
				$message = esc_html__( 'You have already joined this waitlist.', 'woocommerce' );
				break;

			case self::SIGNUP_ALREADY_JOINED_DOUBLE_OPT_IN:
				$notice_text     = esc_html__( 'You have already joined this waitlist. Please complete the sign-up process by following the verification link sent to your e-mail.', 'woocommerce' );
				$url             = $this->notification_management_service->get_resend_verification_email_url( $notification );
				$button_class    = wc_wp_theme_get_element_class_name( 'button' );
				$wp_button_class = $button_class ? ' ' . $button_class : '';
				$message         = sprintf(
					'<a href="%s" class="button wc-forward%s">%s</a> %s',
					$url,
					$wp_button_class,
					esc_html_x( 'Resend verification', 'notice action', 'woocommerce' ),
					$notice_text
				);

				$has_action_button = true;
				break;
			default:
				$message = '';
				break;
		}

		if ( is_user_logged_in() && ! $has_action_button ) {
			$button_class    = \wc_wp_theme_get_element_class_name( 'button' );
			$wp_button_class = $button_class ? ' ' . $button_class : '';
			$message         = sprintf( '<a href="%s" class="button wc-forward%s">%s</a> %s', \wc_get_account_endpoint_url( 'stock-notifications' ), $wp_button_class, esc_html_x( 'Manage notifications', 'notice action', 'woocommerce' ), $message );
		}

		return $message;
	}
}
