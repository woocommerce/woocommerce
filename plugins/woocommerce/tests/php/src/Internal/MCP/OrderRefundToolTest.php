<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrderRefundCreate;
use Automattic\WooCommerce\Internal\MCP\OrderRefundTool;
use WC_Unit_Test_Case;
use WP\MCP\Core\McpRequestContext;
use WP\MCP\Domain\Tools\McpInputRequired;
use WP\MCP\Domain\Tools\McpTool;
use WP\MCP\Domain\Tools\McpToolCallContext;
use WP\McpSchema\Schemas;

/**
 * Tests for the MCP tool that refunds an order after the merchant approves it.
 */
class OrderRefundToolTest extends WC_Unit_Test_Case {

	/**
	 * A shop manager that every test acts as.
	 *
	 * @var int
	 */
	private static $user_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$user_id = $factory->user->create( array( 'role' => 'shop_manager' ) );
	}

	/**
	 * Log in as a shop manager.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$user_id );
	}

	/**
	 * @testdox Should add the tool to the default server and keep the refund ability off MCP.
	 */
	public function test_replaces_the_refund_ability_on_the_default_server(): void {
		$config = OrderRefundTool::add_to_default_server( array( 'tools' => array( 'mcp-adapter/execute-ability' ) ) );

		$this->assertSame( 'mcp-adapter/execute-ability', $config['tools'][0] );
		$this->assertInstanceOf( McpTool::class, $config['tools'][1] );
		$this->assertSame( OrderRefundTool::TOOL_NAME, $config['tools'][1]->get_name() );
		$this->assertFalse( wp_get_ability( OrderRefundCreate::ABILITY_NAME )->get_meta()['mcp']['public'] );
	}

	/**
	 * @testdox Should not tell agents to use a dry run or the refund ability, which the default server does not offer.
	 */
	public function test_descriptions_do_not_point_to_unavailable_refund_paths(): void {
		$schema = OrderRefundTool::create()->get_protocol_record( Schemas::create()->forVersion( Schemas::V2026_07_28 ) )->getInputSchema();

		$this->assertFalse( property_exists( $schema->properties, 'dry_run' ) );
		$this->assertStringNotContainsString( 'dry run', $schema->properties->refund_payment->description );
		$this->assertStringNotContainsString( OrderRefundCreate::ABILITY_NAME, wp_get_ability( 'woocommerce/order-refunds-query' )->get_description() );
	}

	/**
	 * @testdox Should ask the merchant to approve the previewed refund, and refund nothing yet.
	 */
	public function test_first_call_asks_for_approval(): void {
		$order = $this->create_order();

		$result = OrderRefundTool::handle( $this->input( $order ), $this->context() );

		$this->assertInstanceOf( McpInputRequired::class, $result );
		$this->assertMatchesRegularExpression( '/\A[a-f0-9]{32}\z/', (string) $result->request_state() );
		$question = $result->input_requests()['approve_refund'];
		$this->assertSame( 'elicitation/create', $question['method'] );
		$this->assertStringContainsString( '10.00 USD', $question['params']['message'] );
		$this->assertStringContainsString( $order->get_order_number(), $question['params']['message'] );
		$this->assertStringContainsString( 'only recorded', $question['params']['message'] );
		$this->assertSame( array( 'approve' ), $question['params']['requestedSchema']['required'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should describe the customer, the amounts before and after the refund, and that nothing is refunded yet.
	 */
	public function test_question_describes_the_order_and_the_remaining_amount(): void {
		$order = $this->create_order();
		$order->set_billing_first_name( 'Jane' );
		$order->set_billing_last_name( 'Doe' );
		$order->save();
		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => '5',
			)
		);

		$message = $this->ask_message( $order, '10' );

		$this->assertStringContainsString( 'from Jane Doe', $message );
		$this->assertStringContainsString( 'total 50.00 USD', $message );
		$this->assertStringContainsString( 'Already refunded: 5.00 USD. Left to refund after this: 35.00 USD.', $message );
		$this->assertStringNotContainsString( 'status changes to Refunded', $message );
		$this->assertStringContainsString( 'Nothing has been refunded yet.', $message );
	}

	/**
	 * @testdox Should warn that a refund of the whole remaining amount changes the order status.
	 */
	public function test_question_warns_about_a_full_refund(): void {
		$order = $this->create_order();

		$message = $this->ask_message( $order, '50' );

		$this->assertStringContainsString( 'Left to refund after this: 0.00 USD.', $message );
		$this->assertStringContainsString( 'its status changes to Refunded', $message );
	}

	/**
	 * @testdox Should say that the payment gateway sends the money back when the refund goes through it.
	 */
	public function test_question_names_the_gateway_for_a_payment_refund(): void {
		$gateways                                        = WC()->payment_gateways();
		$original                                        = $gateways->payment_gateways;
		$gateways->payment_gateways['refunding_gateway'] = new class() extends \WC_Payment_Gateway {
			/**
			 * Set the ID and the refund support.
			 */
			public function __construct() {
				$this->id       = 'refunding_gateway';
				$this->enabled  = 'yes';
				$this->supports = array( PaymentGatewayFeature::PRODUCTS, PaymentGatewayFeature::REFUNDS );
			}
		};
		$order = $this->create_order();
		$order->set_payment_method( 'refunding_gateway' );
		$order->set_payment_method_title( 'Test card' );
		$order->save();

		try {
			$result = OrderRefundTool::handle( $this->input( $order ) + array( 'refund_payment' => true ), $this->context() );
		} finally {
			$gateways->payment_gateways = $original;
		}

		$this->assertInstanceOf( McpInputRequired::class, $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertStringContainsString( 'The payment gateway (Test card) sends 10.00 USD back to the customer', $result->input_requests()['approve_refund']['params']['message'] );
	}

	/**
	 * @testdox Should create the refund when the merchant approves it.
	 */
	public function test_approval_creates_the_refund(): void {
		$order = $this->create_order();
		$state = $this->ask( $order );

		$result = OrderRefundTool::handle( $this->input( $order ), $this->context( $state, 'accept', true ) );

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertFalse( $result['dry_run'] );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refund nothing when the merchant declines, and not accept the same approval afterwards.
	 */
	public function test_decline_refunds_nothing(): void {
		$order = $this->create_order();
		$state = $this->ask( $order );

		$declined = OrderRefundTool::handle( $this->input( $order ), $this->context( $state, 'decline' ) );
		$retried  = OrderRefundTool::handle( $this->input( $order ), $this->context( $state, 'accept', true ) );

		$this->assertSame( false, $declined['refunded'] );
		$this->assertWPError( $retried );
		$this->assertSame( 'woocommerce_mcp_order_refund_approval_unavailable', $retried->get_error_code() );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refund nothing when the merchant accepts the form but leaves approve false.
	 */
	public function test_accept_without_approval_refunds_nothing(): void {
		$order = $this->create_order();
		$state = $this->ask( $order );

		$result = OrderRefundTool::handle( $this->input( $order ), $this->context( $state, 'accept', false ) );

		$this->assertSame( false, $result['refunded'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should use an approval only once, so a repeated answer cannot refund twice.
	 */
	public function test_approval_is_used_once(): void {
		$order = $this->create_order();
		$state = $this->ask( $order );
		OrderRefundTool::handle( $this->input( $order ), $this->context( $state, 'accept', true ) );

		$result = OrderRefundTool::handle( $this->input( $order ), $this->context( $state, 'accept', true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_mcp_order_refund_approval_unavailable', $result->get_error_code() );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refuse an approval for another refund, so the approved amount cannot be changed on the retry.
	 */
	public function test_approval_is_bound_to_the_input(): void {
		$order = $this->create_order();
		$state = $this->ask( $order );

		$result = OrderRefundTool::handle( $this->input( $order, '40' ), $this->context( $state, 'accept', true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_mcp_order_refund_approval_unavailable', $result->get_error_code() );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refuse an approval that another user asked for.
	 */
	public function test_approval_is_bound_to_the_user(): void {
		$order = $this->create_order();
		$state = $this->ask( $order );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$result = OrderRefundTool::handle( $this->input( $order ), $this->context( $state, 'accept', true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_mcp_order_refund_approval_unavailable', $result->get_error_code() );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refuse an approval that expired.
	 */
	public function test_expired_approval_is_refused(): void {
		$order = $this->create_order();
		$state = $this->ask( $order );
		delete_transient( 'wc_order_refund_approval_' . $state );

		$result = OrderRefundTool::handle( $this->input( $order ), $this->context( $state, 'accept', true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_mcp_order_refund_approval_unavailable', $result->get_error_code() );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should ask again with the same approval when the retry carries no answer.
	 */
	public function test_retry_without_answer_asks_again(): void {
		$order = $this->create_order();
		$state = $this->ask( $order );

		$result = OrderRefundTool::handle( $this->input( $order ), $this->context( $state ) );

		$this->assertInstanceOf( McpInputRequired::class, $result );
		$this->assertSame( $state, $result->request_state() );
	}

	/**
	 * @testdox Should return the error of the preview, such as an over-refund, without asking.
	 */
	public function test_returns_preview_error_without_asking(): void {
		$order = $this->create_order();

		$result = OrderRefundTool::handle( $this->input( $order, '500' ), $this->context() );

		$this->assertWPError( $result );
		$this->assertStringStartsWith( 'woocommerce_ability_order_refund_', $result->get_error_code() );
	}

	/**
	 * @testdox Should refund on the first call when the client cannot show forms.
	 */
	public function test_refunds_directly_without_elicitation(): void {
		$order = $this->create_order();

		$result = OrderRefundTool::handle( $this->input( $order ), $this->context( null, null, null, false ) );

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should only preview when the caller sends dry_run, even without elicitation.
	 */
	public function test_dry_run_only_previews(): void {
		$order = $this->create_order();

		$result = OrderRefundTool::handle( $this->input( $order ) + array( 'dry_run' => true ), $this->context( null, null, null, false ) );

		$this->assertTrue( $result['dry_run'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Should refuse a user who cannot edit the order.
	 */
	public function test_checks_the_ability_permission(): void {
		$order = $this->create_order();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertNotTrue( OrderRefundTool::check_permission( $this->input( $order ) ) );
	}

	/**
	 * Ask for the approval of a refund of 10, and return its request state.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	private function ask( \WC_Order $order ): string {
		$result = OrderRefundTool::handle( $this->input( $order ), $this->context() );
		$this->assertInstanceOf( McpInputRequired::class, $result );

		return (string) $result->request_state();
	}

	/**
	 * Ask for the approval of a manual refund, and return the message of the question.
	 *
	 * @param \WC_Order $order  Order.
	 * @param string    $amount Amount.
	 * @return string
	 */
	private function ask_message( \WC_Order $order, string $amount ): string {
		$result = OrderRefundTool::handle( $this->input( $order, $amount ), $this->context() );
		$this->assertInstanceOf( McpInputRequired::class, $result, is_wp_error( $result ) ? $result->get_error_message() : '' );

		return $result->input_requests()['approve_refund']['params']['message'];
	}

	/**
	 * Tool arguments for a manual refund of an amount.
	 *
	 * @param \WC_Order $order  Order.
	 * @param string    $amount Amount.
	 * @return array
	 */
	private function input( \WC_Order $order, string $amount = '10' ): array {
		return array(
			'id'     => $order->get_id(),
			'amount' => $amount,
		);
	}

	/**
	 * A call context of an MCP 2026-07-28 client, with the answer to the approval question when one is given.
	 *
	 * @param string|null $state       Request state of the approval.
	 * @param string|null $action      Elicitation action: accept, decline or cancel.
	 * @param bool|null   $approve     Value of the approve field, for accept.
	 * @param bool        $elicitation Whether the client supports form elicitation.
	 * @return McpToolCallContext
	 */
	private function context( ?string $state = null, ?string $action = null, ?bool $approve = null, bool $elicitation = true ): McpToolCallContext {
		$capabilities = $elicitation ? (object) array( 'elicitation' => (object) array( 'form' => new \stdClass() ) ) : new \stdClass();
		$answers      = new \stdClass();
		if ( null !== $action ) {
			$answer = (object) array( 'action' => $action );
			if ( null !== $approve ) {
				$answer->content = (object) array( 'approve' => $approve );
			}
			$answers->approve_refund = $answer;
		}

		return new McpToolCallContext(
			new McpRequestContext( Schemas::create()->forVersion( Schemas::V2026_07_28 ), $capabilities, null, 'STDIO' ),
			$answers,
			$state,
			null !== $state || null !== $action
		);
	}

	/**
	 * Create a processing order with a total of 50 and no lines, paid by bank transfer.
	 *
	 * @return \WC_Order
	 */
	private function create_order(): \WC_Order {
		$order = wc_create_order();
		$order->set_payment_method( 'bacs' );
		$order->set_currency( 'USD' );
		$order->set_total( '50' );
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		return $order;
	}
}
