<?php
/**
 * WooPaymentsOperationalQueueService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Throwable;
use WC_Order;

/**
 * Native owner for WooPayments-compatible operational queue hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsOperationalQueueService implements RegisterHooksInterface {

	/**
	 * Preserved store setup sync hook.
	 *
	 * @var string
	 */
	const STORE_SETUP_SYNC_ACTION = 'wcpay_store_setup_sync';

	/**
	 * Preserved saved-payment-method update hook.
	 *
	 * @var string
	 */
	const UPDATE_SAVED_PAYMENT_METHOD_ACTION = 'wcpay_update_saved_payment_method';

	/**
	 * Preserved fee-breakdown order note hook.
	 *
	 * @var string
	 */
	const ADD_FEE_BREAKDOWN_TO_ORDER_NOTES_ACTION = 'wcpay_add_fee_breakdown_to_order_notes';

	/**
	 * Preserved compatibility-data update hook.
	 *
	 * @var string
	 */
	const UPDATE_COMPATIBILITY_DATA_ACTION = 'wcpay_update_compatibility_data';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Action Scheduler service.
	 *
	 * @var WooPaymentsActionSchedulerService
	 */
	private WooPaymentsActionSchedulerService $scheduler;

	/**
	 * WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * WooPayments order data service.
	 *
	 * @var WooPaymentsOrderDataService
	 */
	private WooPaymentsOrderDataService $order_data_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter      $arbiter            Runtime owner arbiter.
	 * @param WooPaymentsActionSchedulerService $scheduler          Action Scheduler service.
	 * @param WooPaymentsApiClient              $api_client         WooPayments API client.
	 * @param WooPaymentsAccountService         $account_service    WooPayments account service.
	 * @param WooPaymentsOrderDataService       $order_data_service WooPayments order data service.
	 */
	final public function init(
		NativePaymentsRuntimeArbiter $arbiter,
		WooPaymentsActionSchedulerService $scheduler,
		WooPaymentsApiClient $api_client,
		WooPaymentsAccountService $account_service,
		WooPaymentsOrderDataService $order_data_service
	): void {
		$this->arbiter            = $arbiter;
		$this->scheduler          = $scheduler;
		$this->api_client         = $api_client;
		$this->account_service    = $account_service;
		$this->order_data_service = $order_data_service;
	}

	/**
	 * Register preserved operational queue producers and consumers.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		add_action( self::STORE_SETUP_SYNC_ACTION, array( $this, 'handle_wcpay_store_setup_sync' ) );
		add_action( 'woocommerce_woocommerce_payments_updated', array( $this, 'handle_wcpay_store_setup_sync' ) );
		add_action( self::UPDATE_SAVED_PAYMENT_METHOD_ACTION, array( $this, 'handle_wcpay_update_saved_payment_method' ), 10, 3 );
		add_action( self::ADD_FEE_BREAKDOWN_TO_ORDER_NOTES_ACTION, array( $this, 'handle_wcpay_add_fee_breakdown_to_order_notes' ), 10, 3 );
		add_action( self::UPDATE_COMPATIBILITY_DATA_ACTION, array( $this, 'handle_wcpay_update_compatibility_data' ), 10, 0 );
		add_action( 'woocommerce_payments_account_refreshed', array( $this, 'schedule_compatibility_data_update' ) );
		add_action( 'after_switch_theme', array( $this, 'schedule_compatibility_data_update' ) );
		add_action( 'action_scheduler_ensure_recurring_actions', array( $this, 'schedule_recurring_actions' ) );
	}

	/**
	 * Schedule WooPayments recurring operational actions.
	 *
	 * @internal
	 */
	public function schedule_recurring_actions(): void {
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		if ( as_has_scheduled_action( self::STORE_SETUP_SYNC_ACTION, null, WooPaymentsActionSchedulerService::GROUP_ID ) ) {
			return;
		}

		as_schedule_recurring_action(
			time() + wp_rand( 10, 60 ),
			6 * HOUR_IN_SECONDS,
			self::STORE_SETUP_SYNC_ACTION,
			array(),
			WooPaymentsActionSchedulerService::GROUP_ID,
			true
		);
	}

	/**
	 * Send the current store setup state to the WooPayments API.
	 *
	 * @internal
	 */
	public function handle_wcpay_store_setup_sync(): void {
		if ( ! $this->api_client->is_available() ) {
			return;
		}

		try {
			$this->api_client->send_store_setup( $this->get_store_setup_details() );
		} catch ( Throwable $exception ) {
			$this->log_exception( 'Failed to sync native WooPayments store setup state.', $exception );
		}
	}

	/**
	 * Schedule a debounced compatibility data update.
	 *
	 * @internal
	 */
	public function schedule_compatibility_data_update(): void {
		$this->scheduler->schedule_job( self::UPDATE_COMPATIBILITY_DATA_ACTION, array(), time() + 2 * MINUTE_IN_SECONDS );
	}

	/**
	 * Send the current compatibility data to the WooPayments API.
	 *
	 * @internal
	 */
	public function handle_wcpay_update_compatibility_data(): void {
		try {
			$this->api_client->update_compatibility_data( $this->get_compatibility_data() );
		} catch ( Throwable $exception ) {
			$this->log_exception( 'Failed to sync native WooPayments compatibility data.', $exception );
		}
	}

	/**
	 * Update a saved payment method with the order billing details.
	 *
	 * @internal
	 *
	 * @param string $payment_method Payment method ID.
	 * @param int    $order_id       Order ID.
	 * @param bool   $is_test_mode   Whether this queued job should run in test mode.
	 */
	public function handle_wcpay_update_saved_payment_method( $payment_method, $order_id, $is_test_mode = false ): void {
		$this->with_test_mode_context(
			(bool) $is_test_mode,
			function () use ( $payment_method, $order_id ): void {
				$order = wc_get_order( $order_id );
				if ( ! $order instanceof WC_Order || ! is_string( $payment_method ) || '' === $payment_method ) {
					return;
				}

				$billing_details = $this->order_data_service->get_billing_data_from_order( $order );
				if ( empty( $billing_details ) ) {
					return;
				}

				try {
					$this->api_client->update_payment_method(
						$payment_method,
						array(
							'billing_details' => $billing_details,
						)
					);
				} catch ( Throwable $exception ) {
					$this->log_exception( 'Failed to update native WooPayments saved payment method.', $exception );
				}
			}
		);
	}

	/**
	 * Add fee-breakdown details to an order note from the intent timeline.
	 *
	 * @internal
	 *
	 * @param int    $order_id     Order ID.
	 * @param string $intent_id    PaymentIntent ID.
	 * @param bool   $is_test_mode Whether this queued job should run in test mode.
	 */
	public function handle_wcpay_add_fee_breakdown_to_order_notes( $order_id, $intent_id, $is_test_mode = false ): void {
		$this->with_test_mode_context(
			(bool) $is_test_mode,
			function () use ( $order_id, $intent_id ): void {
				$order = wc_get_order( $order_id );
				if ( ! $order instanceof WC_Order || ! is_string( $intent_id ) || '' === $intent_id ) {
					return;
				}

				try {
					$events = $this->api_client->get_timeline( $intent_id );
				} catch ( Throwable $exception ) {
					$this->log_exception( 'Failed to read native WooPayments intent timeline.', $exception );
					return;
				}

				if ( ! isset( $events['data'] ) || ! is_array( $events['data'] ) ) {
					return;
				}

				foreach ( $events['data'] as $event ) {
					if ( is_array( $event ) && 'captured' === ( $event['type'] ?? null ) ) {
						if ( $this->order_data_service->add_fee_breakdown_note_from_timeline_event( $order, $event ) ) {
							$order->save();
						}
						return;
					}
				}
			}
		);
	}

	/**
	 * Build the WooPayments-compatible store setup snapshot.
	 *
	 * @return array<string,mixed>
	 */
	private function get_store_setup_details(): array {
		$settings                         = $this->get_gateway_settings();
		$payment_methods_available        = $this->get_payment_methods_available( $settings );
		$payment_methods_enabled          = $this->get_payment_methods_enabled( $settings, $payment_methods_available );
		$payment_methods_disabled         = array_values( array_diff( $payment_methods_available, $payment_methods_enabled ) );
		$provider_capabilities_enabled    = $this->map_payment_methods_to_capabilities( $payment_methods_enabled );
		$provider_capabilities_disabled   = $this->map_payment_methods_to_capabilities( $payment_methods_disabled );
		$provider_capabilities_available  = array_values( array_unique( array_merge( $provider_capabilities_enabled, $provider_capabilities_disabled ) ) );
		$express_checkout_payment_methods = $settings['express_checkout_in_payment_methods'] ?? false;
		$payment_request_locations        = $this->get_express_checkout_method_locations( $settings, 'payment_request' );
		$woopay_locations                 = $this->get_express_checkout_method_locations( $settings, 'woopay' );

		return array(
			'gateway'                                     => array(
				'enabled'              => $this->is_setting_enabled( $settings, 'enabled' ),
				'test_mode'            => $this->account_service->is_test_mode_enabled(),
				'test_mode_onboarding' => $this->account_service->is_test_mode_onboarding_enabled(),
			),
			'payment_methods'                             => array(
				'available'  => $payment_methods_available,
				'enabled'    => $payment_methods_enabled,
				'disabled'   => $payment_methods_disabled,
				'duplicates' => array(),
			),
			'provider_capabilities'                       => array(
				'available' => $provider_capabilities_available,
				'enabled'   => $provider_capabilities_enabled,
				'disabled'  => $provider_capabilities_disabled,
			),
			'express_checkout_in_payment_methods_enabled' => $express_checkout_payment_methods,
			'saved_cards_enabled'                         => $this->is_setting_enabled( $settings, 'saved_cards' ) || $this->is_setting_enabled( $settings, 'saved_cards_enabled' ),
			'manual_capture_enabled'                      => $this->is_setting_enabled( $settings, 'manual_capture' ),
			'debug_log_enabled'                           => $this->is_setting_enabled( $settings, 'enable_logging' ),
			'payment_request'                             => array(
				'enabled'              => ! empty( $payment_request_locations ),
				'enabled_locations'    => $payment_request_locations,
				'button_type'          => $settings['payment_request_button_type'] ?? '',
				'button_size'          => $settings['payment_request_button_size'] ?? '',
				'button_theme'         => $settings['payment_request_button_theme'] ?? '',
				'button_border_radius' => $settings['payment_request_button_border_radius'] ?? '',
			),
			'woopay'                                      => array(
				'enabled'                 => ! empty( $woopay_locations ) || $this->is_setting_enabled( $settings, 'platform_checkout' ) || $this->is_setting_enabled( $settings, 'woopay' ),
				'enabled_locations'       => $woopay_locations,
				'store_logo'              => $settings['platform_checkout_store_logo'] ?? '',
				'custom_message'          => $settings['platform_checkout_custom_message'] ?? '',
				'invalid_extension_found' => (bool) get_option( 'woopay_invalid_extension_found', false ),
			),
			'multi_currency_enabled'                      => false,
			'stripe_billing_enabled'                      => false,
			'plugin'                                      => array(
				'version'              => defined( 'WC_VERSION' ) ? explode( '-', WC_VERSION, 2 )[0] : '',
				'activation_timestamp' => get_option( 'wcpay_activation_timestamp', null ),
			),
			'wp_setup'                                    => array(
				'name'           => get_bloginfo( 'name' ),
				'url'            => home_url(),
				'active_theme'   => $this->get_store_theme_details(),
				'active_plugins' => $this->get_store_active_plugins(),
				'version'        => get_bloginfo( 'version' ),
				'locale'         => get_locale(),
			),
			'wc_setup'                                    => array(
				'version'                     => defined( 'WC_VERSION' ) ? explode( '-', WC_VERSION, 2 )[0] : '',
				'store_id'                    => ( class_exists( '\WC_Install' ) && defined( '\WC_Install::STORE_ID_OPTION' ) ) ? get_option( \WC_Install::STORE_ID_OPTION, null ) : null,
				'currency'                    => get_woocommerce_currency(),
				'tracking_enabled'            => class_exists( '\WC_Site_Tracking' ) ? \WC_Site_Tracking::is_tracking_enabled() : false,
				'registered_payment_gateways' => $this->get_store_registered_gateway_ids(),
				'enabled_payment_gateways'    => $this->get_store_enabled_gateway_ids(),
				'wc_subscriptions_active'     => $this->is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ),
				'wc_subscriptions_version'    => $this->get_plugin_version( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ),
			),
		);
	}

	/**
	 * Build WooPayments-compatible compatibility data.
	 *
	 * @return array<string,mixed>
	 */
	private function get_compatibility_data(): array {
		return array(
			'woopayments_version'    => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'woocommerce_version'    => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'woocommerce_permalinks' => get_option( 'woocommerce_permalinks', array() ),
			'woocommerce_shop'       => $this->get_permalink_for_page_id( 'shop' ),
			'woocommerce_cart'       => $this->get_permalink_for_page_id( 'cart' ),
			'woocommerce_checkout'   => $this->get_permalink_for_page_id( 'checkout' ),
			'blog_theme'             => get_stylesheet(),
			'active_plugins'         => get_option( 'active_plugins', array() ),
			'post_types_count'       => $this->get_post_types_count(),
		);
	}

	/**
	 * Apply a queued job's test-mode context for the duration of a callback.
	 *
	 * @param bool     $is_test_mode Whether the job should run in test mode.
	 * @param callable $callback     Callback to run.
	 */
	private function with_test_mode_context( bool $is_test_mode, callable $callback ): void {
		$apply_test_mode_context = static function () use ( $is_test_mode ): bool {
			return $is_test_mode;
		};

		add_filter( 'wcpay_test_mode', $apply_test_mode_context );
		try {
			$callback();
		} finally {
			remove_filter( 'wcpay_test_mode', $apply_test_mode_context );
		}
	}

	/**
	 * Get WooPayments gateway settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_gateway_settings(): array {
		$settings = get_option( 'woocommerce_' . OrderPaymentStore::GATEWAY_ID . '_settings', array() );

		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Tell whether a yes/no gateway setting is enabled.
	 *
	 * @param array<string,mixed> $settings Gateway settings.
	 * @param string              $key      Setting key.
	 * @return bool
	 */
	private function is_setting_enabled( array $settings, string $key ): bool {
		return isset( $settings[ $key ] ) && true === wc_string_to_bool( $settings[ $key ] );
	}

	/**
	 * Get available payment method IDs.
	 *
	 * @param array<string,mixed> $settings Gateway settings.
	 * @return string[]
	 */
	private function get_payment_methods_available( array $settings ): array {
		$available = $settings['upe_available_payment_methods'] ?? array();
		if ( ! is_array( $available ) || empty( $available ) ) {
			$available = $settings['upe_enabled_payment_method_ids'] ?? array( 'card' );
		}

		return $this->sanitize_string_list( $available );
	}

	/**
	 * Get enabled payment method IDs.
	 *
	 * @param array<string,mixed> $settings  Gateway settings.
	 * @param string[]            $available Available payment method IDs.
	 * @return string[]
	 */
	private function get_payment_methods_enabled( array $settings, array $available ): array {
		$enabled = $settings['upe_enabled_payment_method_ids'] ?? array();
		if ( ! is_array( $enabled ) || empty( $enabled ) ) {
			$enabled = in_array( 'card', $available, true ) ? array( 'card' ) : array();
		}

		return $this->sanitize_string_list( $enabled );
	}

	/**
	 * Map WooPayments payment method IDs to Transact Platform capability keys.
	 *
	 * @param string[] $payment_method_ids Payment method IDs.
	 * @return string[]
	 */
	private function map_payment_methods_to_capabilities( array $payment_method_ids ): array {
		$map          = array(
			'alipay'            => 'alipay_payments',
			'amazon_pay'        => 'amazon_pay_payments',
			'apple_pay'         => 'card_payments',
			'au_becs_debit'     => 'au_becs_debit_payments',
			'bancontact'        => 'bancontact_payments',
			'card'              => 'card_payments',
			'eps'               => 'eps_payments',
			'giropay'           => 'giropay_payments',
			'google_pay'        => 'card_payments',
			'grabpay'           => 'grabpay_payments',
			'ideal'             => 'ideal_payments',
			'jcb'               => 'jcb_payments',
			'klarna'            => 'klarna_payments',
			'link'              => 'link_payments',
			'multibanco'        => 'multibanco_payments',
			'p24'               => 'p24_payments',
			'sepa_debit'        => 'sepa_debit_payments',
			'sofort'            => 'sofort_payments',
			'wechat_pay'        => 'wechat_pay_payments',
			'affirm'            => 'affirm_payments',
			'afterpay_clearpay' => 'afterpay_clearpay_payments',
		);
		$capabilities = array();

		foreach ( $payment_method_ids as $payment_method_id ) {
			if ( isset( $map[ $payment_method_id ] ) ) {
				$capabilities[] = $map[ $payment_method_id ];
			}
		}

		return array_values( array_unique( $capabilities ) );
	}

	/**
	 * Get configured express-checkout button locations.
	 *
	 * @param array<string,mixed> $settings Gateway settings.
	 * @param string              $method   Express checkout method ID.
	 * @return string[]
	 */
	private function get_express_checkout_method_locations( array $settings, string $method ): array {
		$enabled_locations = array();

		foreach ( array( 'product', 'cart', 'checkout' ) as $location ) {
			$enabled_methods = $settings[ 'express_checkout_' . $location . '_methods' ] ?? array();
			if ( is_array( $enabled_methods ) && in_array( $method, $enabled_methods, true ) ) {
				$enabled_locations[] = $location;
			}
		}

		return $enabled_locations;
	}

	/**
	 * Sanitize a string list.
	 *
	 * @param mixed $values Raw values.
	 * @return string[]
	 */
	private function sanitize_string_list( $values ): array {
		if ( ! is_array( $values ) ) {
			return array();
		}

		$strings = array();
		foreach ( $values as $value ) {
			if ( is_string( $value ) && '' !== $value ) {
				$strings[] = $value;
			}
		}

		return array_values( array_unique( $strings ) );
	}

	/**
	 * Get active theme details.
	 *
	 * @return array<string,mixed>
	 */
	private function get_store_theme_details(): array {
		$theme_data = wp_get_theme();

		return array(
			'name'        => $theme_data->get( 'Name' ),
			'version'     => $theme_data->get( 'Version' ),
			'child_theme' => is_child_theme(),
			'wc_support'  => current_theme_supports( 'woocommerce' ),
			'block_theme' => wp_is_block_theme(),
		);
	}

	/**
	 * Get active plugin details.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_store_active_plugins(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		if ( empty( $all_plugins ) ) {
			return array();
		}

		$plugins_list      = array();
		$active_plugin_ids = wp_get_active_and_valid_plugins();
		foreach ( $active_plugin_ids as $plugin_file ) {
			$plugin_file = plugin_basename( $plugin_file );
			if ( isset( $all_plugins[ $plugin_file ] ) ) {
				$plugin_data                  = $all_plugins[ $plugin_file ];
				$plugins_list[ $plugin_file ] = array(
					'name'     => $plugin_data['Name'],
					'slug'     => dirname( $plugin_file ),
					'version'  => $plugin_data['Version'],
					'wc_aware' => null,
				);
			}
		}

		return array_values( $plugins_list );
	}

	/**
	 * Get registered gateway IDs.
	 *
	 * @return string[]
	 */
	private function get_store_registered_gateway_ids(): array {
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		if ( empty( $payment_gateways ) ) {
			return array();
		}

		return array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( $gateway ): ?string => isset( $gateway->id ) && is_string( $gateway->id ) ? $gateway->id : null,
						$payment_gateways
					)
				)
			)
		);
	}

	/**
	 * Get enabled gateway IDs.
	 *
	 * @return string[]
	 */
	private function get_store_enabled_gateway_ids(): array {
		$enabled_gateways = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( isset( $gateway->id, $gateway->enabled ) && is_string( $gateway->id ) && 'yes' === $gateway->enabled ) {
				$enabled_gateways[] = $gateway->id;
			}
		}

		return array_values( array_unique( $enabled_gateways ) );
	}

	/**
	 * Get public post type publish counts.
	 *
	 * @return array<string,int>
	 */
	private function get_post_types_count(): array {
		$post_types_count = array();
		foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
			$post_types_count[ $post_type ] = (int) wp_count_posts( $post_type )->publish;
		}

		return $post_types_count;
	}

	/**
	 * Gets the permalink for a WooCommerce page ID.
	 *
	 * @param string $page_id Page ID key.
	 * @return string
	 */
	private function get_permalink_for_page_id( string $page_id ): string {
		$permalink = get_permalink( wc_get_page_id( $page_id ) );

		return $permalink ? $permalink : 'Not set';
	}

	/**
	 * Tell whether a plugin file is active.
	 *
	 * @param string $plugin_file Plugin basename.
	 * @return bool
	 */
	private function is_plugin_active( string $plugin_file ): bool {
		$active_plugins = get_option( 'active_plugins', array() );

		return is_array( $active_plugins ) && in_array( $plugin_file, $active_plugins, true );
	}

	/**
	 * Get a plugin version from the installed plugins list.
	 *
	 * @param string $plugin_file Plugin basename.
	 * @return string
	 */
	private function get_plugin_version( string $plugin_file ): string {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		return isset( $plugins[ $plugin_file ]['Version'] ) ? (string) $plugins[ $plugin_file ]['Version'] : '';
	}

	/**
	 * Log an operational queue exception without fataling the request.
	 *
	 * @param string    $message   Log message.
	 * @param Throwable $exception Exception thrown.
	 */
	private function log_exception( string $message, Throwable $exception ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->error(
			$message . ' ' . $exception->getMessage(),
			array( 'source' => 'woopayments' )
		);
	}
}
