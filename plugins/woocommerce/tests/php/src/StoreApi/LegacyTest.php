<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Legacy;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use WC_Order;
use WC_Payment_Gateway;
use WC_Unit_Test_Case;

/**
 * Tests for the StoreApi Legacy class, especially the resilience of
 * process_legacy_payment() to non-array return values from a gateway's
 * process_payment() method.
 */
class LegacyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Legacy
	 */
	private $sut;

	/**
	 * Order used across tests.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut   = new Legacy();
		$this->order = wc_create_order();

		// Make sure no stale notices leak between tests.
		wc_clear_notices();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wc_clear_notices();
		parent::tearDown();
	}

	/**
	 * Build a PaymentContext anonymous subclass returning the supplied gateway
	 * instance from get_payment_method_instance(), bypassing the global
	 * registered gateways list.
	 *
	 * @param WC_Payment_Gateway|null $gateway Gateway instance to inject.
	 * @return PaymentContext
	 */
	private function make_context( $gateway ): PaymentContext {
		$context = new class() extends PaymentContext {
			/**
			 * The injected gateway instance.
			 *
			 * @var WC_Payment_Gateway|null
			 */
			public $injected_gateway;

			/**
			 * Override to return the injected gateway.
			 *
			 * @return WC_Payment_Gateway|null
			 */
			public function get_payment_method_instance() {
				return $this->injected_gateway;
			}
		};

		$context->injected_gateway = $gateway;
		$context->set_order( $this->order );

		return $context;
	}

	/**
	 * @testdox Should not fatal and should default status to failure when gateway returns null with no notices.
	 */
	public function test_handles_null_gateway_result_without_notices(): void {
		$gateway = new class() extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id = 'qa_null_gateway';
			}

			/**
			 * Returns null instead of the documented array.
			 *
			 * @param int $order_id Order ID.
			 * @return null
			 */
			public function process_payment( $order_id ) {
				return null;
			}
		};

		$context = $this->make_context( $gateway );
		$result  = new PaymentResult();

		$this->sut->process_legacy_payment( $context, $result );

		$this->assertSame(
			'failure',
			$result->get_status(),
			'A null gateway result with no notices should be treated as a generic failure.'
		);
	}

	/**
	 * @testdox Should throw a RouteException carrying the gateway notice when gateway returns null after adding an error notice.
	 */
	public function test_converts_notice_to_exception_when_gateway_returns_null_with_notice(): void {
		$gateway = new class() extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id = 'qa_null_gateway_with_notice';
			}

			/**
			 * Adds an error notice and returns null.
			 *
			 * @param int $order_id Order ID.
			 * @return null
			 */
			public function process_payment( $order_id ) {
				wc_add_notice( 'my gateway error', 'error' );
				return null;
			}
		};

		$context = $this->make_context( $gateway );
		$result  = new PaymentResult();

		$this->expectException( RouteException::class );
		$this->expectExceptionMessage( 'my gateway error' );

		$this->sut->process_legacy_payment( $context, $result );
	}

	/**
	 * @testdox Should default status to failure without fatal when gateway returns a non-array scalar.
	 */
	public function test_handles_non_array_scalar_gateway_result(): void {
		$gateway = new class() extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id = 'qa_scalar_gateway';
			}

			/**
			 * Returns an unexpected string.
			 *
			 * @param int $order_id Order ID.
			 * @return string
			 */
			public function process_payment( $order_id ) {
				return 'not-an-array';
			}
		};

		$context = $this->make_context( $gateway );
		$result  = new PaymentResult();

		$this->sut->process_legacy_payment( $context, $result );

		$this->assertSame(
			'failure',
			$result->get_status(),
			'A non-array scalar gateway result should be treated as a failure.'
		);
	}

	/**
	 * @testdox Should pass status and redirect through when gateway returns a valid success array.
	 */
	public function test_passes_through_valid_success_array(): void {
		$gateway = new class() extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id = 'qa_success_gateway';
			}

			/**
			 * Returns the canonical success array shape.
			 *
			 * @param int $order_id Order ID.
			 * @return array
			 */
			public function process_payment( $order_id ) {
				return array(
					'result'   => 'success',
					'redirect' => 'https://example.test/thanks',
				);
			}
		};

		$context = $this->make_context( $gateway );
		$result  = new PaymentResult();

		$this->sut->process_legacy_payment( $context, $result );

		$this->assertSame( 'success', $result->get_status(), 'A success array should set status to success.' );
		$this->assertSame(
			'https://example.test/thanks',
			$result->redirect_url,
			'A success array with a redirect should propagate the redirect URL.'
		);
	}

	/**
	 * @testdox Should throw a RouteException carrying the gateway message when result array reports failure with a message.
	 */
	public function test_failure_array_with_message_throws_route_exception(): void {
		$gateway = new class() extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id = 'qa_failure_message_gateway';
			}

			/**
			 * Returns a failure array with a message.
			 *
			 * @param int $order_id Order ID.
			 * @return array
			 */
			public function process_payment( $order_id ) {
				return array(
					'result'  => 'failure',
					'message' => 'card declined',
				);
			}
		};

		$context = $this->make_context( $gateway );
		$result  = new PaymentResult();

		$this->expectException( RouteException::class );
		$this->expectExceptionMessage( 'card declined' );

		$this->sut->process_legacy_payment( $context, $result );
	}
}
