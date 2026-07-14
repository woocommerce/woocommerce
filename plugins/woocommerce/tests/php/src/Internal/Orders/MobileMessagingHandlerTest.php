<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Orders\MobileMessagingHandler;
use DateTime;
use WC_Order;
use WC_Gateway_COD;

/**
 * Tests for MobileMessagingHandler.
 */
class MobileMessagingHandlerTest extends \WC_Unit_Test_Case {

	const BLOG_ID  = 2;
	const ORDER_ID = 5;
	const DOMAIN   = 'sample-domain.com';

	/** @var array{exists: bool, value: string|null, autoload: string|null} Initial raw country option state. */
	private static array $initial_country = array();

	/** @var array{exists: bool, value: string|null, autoload: string|null} Initial raw currency option state. */
	private static array $initial_currency = array();

	/**
	 * Saves values of initial country and currency before running test suite.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::$initial_country  = self::get_raw_option_state( 'woocommerce_default_country' );
		self::$initial_currency = self::get_raw_option_state( 'woocommerce_currency' );
	}

	/**
	 * Restores initial values of country and currency after running test suite.
	 */
	public static function wpTearDownAfterClass(): void {
		try {
			self::restore_raw_option_state( 'woocommerce_default_country', self::$initial_country );
		} finally {
			self::restore_raw_option_state( 'woocommerce_currency', self::$initial_currency );
		}
	}

