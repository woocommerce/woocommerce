<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\AbstractOrderConfirmationBlock;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for order-confirmation permission decisions.
 */
final class AbstractOrderConfirmationBlockTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var AbstractOrderConfirmationBlock
	 */
	private $sut;

	/**
	 * Set up the test proxy.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new class() extends AbstractOrderConfirmationBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function get_view_order_permissions_proxy( ?WC_Order $order ) {
				return $this->get_view_order_permissions( $order );
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function email_verification_permitted_proxy( WC_Order $order ): bool {
				return $this->email_verification_permitted( $order );
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			protected function render_content( $order, $permission = false, $attributes = array(), $content = '' ) {
				return '';
			}
		};
	}

	/**
	 * @testdox Order details require the correct key and the appropriate owner, session, grace-period, or verified-email context.
	 * @dataProvider view_permission_cases
	 *
	 * @param string       $scenario Scenario to arrange.
	 * @param string|false $expected Expected permission.
	 */
	public function test_get_view_order_permissions( string $scenario, $expected ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request globals are restored fixture state.
		$original_get = $_GET;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Request globals are restored fixture state.
		$original_post        = $_POST;
		$original_user_id     = get_current_user_id();
		$original_draft_order = WC()->session->get( 'store_api_draft_order' );
		$order                = null;
		$filter_added         = false;

		try {
			$_GET  = array();
			$_POST = array();
			wp_set_current_user( 0 );
			WC()->session->set( 'store_api_draft_order', null );

			if ( 'null order' !== $scenario ) {
				$is_customer_order = in_array(
					$scenario,
					array( 'owner valid key', 'owner missing key', 'owner wrong key', 'wrong logged-in user', 'known shopper logged out', 'known shopper filter disabled' ),
					true
				);
				$customer_id       = $is_customer_order ? self::factory()->user->create( array( 'role' => 'customer' ) ) : 0;
				$order             = $this->create_order( $customer_id, ! in_array( $scenario, array( 'guest draft session', 'guest expired', 'guest verified email' ), true ) );
				$_GET['key']       = $order->get_order_key();

				switch ( $scenario ) {
					case 'owner valid key':
						wp_set_current_user( $customer_id );
						break;
					case 'owner missing key':
						wp_set_current_user( $customer_id );
						unset( $_GET['key'] );
						break;
					case 'owner wrong key':
						wp_set_current_user( $customer_id );
						$_GET['key'] = 'wc_order_wrong';
						break;
					case 'wrong logged-in user':
						wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
						break;
					case 'known shopper filter disabled':
						add_filter( 'woocommerce_order_received_verify_known_shoppers', '__return_false' );
						$filter_added = true;
						break;
					case 'guest draft session':
						WC()->session->set( 'store_api_draft_order', $order->get_id() );
						break;
					case 'guest verified email':
						$_POST['email']    = $order->get_billing_email();
						$_POST['_wpnonce'] = wp_create_nonce( 'wc_verify_email' );
						break;
				}
			}

			$this->assertSame( $expected, $this->sut->get_view_order_permissions_proxy( $order ) );
		} finally {
			if ( $filter_added ) {
				remove_filter( 'woocommerce_order_received_verify_known_shoppers', '__return_false' );
			}
			if ( $order instanceof WC_Order ) {
				$order->delete( true );
			}
			WC()->session->set( 'store_api_draft_order', $original_draft_order );
			wp_set_current_user( $original_user_id );
			$_GET  = $original_get;
			$_POST = $original_post;
		}
	}

	/**
	 * Named order permission cases.
	 *
	 * @return array<string, array{string, string|false}>
	 */
	public static function view_permission_cases(): array {
		return array(
			'null order'                    => array( 'null order', false ),
			'owner with valid key'          => array( 'owner valid key', 'full' ),
			'owner without key'             => array( 'owner missing key', false ),
			'owner with wrong key'          => array( 'owner wrong key', false ),
			'different logged-in customer'  => array( 'wrong logged-in user', false ),
			'known shopper logged out'      => array( 'known shopper logged out', false ),
			'known shopper filter disabled' => array( 'known shopper filter disabled', 'full' ),
			'guest Store API draft session' => array( 'guest draft session', 'full' ),
			'guest within grace period'     => array( 'guest grace period', 'full' ),
			'guest after grace period'      => array( 'guest expired', false ),
			'guest with verified email'     => array( 'guest verified email', 'full' ),
		);
	}

	/**
	 * @testdox Email verification is offered only to guest orders with guest checkout enabled and a valid key.
	 * @dataProvider email_verification_permission_cases
	 *
	 * @param bool $guest_checkout_enabled Whether guest checkout is enabled.
	 * @param bool $customer_order Whether the order belongs to a customer.
	 * @param bool $valid_key Whether the request has the order key.
	 * @param bool $expected Expected result.
	 */
	public function test_email_verification_permitted( bool $guest_checkout_enabled, bool $customer_order, bool $valid_key, bool $expected ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request globals are restored fixture state.
		$original_get    = $_GET;
		$original_option = get_option( 'woocommerce_enable_guest_checkout', null );
		$customer_id     = $customer_order ? self::factory()->user->create( array( 'role' => 'customer' ) ) : 0;
		$order           = $this->create_order( $customer_id );

		try {
			update_option( 'woocommerce_enable_guest_checkout', $guest_checkout_enabled ? 'yes' : 'no' );
			$_GET        = array();
			$_GET['key'] = $valid_key ? $order->get_order_key() : 'wc_order_wrong';

			$this->assertSame( $expected, $this->sut->email_verification_permitted_proxy( $order ) );
		} finally {
			$order->delete( true );
			$_GET = $original_get;
			if ( null === $original_option ) {
				delete_option( 'woocommerce_enable_guest_checkout' );
			} else {
				update_option( 'woocommerce_enable_guest_checkout', $original_option );
			}
		}
	}

	/**
	 * Named email-verification eligibility cases.
	 *
	 * @return array<string, array{bool, bool, bool, bool}>
	 */
	public static function email_verification_permission_cases(): array {
		return array(
			'guest checkout and valid key'      => array( true, false, true, true ),
			'guest checkout disabled'           => array( false, false, true, false ),
			'guest order with wrong key'        => array( true, false, false, false ),
			'known customer order with key'     => array( true, true, true, false ),
			'known customer without guest mode' => array( false, true, false, false ),
		);
	}

	/**
	 * Create a persisted order for a guest or registered customer.
	 *
	 * @param int  $customer_id Customer ID, or zero for a guest.
	 * @param bool $within_grace_period Whether the order is recent.
	 * @return WC_Order
	 */
	private function create_order( int $customer_id, bool $within_grace_period = true ): WC_Order {
		$order = wc_create_order( array( 'customer_id' => $customer_id ) );
		$order->set_billing_email( 'shopper-' . $order->get_id() . '@example.com' );
		if ( ! $within_grace_period ) {
			$order->set_date_created( time() - ( 20 * MINUTE_IN_SECONDS ) );
		}
		$order->save();

		return $order;
	}
}
