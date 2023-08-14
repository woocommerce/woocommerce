<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Traits\SourceAttributionMeta;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WC_Customer;
use WC_Log_Levels;
use WC_Logger_Interface;
use WC_Order;
use WP_User;

/**
 * Class SourceAttributionController
 *
 * @since x.x.x
 */
class SourceAttributionController implements RegisterHooksInterface {

	use SourceAttributionMeta;

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
	 * @param LegacyProxy              $proxy  The legacy proxy.
	 * @param WC_Logger_Interface|null $logger The logger object. If not provided, it will be obtained from the proxy.
	 */
	final public function init( LegacyProxy $proxy, ?WC_Logger_Interface $logger = null ) {
		$this->proxy  = $proxy;
		$this->logger = $logger ?? $proxy->call_function( 'wc_get_logger' );
	}

	/**
	 * Register this class instance to the appropriate hooks.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register() {
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

		// Include our hidden fields on order notes and registration form.
		$source_form_fields = function() {
			$this->source_form_fields();
		};

		add_action( 'woocommerce_after_order_notes', $source_form_fields );
		add_action( 'woocommerce_register_form', $source_form_fields );

		// Update data based on submitted fields.
		add_action(
			'woocommerce_checkout_order_created',
			function( $order ) {
				$this->set_order_source_data( $order );
			}
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

		// Add output to the User display page.
		$customer_meta_boxes = function( WP_User $user ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			try {
				$customer = new WC_Customer( $user->ID );
				$this->display_customer_source_data( $customer );
			} catch ( Exception $e ) {
				$this->log( $e->getMessage(), __METHOD__, WC_Log_Levels::ERROR );
			}
		};

		add_action( 'show_user_profile', $customer_meta_boxes );
		add_action( 'edit_user_profile', $customer_meta_boxes );

		// Add source data to the order table.
		add_filter(
			'manage_edit-shop_order_columns',
			function( $columns ) {
				$columns['origin'] = esc_html__( 'Origin', 'woocommerce' );

				return $columns;
			}
		);

		add_action(
			'manage_shop_order_posts_custom_column',
			function( $column_name, $order_id ) {
				if ( 'origin' !== $column_name ) {
					return;
				}
				$this->display_origin_column( $order_id );
			},
			10,
			2
		);
	}

	/**
	 * Scripts & styles for custom source tracking and cart tracking.
	 */
	private function enqueue_scripts_and_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script(
			'sourcebuster-js',
			plugins_url( "assets/js/frontend/sourcebuster{$suffix}.js", WC_PLUGIN_FILE ),
			array( 'jquery' ),
			WC_VERSION,
			true
		);

		wp_enqueue_script(
			'woocommerce-order-source-attribution-js',
			plugins_url( "assets/js/frontend/order-source-attribution{$suffix}.js", WC_PLUGIN_FILE ),
			array( 'jquery', 'sourcebuster-js' ),
			WC_VERSION,
			true
		);

		// Pass parameters to Order Source Attribution JS.
		$params = array(
			'lifetime'      => (int) apply_filters( 'wc_order_source_attribution_cookie_lifetime_months', 6 ),
			'session'       => (int) apply_filters( 'wc_order_source_attribution_session_length_minutes', 30 ),
			'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'prefix'        => $this->field_prefix,
			'allowTracking' => wc_bool_to_string( apply_filters( 'wc_order_source_attribution_allow_tracking', true ) ),
		);

		wp_localize_script( 'woocommerce-order-source-attribution-js', 'wc_order_attribute_source_params', $params );
	}

	/**
	 * Enqueue the stylesheet for admin pages.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function enqueue_admin_scripts_and_styles() {
		$screen            = get_current_screen();
		$order_page_suffix = $this->is_hpos_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		if ( $screen->id !== $order_page_suffix ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_enqueue_script(
			'woocommerce-order-source-attribution-admin-js',
			plugins_url( "assets/js/admin/order-source-attribution-admin{$suffix}.js", WC_PLUGIN_FILE ),
			array( 'jquery' ),
			WC_VERSION
		);
	}

	/**
	 * Display the source data template for the customer.
	 *
	 * @param WC_Customer $customer The customer object.
	 *
	 * @return void
	 */
	private function display_customer_source_data( WC_Customer $customer ) {
		$meta = $this->filter_meta_data( $customer->get_meta_data() );

		// If we don't have any meta to show, return.
		if ( empty( $meta ) ) {
			return;
		}

		include dirname( WC_PLUGIN_FILE ) . '/templates/order/source-data-fields.php';
	}

	/**
	 * Display the origin column in the orders table.
	 *
	 * @since x.x.x
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	private function display_origin_column( $order_id ): void {
		// Ensure we've got a valid order.
		try {
			$order = $this->get_hpos_order_object( $order_id );
			$this->output_origin_column( $order );
		} catch ( Exception $e ) {
			return;
		}
	}

	/**
	 * Output the data for the Origin column in the orders table.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	private function output_origin_column( WC_Order $order ) {
		$source_type      = $order->get_meta( $this->get_meta_prefixed_field( 'type' ) );
		$source           = $order->get_meta( $this->get_meta_prefixed_field( 'utm_source' ) ) ?: esc_html__( '(none)', 'woocommerce' );
		$formatted_source = ucfirst( trim( $source, '()' ) );
		$label            = $this->get_source_label( $source_type );

		if ( empty( $label ) ) {
			echo esc_html( $formatted_source );
			return;
		}

		printf( $label, esc_html( $formatted_source ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Add attribution hidden input fields for checkout & customer register froms.
	 */
	private function source_form_fields() {
		foreach ( $this->fields as $field ) {
			printf( '<input type="hidden" name="%s" value="" />', esc_attr( $this->get_prefixed_field( $field ) ) );
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
		foreach ( $this->get_source_values() as $key => $value ) {
			$customer->add_meta_data( $key, $value );
		}

		$customer->save_meta_data();
	}

	/**
	 * Save source data for an Order object.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	private function set_order_source_data( WC_Order $order ) {
		foreach ( $this->get_source_values() as $key => $value ) {
			$order->add_meta_data( $key, $value );
		}

		$order->save_meta_data();
	}

	/**
	 * Map posted values to meta values.
	 *
	 * @return array
	 */
	private function get_source_values(): array {
		$values = array();

		// Look through each field in POST data.
		foreach ( $this->fields as $field ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$value = sanitize_text_field( wp_unslash( $_POST[ $this->get_prefixed_field( $field ) ] ?? '' ) );
			if ( '(none)' === $value ) {
				continue;
			}

			$values[ $this->get_meta_prefixed_field( $field ) ] = $value;
		}

		return $values;
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
		 * @since x.x.x
		 *
		 * @param string $enabled 'yes' to enable debug mode, 'no' to disable.
		 */
		if ( 'yes' !== apply_filters( 'wc_order_source_attribution_debug_mode_enabled', 'no' ) ) {
			return;
		}

		$this->logger->log(
			$level,
			sprintf( '%s %s', $method, $message ),
			array( 'source' => 'woocommerce-order-source-attribution' )
		);
	}

	/**
	 * Check to see if HPOS is enabled.
	 *
	 * @return bool
	 */
	private function is_hpos_enabled(): bool {
		try {
			/** @var CustomOrdersTableController $cot_controller */
			$cot_controller = wc_get_container()->get( CustomOrdersTableController::class );

			return $cot_controller->custom_orders_table_usage_is_enabled();
		} catch ( Exception $e ) {
			$this->log( $e->getMessage(), __METHOD__, WC_Log_Levels::ERROR );
			return false;
		}
	}
}
