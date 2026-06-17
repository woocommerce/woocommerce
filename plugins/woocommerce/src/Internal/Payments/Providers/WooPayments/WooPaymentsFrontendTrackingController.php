<?php
/**
 * WooPaymentsFrontendTrackingController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Throwable;
use WC_Tracks;
use WC_Tracks_Client;
use WC_Tracks_Event;
use WP_Error;
use WP_User;

/**
 * Native WooPayments shopper frontend Tracks callbacks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsFrontendTrackingController implements RegisterHooksInterface {

	private const USER_EVENT_PREFIX = 'wcpay';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter         Runtime owner arbiter.
	 * @param WooPaymentsAccountService    $account_service WooPayments account service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsAccountService $account_service ): void {
		$this->arbiter         = $arbiter;
		$this->account_service = $account_service;
	}

	/**
	 * Register frontend tracking AJAX hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		foreach ( $this->get_ajax_hooks() as $hook => $callback ) {
			if ( false === has_action( $hook, $callback ) ) {
				add_action( $hook, $callback );
			}
		}
	}

	/**
	 * Handle the preserved platform Tracks AJAX action.
	 */
	public function handle_tracks(): void {
		$response = $this->get_tracks_response( wp_unslash( $_REQUEST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $response['success'] ) {
			wp_send_json_success( $response['data'], $response['status_code'] );
		}

		wp_send_json_error( $response['data'], $response['status_code'] );
	}

	/**
	 * Handle the preserved Tracks identity AJAX action.
	 */
	public function handle_tracks_identity(): void {
		$response = $this->get_tracks_identity_response();

		wp_send_json_success( $response['data'], $response['status_code'] );
	}

	/**
	 * Build the platform Tracks response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array{success:bool,status_code:int,data:mixed}
	 */
	public function get_tracks_response( array $request ): array {
		if (
			empty( $request['tracksNonce'] )
			|| ! is_scalar( $request['tracksNonce'] )
			|| ! wp_verify_nonce( (string) $request['tracksNonce'], 'platform_tracks_nonce' )
		) {
			return $this->error_response( __( 'You aren’t authorized to do that.', 'woocommerce' ), 403 );
		}

		if ( empty( $request['tracksEventName'] ) || ! is_scalar( $request['tracksEventName'] ) ) {
			return $this->error_response( __( 'No valid event name or type.', 'woocommerce' ), 403 );
		}

		$properties = array();
		if ( isset( $request['tracksEventProp'] ) && is_scalar( $request['tracksEventProp'] ) ) {
			$encoded_properties = wc_clean( (string) $request['tracksEventProp'] );
			if ( is_string( $encoded_properties ) ) {
				$decoded = json_decode( $encoded_properties, true );
				if ( is_array( $decoded ) ) {
					$properties = $decoded;
				}
			}
		}

		$this->record_user_event( sanitize_text_field( (string) $request['tracksEventName'] ), $properties );

		return array(
			'success'     => true,
			'status_code' => 200,
			'data'        => array(),
		);
	}

	/**
	 * Build the Tracks identity response.
	 *
	 * @return array{success:bool,status_code:int,data:array<string,mixed>}
	 */
	public function get_tracks_identity_response(): array {
		return array(
			'success'     => true,
			'status_code' => 200,
			'data'        => $this->get_tracks_identity( get_current_user_id() ),
		);
	}

	/**
	 * Tell whether shopper tracking is enabled for native WooPayments.
	 *
	 * @param bool $is_admin_event      Whether the event is emitted from the admin area.
	 * @param bool $track_on_all_stores Whether the event should ignore WooPay eligibility.
	 * @return bool
	 */
	public function is_shopper_tracking_enabled( bool $is_admin_event = false, bool $track_on_all_stores = false ): bool {
		/**
		 * Filters whether WooPayments shopper tracking is enabled.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $is_enabled Whether shopper tracking is enabled.
		 */
		if ( ! apply_filters( 'wcpay_shopper_tracking_enabled', 'no' !== get_option( 'woocommerce_allow_tracking', '' ) ) ) {
			return false;
		}

		if ( ! $this->get_account_service()->can_process_payments() ) {
			return false;
		}

		if ( ! $this->is_country_tracks_eligible() ) {
			return false;
		}

		if ( ! empty( $_COOKIE['tk_opt-out'] ) ) {
			return false;
		}

		if ( $is_admin_event ) {
			return true;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		$user = wp_get_current_user();
		if ( $user instanceof WP_User && is_user_logged_in() && in_array( 'administrator', $user->roles, true ) ) {
			return false;
		}

		if ( $track_on_all_stores ) {
			return true;
		}

		$account_data = $this->get_account_service()->get_cached_account_data();

		return 'yes' === $this->get_account_service()->get_gateway_setting( 'platform_checkout', 'no' )
			&& ! empty( $account_data['platform_checkout_eligible'] );
	}

	/**
	 * Record a WooPayments shopper event.
	 *
	 * @param string              $event_name Event name without the wcpay_ prefix.
	 * @param array<string,mixed> $properties Event properties.
	 * @return bool|\WP_Error
	 */
	public function record_user_event( string $event_name, array $properties = array() ) {
		if ( '_aliasUser' !== $event_name ) {
			$event_name = self::USER_EVENT_PREFIX . '_' . $event_name;
		}

		/**
		 * Filters WooPayments Tracks event properties.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,mixed> $properties Event properties.
		 * @param string              $event_name Event name.
		 */
		$properties = apply_filters( 'wcpay_tracks_event_properties', $properties, $event_name );

		$is_admin_event      = false;
		$track_on_all_stores = false;
		if ( isset( $properties['record_event_data'] ) && is_array( $properties['record_event_data'] ) ) {
			$is_admin_event      = ! empty( $properties['record_event_data']['is_admin_event'] );
			$track_on_all_stores = ! empty( $properties['record_event_data']['track_on_all_stores'] );
			unset( $properties['record_event_data'] );
		}

		if ( ! $this->is_shopper_tracking_enabled( $is_admin_event, $track_on_all_stores ) ) {
			return false;
		}

		$user = wp_get_current_user();
		if ( $user instanceof WP_User && 'wptests_capabilities' === $user->cap_key ) {
			return false;
		}

		$event = new WC_Tracks_Event( $this->build_event_properties( $event_name, $properties, $user ) );

		if ( is_wp_error( $event->error ) ) {
			return $event->error;
		}

		$pixel = $event->build_pixel_url();
		if ( ! $pixel ) {
			return new WP_Error( 'invalid_pixel', 'cannot generate tracks pixel for given input', 400 );
		}

		return WC_Tracks_Client::record_pixel( $pixel );
	}

	/**
	 * Get AJAX hooks and callbacks.
	 *
	 * @return array<string,callable>
	 */
	private function get_ajax_hooks(): array {
		return array(
			'wp_ajax_platform_tracks'        => array( $this, 'handle_tracks' ),
			'wp_ajax_nopriv_platform_tracks' => array( $this, 'handle_tracks' ),
			'wp_ajax_get_identity'           => array( $this, 'handle_tracks_identity' ),
			'wp_ajax_nopriv_get_identity'    => array( $this, 'handle_tracks_identity' ),
		);
	}

	/**
	 * Build an error response.
	 *
	 * @param string $message Error message.
	 * @param int    $status_code HTTP status code.
	 * @return array{success:bool,status_code:int,data:string}
	 */
	private function error_response( string $message, int $status_code ): array {
		return array(
			'success'     => false,
			'status_code' => $status_code,
			'data'        => $message,
		);
	}

	/**
	 * Build Tracks event properties.
	 *
	 * @param string              $event_name Event name.
	 * @param array<string,mixed> $properties Event properties.
	 * @param WP_User             $user       Current user.
	 * @return array<string,mixed>
	 */
	private function build_event_properties( string $event_name, array $properties, WP_User $user ): array {
		$blog_details = WC_Tracks::get_blog_details( $user->ID );

		$event = array_merge(
			$properties,
			WC_Tracks::get_server_details(),
			$this->get_tracks_identity( $user->ID ),
			$blog_details,
			array(
				'_en'           => $event_name,
				'_ts'           => WC_Tracks_Client::build_timestamp(),
				'blog_url'      => get_option( 'siteurl' ),
				'user_lang'     => $user->get( 'WPLANG' ),
				'test_mode'     => $this->get_account_service()->is_test_mode_enabled() ? 1 : 0,
				'wcpay_version' => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			)
		);

		foreach ( $event as $key => $value ) {
			if ( null === $value ) {
				unset( $event[ $key ] );
			}
		}

		return $event;
	}

	/**
	 * Get the WooPayments-compatible Tracks identity.
	 *
	 * @param int $user_id User ID.
	 * @return array{_ut:string,_ui:string}
	 */
	private function get_tracks_identity( int $user_id ): array {
		$wpcom_id = get_user_meta( $user_id, 'jetpack_tracks_wpcom_id', true );
		if ( is_string( $wpcom_id ) && '' !== $wpcom_id && $this->is_user_connected( $user_id ) ) {
			return array(
				'_ut' => 'wpcom:user_id',
				'_ui' => $wpcom_id,
			);
		}

		if ( $this->is_user_connected( $user_id ) ) {
			$wpcom_user_data = $this->get_connected_user_data( $user_id );
			$wpcom_id        = is_array( $wpcom_user_data ) ? ( $wpcom_user_data['ID'] ?? null ) : null;
			if ( is_string( $wpcom_id ) && '' !== $wpcom_id ) {
				update_user_meta( $user_id, 'jetpack_tracks_wpcom_id', $wpcom_id );

				return array(
					'_ut' => 'wpcom:user_id',
					'_ui' => $wpcom_id,
				);
			}
		}

		$anon_id = get_user_meta( $user_id, 'jetpack_tracks_anon_id', true );
		if ( ! is_string( $anon_id ) || '' === $anon_id ) {
			$anon_id = $this->generate_tracks_anon_id();
			add_user_meta( $user_id, 'jetpack_tracks_anon_id', $anon_id, false );
		}

		return array(
			'_ut' => 'anon',
			'_ui' => $anon_id,
		);
	}

	/**
	 * Tell whether a user has a connected WPCOM identity.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function is_user_connected( int $user_id ): bool {
		if ( ! class_exists( JetpackConnectionManager::class ) ) {
			return false;
		}

		try {
			$manager = new JetpackConnectionManager( 'woocommerce' );

			return (bool) $manager->is_user_connected( $user_id );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get connected WPCOM user data.
	 *
	 * @param int $user_id User ID.
	 * @return array<string,mixed>|null
	 */
	private function get_connected_user_data( int $user_id ): ?array {
		if ( ! class_exists( JetpackConnectionManager::class ) ) {
			return null;
		}

		try {
			$manager = new JetpackConnectionManager( 'woocommerce' );
			$data    = $manager->get_connected_user_data( $user_id );

			return is_array( $data ) ? $data : null;
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * Generate a Jetpack-compatible Tracks anonymous ID.
	 *
	 * @return string
	 */
	private function generate_tracks_anon_id(): string {
		if (
			class_exists( 'Jetpack_Tracks_Client' )
			&& is_callable( array( 'Jetpack_Tracks_Client', 'get_anon_id' ) )
		) {
			$anon_id = \Jetpack_Tracks_Client::get_anon_id();
			if ( is_string( $anon_id ) && '' !== $anon_id ) {
				return $anon_id;
			}
		}

		$binary = '';
		for ( $i = 0; $i < 18; ++$i ) {
			$binary .= chr( wp_rand( 0, 255 ) );
		}

		return 'jetpack:' . base64_encode( $binary ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Check whether the store country is eligible for WooPayments shopper Tracks.
	 *
	 * @return bool
	 */
	private function is_country_tracks_eligible(): bool {
		if ( ! function_exists( 'wc_get_base_location' ) ) {
			return false;
		}

		$base_location = wc_get_base_location();

		return 'US' === ( $base_location['country'] ?? '' );
	}

	/**
	 * Get the WooPayments account service.
	 *
	 * @return WooPaymentsAccountService
	 */
	private function get_account_service(): WooPaymentsAccountService {
		if ( ! isset( $this->account_service ) ) {
			$this->account_service = wc_get_container()->get( WooPaymentsAccountService::class );
		}

		return $this->account_service;
	}
}
