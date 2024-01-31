<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Integrations\WPConsentAPI;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Traits\ScriptDebug;
use Automattic\WooCommerce\Internal\Traits\OrderAttributionMeta;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Exception;
use WC_Customer;
use WC_Log_Levels;
use WC_Logger_Interface;
use WC_Order;
use WC_Tracks;

/**
 * Class OrderAttributionController
 *
 * @since 8.5.0
 */
class OrderAttributionController implements RegisterHooksInterface {

	use ScriptDebug;
	use OrderAttributionMeta {
		get_prefixed_field_name as public;
	}

	/**
	 * The WPConsentAPI integration instance.
	 *
	 * @var WPConsentAPI
	 */
	private $consent;

	/**
	 * The FeatureController instance.
	 *
	 * @var FeaturesController
	 */
	private $feature_controller;

	/**
	 * WooCommerce logger class instance.
	 *
	 * @var WC_Logger_Interface
	 */
	private $logger;

	/**
	 * The LegacyProxy instance.
	 *
	 * @var LegacyProxy
	 */
	private $proxy;

	/**
	 * Initialization method.
	 *
	 * Takes the place of the constructor within WooCommerce Dependency injection.
	 *
	 * @internal
	 *
	 * @param LegacyProxy         $proxy      The legacy proxy.
	 * @param FeaturesController  $controller The feature controller.
	 * @param WPConsentAPI        $consent    The WPConsentAPI integration.
	 * @param WC_Logger_Interface $logger     The logger object. If not provided, it will be obtained from the proxy.
	 */
	final public function init( LegacyProxy $proxy, FeaturesController $controller, WPConsentAPI $consent, ?WC_Logger_Interface $logger = null ) {
		$this->proxy              = $proxy;
		$this->feature_controller = $controller;
		$this->consent            = $consent;
		$this->logger             = $logger ?? $proxy->call_function( 'wc_get_logger' );
		$this->set_fields_and_prefix();
	}