	/**
	 * Read an option without default-option filters or value coercion.
	 *
	 * @param string $option_name Option name.
	 * @return array{exists: bool, value: string|null, autoload: string|null}
	 */
	private static function get_raw_option_state( string $option_name ): array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $option_name ),
			ARRAY_A
		);

		return null === $row
			? array(
				'exists'   => false,
				'value'    => null,
				'autoload' => null,
			)
			: array(
				'exists'   => true,
				'value'    => $row['option_value'],
				'autoload' => $row['autoload'],
			);
	}

	/**
	 * Restore an option without invoking setting sanitizers.
	 *
	 * @param string                                                         $option_name Option name.
	 * @param array{exists: bool, value: string|null, autoload: string|null} $state Raw option state.
	 */
	private static function restore_raw_option_state( string $option_name, array $state ): void {
		global $wpdb;

		if ( ! $state['exists'] ) {
			$result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option_name ) );
		} elseif ( self::get_raw_option_state( $option_name )['exists'] ) {
			$result = $wpdb->update(
				$wpdb->options,
				array(
					'option_value' => $state['value'],
					'autoload'     => $state['autoload'],
				),
				array( 'option_name' => $option_name )
			);
		} else {
			$result = $wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $option_name,
					'option_value' => $state['value'],
					'autoload'     => $state['autoload'],
				)
			);
		}
		\PHPUnit\Framework\Assert::assertNotFalse( $result, "Failed to restore option {$option_name}." );

		wp_cache_delete( $option_name, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Tests if SUT is not throwing an exception in scenario, when user has only
	 * one mobile platform usage recorded.
	 */
	public function test_tracker_reports_only_android_usage() {
		$now = $this->prepare_timeline_with_valid_last_mobile_app_usage();

		$mobile_message = MobileMessagingHandler::prepare_mobile_message( new WC_Order(), self::BLOG_ID, $now, self::DOMAIN );

		$this->assertNotNull( $mobile_message );
	}

	/**
	 * Tests if SUT returns correct message when there are no mobile app usages reported and order is not ipp eligible
	 */
	public function test_show_get_app_message_when_no_mobile_reports_at_all() {
		$now = new DateTime( '2022-08-05T00:00:00+00:00' );
		update_option(
			'woocommerce_mobile_app_usage',
			array()
		);

		$mobile_message = MobileMessagingHandler::prepare_mobile_message( new WC_Order(), self::BLOG_ID, $now, self::DOMAIN );

		$this->assertStringContainsString(
			'href="https://woocommerce.com/mobile?blog_id=' . self::BLOG_ID . '&#038;utm_campaign=deeplinks_promote_app&#038;utm_medium=email&#038;utm_source=' . self::DOMAIN . '&#038;utm_term=' . self::BLOG_ID,
			$mobile_message
		);
	}

	/**
	 * Tests if SUT returns message with expected deep link when the user uses mobile app but store is not eligible for IPP and order is
	 */
	public function test_show_manage_order_message_when_store_is_NOT_ipp_eligible() {
		$now = $this->prepare_timeline_with_valid_last_mobile_app_usage();
		$this->make_store_not_ipp_eligible();
		$ipp_eligible_order = $this->generate_ipp_eligible_order();

		$mobile_message = MobileMessagingHandler::prepare_mobile_message( $ipp_eligible_order, self::BLOG_ID, $now, self::DOMAIN );

		$this->assertStringContainsString(
			'href="https://woocommerce.com/mobile/orders/details?blog_id=' . self::BLOG_ID . '&#038;order_id=' . self::ORDER_ID . '&#038;utm_campaign=deeplinks_orders_details&#038;utm_medium=email&#038;utm_source=' . self::DOMAIN . '&#038;utm_term=' . self::BLOG_ID,
			$mobile_message
		);
	}

	/**
	 * Tests if SUT returns correct message when the user uses mobile app and store and order are eligible for IPP
	 */
	public function test_show_accept_payment_message_when_store_and_order_are_ipp_eligible() {
		$now = $this->prepare_timeline_with_valid_last_mobile_app_usage();
		$this->make_store_ipp_eligible();
		$ipp_eligible_order = $this->generate_ipp_eligible_order();

		$mobile_message = MobileMessagingHandler::prepare_mobile_message( $ipp_eligible_order, self::BLOG_ID, $now, self::DOMAIN );

		$this->assertStringContainsString(
			'href="https://woocommerce.com/mobile/payments?blog_id=' . self::BLOG_ID . '&#038;utm_campaign=deeplinks_payments&#038;utm_medium=email&#038;utm_source=' . self::DOMAIN . '&#038;utm_term=' . self::BLOG_ID,
			$mobile_message
		);
	}

	/**
	 * Tests if SUT returns correct message when the user is IPP eligible but does not use mobile app and site has no Jetpack
	 */
	public function test_show_accept_payment_message_when_store_and_order_are_ipp_eligible_but_no_mobile_app_usage() {
		$now = new DateTime( '2022-08-05T00:00:00+00:00' );
		update_option(
			'woocommerce_mobile_app_usage',
			array()
		);
		$this->make_store_ipp_eligible();
		$ipp_eligible_order = $this->generate_ipp_eligible_order();

		$mobile_message = MobileMessagingHandler::prepare_mobile_message( $ipp_eligible_order, null, $now, self::DOMAIN );

		$this->assertStringContainsString(
			'href="https://woocommerce.com/mobile/payments?blog_id=0&#038;utm_campaign=deeplinks_payments&#038;utm_medium=email&#038;utm_source=' . self::DOMAIN . '&#038;utm_term=0',
			$mobile_message
		);
	}

	/**
	 * Sets store to location USA and currency USD making it In-Person Payments eligible
	 */
	private function make_store_ipp_eligible() {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_currency', 'USD' );
	}

	/**
	 * Sets store to random location and currency making it NOT In-Person Payments eligible
	 */
	private function make_store_not_ipp_eligible() {
		update_option( 'woocommerce_default_country', 'AA:BB' );
		update_option( 'woocommerce_currency', 'CCC' );
	}

	/**
	 * Sets time of last mobile app usage within specified by @const WC_Mobile_Messaging_Handler#OPEN_ORDER_INTERVAL_DAYS interval
	 *
	 * @return DateTime date of execution within interval with mobile app usage
	 */
	private function prepare_timeline_with_valid_last_mobile_app_usage(): DateTime {
		$now = new DateTime( '2022-08-05T00:00:00+00:00' );
		update_option(
			'woocommerce_mobile_app_usage',
			array(
				'android' => array(
					'last_used' => '2022-08-03T00:00:00+00:00',
				),
			)
		);

		return $now;
	}

	/**
	 * @return WC_Order returns an empty Order, eligible for In-Person Payment
	 */
	public static function generate_ipp_eligible_order(): WC_Order {
		$ipp_eligible_order = new WC_Order();
		$ipp_eligible_order->set_id( self::ORDER_ID );
		$ipp_eligible_order->set_status( OrderStatus::PENDING );
		try {
			$ipp_eligible_order->set_payment_method( WC_Gateway_COD::ID );
		} catch ( WC_Data_Exception $e ) {
			exit();
		}

		return $ipp_eligible_order;
	}
}