	/**
	 * Register this class instance to the appropriate hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Don't run during install.
		if ( Constants::get_constant( 'WC_INSTALLING' ) ) {
			return;
		}

		// Bail if the feature is not enabled.
		if ( ! $this->feature_controller->feature_is_enabled( 'order_attribution' ) ) {
			return;
		}

		// Register WPConsentAPI integration.
		$this->consent->register();

		add_action(
			'wp_enqueue_scripts',
			function() {
				$this->enqueue_scripts_and_styles();
			}
		);

		add_action(
			'admin_enqueue_scripts',
			function() {
				$this->enqueue_admin_scripts_and_styles();
			}
		);

		// Include our hidden `<input>` elements on order notes and registration form.
		$source_form_elements = function() {
			$this->source_form_elements();
		};

		add_action( 'woocommerce_after_order_notes', $source_form_elements );
		add_action( 'woocommerce_register_form', $source_form_elements );

		// Update order based on submitted fields.
		add_action(
			'woocommerce_checkout_order_created',
			function( $order ) {
				// Nonce check is handled by WooCommerce before woocommerce_checkout_order_created hook.
				// phpcs:ignore WordPress.Security.NonceVerification
				$params = $this->get_unprefixed_field_values( $_POST );
				/**
				 * Run an action to save order attribution data.
				 *
				 * @since 8.5.0
				 *
				 * @param WC_Order $order The order object.
				 * @param array    $params Unprefixed order attribution data.
				 */
				do_action( 'woocommerce_order_save_attribution_data', $order, $params );
			}
		);

		add_action(
			'woocommerce_order_save_attribution_data',
			function( $order, $data ) {
				$source_data = $this->get_source_values( $data );
				$this->send_order_tracks( $source_data, $order );
				$this->set_order_source_data( $source_data, $order );
			},
			10,
			2
		);

		add_action(
			'user_register',
			function( $customer_id ) {
				try {
					$customer = new WC_Customer( $customer_id );
					$this->set_customer_source_data( $customer );
				} catch ( Exception $e ) {
					$this->log( $e->getMessage(), __METHOD__, WC_Log_Levels::ERROR );
				}
			}
		);

		// Add origin data to the order table.
		add_action(
			'admin_init',
			function() {
				$this->register_order_origin_column();
			}
		);

		add_action(
			'woocommerce_new_order',
			function( $order_id, $order ) {
				$this->maybe_set_admin_source( $order );
			},
			2,
			10
		);
	}

	/**
	 * If the order is created in the admin, set the source type and origin to admin/Web admin.
	 *
	 * @param WC_Order $order The recently created order object.
	 *
	 * @since 8.5.0
	 */
	private function maybe_set_admin_source( WC_Order $order ) {
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			$order->add_meta_data( $this->get_meta_prefixed_field_name( 'source_type' ), 'admin' );
			$order->save();
		}
	}

	/**
	 * Get all of the field names.
	 *
	 * @return array
	 */
	public function get_field_names(): array {
		return $this->field_names;
	}

	/**
	 * Get the prefix for the fields.
	 *
	 * @return string
	 */
	public function get_prefix(): string {
		return $this->field_prefix;
	}

	/**
	 * Scripts & styles for custom source tracking and cart tracking.
	 */
	private function enqueue_scripts_and_styles() {
		wp_enqueue_script(
			'sourcebuster-js',
			plugins_url( "assets/js/sourcebuster/sourcebuster{$this->get_script_suffix()}.js", WC_PLUGIN_FILE ),
			array(),
			Constants::get_constant( 'WC_VERSION' ),
			true
		);

		wp_enqueue_script(
			'wc-order-attribution',
			plugins_url( "assets/js/frontend/order-attribution{$this->get_script_suffix()}.js", WC_PLUGIN_FILE ),
			// Technically we do depend on 'wp-data', 'wc-blocks-checkout' for blocks checkout,
			// but as implementing conditional dependency on the server-side would be too complex,
			// we resolve this condition at the client-side.
			array( 'sourcebuster-js' ),
			Constants::get_constant( 'WC_VERSION' ),
			true
		);

		/**
		 * Filter the lifetime of the cookie used for source tracking.
		 *
		 * @since 8.5.0
		 *
		 * @param float $lifetime The lifetime of the Sourcebuster cookies in months.
		 *
		 * The default value forces Sourcebuster into making the cookies valid for the current session only.
		 */
		$lifetime = (float) apply_filters( 'wc_order_attribution_cookie_lifetime_months', 0.00001 );

		/**
		 * Filter the session length for source tracking.
		 *
		 * @since 8.5.0
		 *
		 * @param int $session_length The session length in minutes.
		 */
		$session_length = (int) apply_filters( 'wc_order_attribution_session_length_minutes', 30 );

		/**
		 * Filter to allow tracking.
		 *
		 * @since 8.5.0
		 *
		 * @param bool $allow_tracking True to allow tracking, false to disable.
		 */
		$allow_tracking = wc_bool_to_string( apply_filters( 'wc_order_attribution_allow_tracking', true ) );

		// Create Order Attribution JS namespace with parameters.
		$namespace = array(
			'params' => array(
				'lifetime'      => $lifetime,
				'session'       => $session_length,
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'prefix'        => $this->field_prefix,
				'allowTracking' => 'yes' === $allow_tracking,
			),
			'fields' => $this->fields,
		);

		wp_localize_script( 'wc-order-attribution', 'wc_order_attribution', $namespace );
	}

	/**
	 * Enqueue the stylesheet for admin pages.
	 *
	 * @return void
	 */
	private function enqueue_admin_scripts_and_styles() {
		$screen = get_current_screen();
		if ( $screen->id !== $this->get_order_screen_id() ) {
			return;
		}

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_enqueue_script(
			'woocommerce-order-attribution-admin-js',
			plugins_url( "assets/js/admin/order-attribution-admin{$this->get_script_suffix()}.js", WC_PLUGIN_FILE ),
			array( 'jquery' ),
			Constants::get_constant( 'WC_VERSION' )
		);
	}

	/**
	 * Display the origin column in the orders table.
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	private function display_origin_column( $order_id ): void {
		try {
			// Ensure we've got a valid order.
			$order = $this->get_hpos_order_object( $order_id );
			$this->output_origin_column( $order );
		} catch ( Exception $e ) {
			return;
		}
	}

	/**
	 * Output the translated origin label for the Origin column in the orders table.
	 *
	 * Default to "Unknown" if no origin is set.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	private function output_origin_column( WC_Order $order ) {
		$source_type = $order->get_meta( $this->get_meta_prefixed_field_name( 'source_type' ) );
		$source      = $order->get_meta( $this->get_meta_prefixed_field_name( 'utm_source' ) );
		$origin      = $this->get_origin_label( $source_type, $source );
		if ( empty( $origin ) ) {
			$origin = __( 'Unknown', 'woocommerce' );
		}
		echo esc_html( $origin );
	}

	/**
	 * Add `<input type="hidden">` elements for source fields.
	 * Used for checkout & customer register froms.
	 */
	private function source_form_elements() {
		foreach ( $this->field_names as $field_name ) {
			printf( '<input type="hidden" name="%s" value="" />', esc_attr( $this->get_prefixed_field_name( $field_name ) ) );
		}
	}

	/**
	 * Save source data for a Customer object.
	 *
	 * @param WC_Customer $customer The customer object.
	 *
	 * @return void
	 */
	private function set_customer_source_data( WC_Customer $customer ) {
		// Nonce check is handled before user_register hook.
		// phpcs:ignore WordPress.Security.NonceVerification
		foreach ( $this->get_source_values( $this->get_unprefixed_field_values( $_POST ) ) as $key => $value ) {
			$customer->add_meta_data( $this->get_meta_prefixed_field_name( $key ), $value );
		}

		$customer->save_meta_data();
	}

	/**
	 * Save source data for an Order object.
	 *
	 * @param array    $source_data The source data.
	 * @param WC_Order $order       The order object.
	 *
	 * @return void
	 */
	private function set_order_source_data( array $source_data, WC_Order $order ) {
		// If all the values are empty, bail.
		if ( empty( array_filter( $source_data ) ) ) {
			return;
		}
		foreach ( $source_data as $key => $value ) {
			$order->add_meta_data( $this->get_meta_prefixed_field_name( $key ), $value );
		}

		$order->save_meta_data();
	}

	/**
	 * Log a message as a debug log entry.
	 *
	 * @param string $message The message to log.
	 * @param string $method  The method that is logging the message.
	 * @param string $level   The log level.
	 */
	private function log( string $message, string $method, string $level = WC_Log_Levels::DEBUG ) {
		/**
		 * Filter to enable debug mode.
		 *
		 * @since 8.5.0
		 *
		 * @param string $enabled 'yes' to enable debug mode, 'no' to disable.
		 */
		if ( 'yes' !== apply_filters( 'wc_order_attribution_debug_mode_enabled', 'no' ) ) {
			return;
		}

		$this->logger->log(
			$level,
			sprintf( '%s %s', $method, $message ),
			array( 'source' => 'woocommerce-order-attribution' )
		);
	}

	/**
	 * Send order source data to Tracks.
	 *
	 * @param array    $source_data The source data.
	 * @param WC_Order $order       The order object.
	 *
	 * @return void
	 */
	private function send_order_tracks( array $source_data, WC_Order $order ) {
		$origin_label        = $this->get_origin_label(
			$source_data['source_type'] ?? '',
			$source_data['utm_source'] ?? '',
			false
		);
		$customer_identifier = $order->get_customer_id() ? $order->get_customer_id() : $order->get_billing_email();
		$customer_info       = $this->get_customer_history( $customer_identifier );
		$tracks_data         = array(
			'order_id'             => $order->get_id(),
			'source_type'          => $source_data['source_type'] ?? '',
			'medium'               => $source_data['utm_medium'] ?? '',
			'source'               => $source_data['utm_source'] ?? '',
			'device_type'          => strtolower( $source_data['device_type'] ?? 'unknown' ),
			'origin_label'         => strtolower( $origin_label ),
			'session_pages'        => $source_data['session_pages'] ?? 0,
			'session_count'        => $source_data['session_count'] ?? 0,
			'order_total'          => $order->get_total(),
			// Add 1 to include the current order (which is currently still Pending when the event is sent).
			'customer_order_count' => $customer_info['order_count'] + 1,
			'customer_registered'  => $order->get_customer_id() ? 'yes' : 'no',
		);
		$this->proxy->call_static( WC_Tracks::class, 'record_event', 'order_attribution', $tracks_data );
	}

	/**
	 * Get the screen ID for the orders page.
	 *
	 * @return string
	 */
	private function get_order_screen_id(): string {
		return OrderUtil::custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	}

	/**
	 * Register the origin column in the orders table.
	 *
	 * This accounts for the differences in hooks based on whether HPOS is enabled or not.
	 *
	 * @return void
	 */
	private function register_order_origin_column() {
		$screen_id = $this->get_order_screen_id();

		$add_column = function( $columns ) {
			$columns['origin'] = esc_html__( 'Origin', 'woocommerce' );

			return $columns;
		};
		// HPOS and non-HPOS use different hooks.
		add_filter( "manage_{$screen_id}_columns", $add_column );
		add_filter( "manage_edit-{$screen_id}_columns", $add_column );

		$display_column = function( $column_name, $order_id ) {
			if ( 'origin' !== $column_name ) {
				return;
			}
			$this->display_origin_column( $order_id );
		};
		// HPOS and non-HPOS use different hooks.
		add_action( "manage_{$screen_id}_custom_column", $display_column, 10, 2 );
		add_action( "manage_{$screen_id}_posts_custom_column", $display_column, 10, 2 );
	}
}
