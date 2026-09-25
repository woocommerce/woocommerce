<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\CashSessions;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\CashSessions\CashSessionsDataStore;
use WC_Order;
use WC_Order_Refund;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for the cash sessions REST API (/wc/pos/v1/cash-sessions).
 */
class CashSessionsRestControllerTest extends WC_REST_Unit_Test_Case {

	private const BASE = '/wc/pos/v1/cash-sessions';

	/**
	 * Store manager used for most requests.
	 *
	 * @var int
	 */
	private $manager_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_currency', 'EUR' );
		update_option( 'woocommerce_price_num_decimals', '2' );

		$this->manager_id = $this->factory->user->create(
			array(
				'role'         => 'shop_manager',
				'display_name' => 'Store Manager',
			)
		);
		wp_set_current_user( $this->manager_id );
	}

	/**
	 * @testdox Should open a session with its opening float, captured currency, actor and UTC timestamps.
	 */
	public function test_open_session(): void {
		$response = $this->open_session( array( 'opening_amount' => '150.5' ) );
		$data     = $response->get_data();

		$this->assertSame( 201, $response->get_status() );
		$this->assertStringEndsWith( self::BASE . '/' . $data['id'], $response->get_headers()['Location'] );
		$this->assertSame( 'open', $data['status'] );
		$this->assertSame( 1, $data['revision'] );
		$this->assertSame( 'EUR', $data['currency'] );
		$this->assertSame( 2, $data['currency_precision'] );
		$this->assertSame( '150.50', $data['opening_amount'] );
		$this->assertSame( '0.00', $data['cash_sales_total'] );
		$this->assertSame( '150.50', $data['expected_amount'] );
		$this->assertNull( $data['counted_amount'] );
		$this->assertNull( $data['variance'] );
		$this->assertNull( $data['drawer_id'] );
		$this->assertSame( '', $data['note'] );
		$this->assertSame( $this->manager_id, $data['opened_by'] );
		$this->assertSame( 'Store Manager', $data['opened_by_name'] );
		$this->assertNull( $data['closed_by'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $data['date_created_gmt'] );
		$this->assertNull( $data['date_closed_gmt'] );

		$movements = $this->request( 'GET', self::BASE . '/' . $data['id'] . '/movements' )->get_data();
		$this->assertCount( 1, $movements );
		$this->assertSame( 'opening_float', $movements[0]['type'] );
		$this->assertSame( 'in', $movements[0]['direction'] );
		$this->assertSame( '150.50', $movements[0]['amount'] );
	}

	/**
	 * @testdox Should keep the currency and precision captured at opening when store settings change.
	 */
	public function test_session_keeps_captured_currency(): void {
		$session_id = $this->open_session( array( 'opening_amount' => '0' ) )->get_data()['id'];

		update_option( 'woocommerce_currency', 'JPY' );
		update_option( 'woocommerce_price_num_decimals', '0' );

		$data = $this->request( 'GET', self::BASE . '/' . $session_id )->get_data();
		$this->assertSame( 'EUR', $data['currency'] );
		$this->assertSame( 2, $data['currency_precision'] );
		$this->assertSame( '0.00', $data['opening_amount'] );

		$response = $this->record_movement(
			$session_id,
			array(
				'type'   => 'paid_in',
				'amount' => '1.25',
				'reason' => 'Change',
			)
		);
		$this->assertSame( 201, $response->get_status(), 'Validation should use the session precision' );
	}

	/**
	 * @testdox Should retrieve an open session from storage in a fresh request.
	 */
	public function test_get_session_after_reload(): void {
		$session_id = $this->open_session( array( 'opening_amount' => '20.00' ) )->get_data()['id'];

		wp_cache_flush();
		$response = $this->request( 'GET', self::BASE . '/' . $session_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '20.00', $response->get_data()['expected_amount'] );
	}

	/**
	 * @testdox Should return 404 for a missing session on every session route.
	 */
	public function test_missing_session_returns_404(): void {
		$missing = self::BASE . '/999999';

		$this->assertSame( 404, $this->request( 'GET', $missing )->get_status() );
		$this->assertSame( 404, $this->request( 'GET', "$missing/movements" )->get_status() );
		$this->assertSame( 404, $this->request( 'GET', "$missing/drawer-events" )->get_status() );
		$this->assertSame(
			404,
			$this->request(
				'POST',
				"$missing/close",
				array(
					'request_id'        => wp_generate_uuid4(),
					'expected_revision' => 1,
					'counted_amount'    => '0',
				)
			)->get_status()
		);
		$this->assertSame(
			404,
			$this->record_movement(
				999999,
				array(
					'type'   => 'paid_in',
					'amount' => '1',
					'reason' => 'x',
				)
			)->get_status()
		);
		$response = $this->record_drawer_event( 999999, array() );
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cash_session_not_found', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should reject invalid opening amounts without creating a session.
	 *
	 * @testWith ["-1.00"]
	 *           ["1.005"]
	 *           ["1e2"]
	 *           ["1,00"]
	 *           [""]
	 *           ["100000000000000.00"]
	 *
	 * @param string $amount Test value.
	 */
	public function test_open_rejects_invalid_amount( string $amount ): void {
		$response = $this->open_session( array( 'opening_amount' => $amount ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 0, $this->count_rows( 'sessions' ) );
	}

	/**
	 * @testdox Should reject a missing or malformed request ID.
	 */
	public function test_open_requires_uuid_request_id(): void {
		$this->assertSame( 400, $this->open_session( array( 'request_id' => 'not-a-uuid' ) )->get_status() );

		$request = array(
			'device_id'      => 'ipad-1',
			'opening_amount' => '1',
		);
		$this->assertSame( 400, $this->request( 'POST', self::BASE, $request )->get_status() );
	}

	/**
	 * @testdox Should replay an open request, even after close, without a second opening float.
	 */
	public function test_open_replay(): void {
		$request_id = wp_generate_uuid4();
		$first      = $this->open_session(
			array(
				'request_id'     => $request_id,
				'opening_amount' => '10.00',
				'drawer_id'      => 'Front counter',
			)
		);
		$retry      = $this->open_session(
			array(
				'request_id'     => strtoupper( $request_id ),
				'opening_amount' => '10.0',
				'drawer_id'      => '  front COUNTER ',
			)
		);

		$this->assertSame( 200, $retry->get_status() );
		$this->assertSame( $first->get_data(), $retry->get_data() );
		$this->assertSame( 1, $this->count_rows( 'movements' ) );

		$this->close_session( $first->get_data()['id'], 1, '10.00' );
		$after_close = $this->open_session(
			array(
				'request_id'     => $request_id,
				'opening_amount' => '10.00',
				'drawer_id'      => 'Front counter',
			)
		);
		$this->assertSame( 200, $after_close->get_status() );
		$this->assertSame( 'closed', $after_close->get_data()['status'] );
	}

	/**
	 * @testdox Should reject reusing an open request ID with a different payload.
	 */
	public function test_open_request_conflict(): void {
		$request_id = wp_generate_uuid4();
		$this->open_session(
			array(
				'request_id'     => $request_id,
				'opening_amount' => '10.00',
			)
		);

		$response = $this->open_session(
			array(
				'request_id'     => $request_id,
				'opening_amount' => '11.00',
			)
		);

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cash_request_conflict', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should allow one open session per device and report the existing one.
	 */
	public function test_duplicate_open_for_device(): void {
		$first = $this->open_session( array( 'device_id' => 'ipad-1' ) )->get_data();

		$response = $this->open_session( array( 'device_id' => 'ipad-1' ) );
		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cash_session_already_open', $response->get_data()['code'] );
		$this->assertSame( $first['id'], $response->get_data()['data']['session_id'] );

		$this->assertSame( 201, $this->open_session( array( 'device_id' => 'ipad-2' ) )->get_status() );

		$this->close_session( $first['id'], 1, '0' );
		$this->assertSame( 201, $this->open_session( array( 'device_id' => 'ipad-1' ) )->get_status(), 'A device can open again after close' );
	}

	/**
	 * @testdox Should keep the trimmed drawer spelling and match drawer names case-insensitively.
	 */
	public function test_drawer_name_binding_and_filter(): void {
		$front = $this->open_session(
			array(
				'device_id' => 'ipad-1',
				'drawer_id' => "  Front counter\t",
			)
		)->get_data();
		$this->open_session(
			array(
				'device_id' => 'ipad-2',
				'drawer_id' => 'Back office',
			)
		);
		$this->close_session( $front['id'], 1, '0' );
		$renamed = $this->open_session(
			array(
				'device_id' => 'ipad-1',
				'drawer_id' => 'FRONT COUNTER',
			)
		)->get_data();

		$this->assertSame( 'Front counter', $front['drawer_id'] );
		$this->assertSame( 'FRONT COUNTER', $renamed['drawer_id'] );

		$list = $this->request( 'GET', self::BASE, array( 'drawer_id' => ' front counter ' ) );
		$this->assertSame( array( $renamed['id'], $front['id'] ), array_column( $list->get_data(), 'id' ) );
		$this->assertSame( 'Front counter', $list->get_data()[1]['drawer_id'], 'History keeps the original spelling' );

		$this->assertSame( 400, $this->open_session( array( 'drawer_id' => '   ' ) )->get_status() );
		$this->assertSame( 400, $this->open_session( array( 'drawer_id' => str_repeat( 'a', 129 ) ) )->get_status() );
	}

	/**
	 * @testdox Should list sessions newest first with filters and pagination headers.
	 */
	public function test_list_sessions(): void {
		$ids = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$ids[] = $this->open_session( array( 'device_id' => "ipad-$i" ) )->get_data()['id'];
		}
		$this->close_session( $ids[0], 1, '0' );

		$page = $this->request(
			'GET',
			self::BASE,
			array(
				'per_page' => 2,
				'page'     => 1,
			)
		);
		$this->assertSame( array( $ids[2], $ids[1] ), array_column( $page->get_data(), 'id' ) );
		$this->assertSame( '3', $page->get_headers()['X-WP-Total'] );
		$this->assertSame( '2', $page->get_headers()['X-WP-TotalPages'] );

		$page2 = $this->request(
			'GET',
			self::BASE,
			array(
				'per_page' => 2,
				'page'     => 2,
			)
		);
		$this->assertSame( array( $ids[0] ), array_column( $page2->get_data(), 'id' ) );

		$this->assertSame( array( $ids[0] ), array_column( $this->request( 'GET', self::BASE, array( 'status' => 'closed' ) )->get_data(), 'id' ) );
		$this->assertSame( array( $ids[1] ), array_column( $this->request( 'GET', self::BASE, array( 'device_id' => 'ipad-2' ) )->get_data(), 'id' ) );
		$this->assertSame( array(), $this->request( 'GET', self::BASE, array( 'device_id' => 'IPAD-2' ) )->get_data() );
		$this->assertSame( 400, $this->request( 'GET', self::BASE, array( 'per_page' => 101 ) )->get_status() );
	}

	/**
	 * @testdox Should record paid in and paid out movements and update authoritative totals.
	 */
	public function test_paid_in_and_out_totals(): void {
		$session_id = $this->open_session( array( 'opening_amount' => '100.00' ) )->get_data()['id'];

		$in  = $this->record_movement(
			$session_id,
			array(
				'type'        => 'paid_in',
				'amount'      => '25.10',
				'reason'      => 'Extra change',
				'occurred_at' => '2026-09-25T14:00:00+02:00',
			)
		);
		$out = $this->record_movement(
			$session_id,
			array(
				'type'   => 'paid_out',
				'amount' => '40.20',
				'reason' => 'Supplier',
			)
		);

		$this->assertSame( 201, $in->get_status() );
		$this->assertSame( 'in', $in->get_data()['direction'] );
		$this->assertSame( '2026-09-25T12:00:00Z', $in->get_data()['occurred_at'] );
		$this->assertSame( $this->manager_id, $in->get_data()['created_by'] );
		$this->assertSame( 'Store Manager', $in->get_data()['created_by_name'] );
		$this->assertSame( 'out', $out->get_data()['direction'] );
		$this->assertNull( $out->get_data()['order_id'] );

		$session = $this->request( 'GET', self::BASE . "/$session_id" )->get_data();
		$this->assertSame( '25.10', $session['paid_in_total'] );
		$this->assertSame( '40.20', $session['paid_out_total'] );
		$this->assertSame( '84.90', $session['expected_amount'] );
		$this->assertSame( 3, $session['revision'] );
	}

	/**
	 * @testdox Should reject invalid paid in or paid out requests.
	 *
	 * @dataProvider invalid_movement_provider
	 *
	 * @param array<string, mixed> $body Request body without request_id.
	 */
	public function test_invalid_movements_are_rejected( array $body ): void {
		$session_id = $this->open_session()->get_data()['id'];

		$response = $this->record_movement( $session_id, $body );

		$this->assertSame( 400, $response->get_status(), (string) wp_json_encode( $response->get_data() ) );
		$this->assertSame( 1, $this->count_rows( 'movements' ) );
	}

	/**
	 * Invalid movement payloads.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function invalid_movement_provider(): array {
		return array(
			'zero amount'        => array( array( 'type' => 'paid_in', 'amount' => '0.00', 'reason' => 'x' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'negative amount'    => array( array( 'type' => 'paid_out', 'amount' => '-1', 'reason' => 'x' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'too precise'        => array( array( 'type' => 'paid_in', 'amount' => '1.001', 'reason' => 'x' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'missing reason'     => array( array( 'type' => 'paid_in', 'amount' => '1' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'blank reason'       => array( array( 'type' => 'paid_in', 'amount' => '1', 'reason' => "  \n" ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'missing amount'     => array( array( 'type' => 'paid_in', 'reason' => 'x' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'order on paid in'   => array( array( 'type' => 'paid_in', 'amount' => '1', 'reason' => 'x', 'order_id' => 5 ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'unknown type'       => array( array( 'type' => 'opening_float', 'amount' => '1', 'reason' => 'x' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'no offset'          => array( array( 'type' => 'paid_in', 'amount' => '1', 'reason' => 'x', 'occurred_at' => '2026-09-25T12:00:00' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'sale with amount'   => array( array( 'type' => 'cash_sale', 'order_id' => 5, 'amount' => '1' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'sale without order' => array( array( 'type' => 'cash_sale' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'refund no refund'   => array( array( 'type' => 'cash_refund', 'order_id' => 5 ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'sale with time'     => array( array( 'type' => 'cash_sale', 'order_id' => 5, 'occurred_at' => '2026-09-25T12:00:00Z' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		);
	}

	/**
	 * @testdox Should record a cash sale from the order total and freeze it against later order edits.
	 */
	public function test_cash_sale_amount_is_derived_and_frozen(): void {
		$session_id = $this->open_session( array( 'opening_amount' => '50.00' ) )->get_data()['id'];
		$order      = $this->create_cash_order( '19.99' );

		$response = $this->record_movement(
			$session_id,
			array(
				'type'     => 'cash_sale',
				'order_id' => $order->get_id(),
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 201, $response->get_status(), (string) wp_json_encode( $data ) );
		$this->assertSame( '19.99', $data['amount'] );
		$this->assertSame( 'in', $data['direction'] );
		$this->assertSame( $order->get_id(), $data['order_id'] );
		$this->assertSame( '2026-09-25T09:30:00Z', $data['occurred_at'], 'occurred_at comes from the date paid' );

		$order->set_total( '99.00' );
		$order->save();

		$session = $this->request( 'GET', self::BASE . "/$session_id" )->get_data();
		$this->assertSame( '19.99', $session['cash_sales_total'] );
		$this->assertSame( '69.99', $session['expected_amount'] );
	}

	/**
	 * @testdox Should accept a cash order whose change due is zero.
	 */
	public function test_cash_sale_with_zero_change(): void {
		$session_id = $this->open_session()->get_data()['id'];
		$order      = $this->create_cash_order( '10.00', array( 'change' => '0' ) );

		$this->assertSame( 201, $this->record_sale( $session_id, $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should reject cash sale sources that are not recognized, paid, or in the session currency.
	 */
	public function test_cash_sale_source_validation(): void {
		$session_id = $this->open_session()->get_data()['id'];

		$card = $this->create_cash_order( '10.00', array( 'change' => null ) );
		$this->assert_error( $this->record_sale( $session_id, $card->get_id() ), 400, 'woocommerce_rest_cash_invalid_source' );

		$unpaid = $this->create_cash_order( '10.00', array( 'status' => 'pending' ) );
		$this->assert_error( $this->record_sale( $session_id, $unpaid->get_id() ), 400, 'woocommerce_rest_cash_invalid_source' );

		$usd = $this->create_cash_order( '10.00', array( 'currency' => 'USD' ) );
		$this->assert_error( $this->record_sale( $session_id, $usd->get_id() ), 400, 'woocommerce_rest_cash_currency_mismatch' );

		update_option( 'woocommerce_price_num_decimals', '3' );
		$precise = $this->create_cash_order( '10.005' );
		update_option( 'woocommerce_price_num_decimals', '2' );
		$this->assert_error( $this->record_sale( $session_id, $precise->get_id() ), 400, 'woocommerce_rest_cash_precision_mismatch' );

		$this->assert_error( $this->record_sale( $session_id, 987654 ), 400, 'woocommerce_rest_cash_invalid_source' );

		$refund = $this->create_refund( $this->create_cash_order( '10.00' ), '1.00' );
		$this->assert_error( $this->record_sale( $session_id, $refund->get_id() ), 400, 'woocommerce_rest_cash_invalid_source' );

		$this->assertSame( 1, $this->count_rows( 'movements' ) );
		$this->assertSame( 1, $this->request( 'GET', self::BASE . "/$session_id" )->get_data()['revision'] );
	}

	/**
	 * @testdox Should record a source once per site and report where it was recorded.
	 */
	public function test_source_is_recorded_once(): void {
		$first_session  = $this->open_session( array( 'device_id' => 'ipad-1' ) )->get_data()['id'];
		$second_session = $this->open_session( array( 'device_id' => 'ipad-2' ) )->get_data()['id'];
		$order          = $this->create_cash_order( '12.00' );

		$movement = $this->record_sale( $first_session, $order->get_id() )->get_data();

		$again = $this->record_sale( $first_session, $order->get_id() );
		$this->assert_error( $again, 409, 'woocommerce_rest_cash_source_already_recorded' );
		$this->assertSame( $first_session, $again->get_data()['data']['session_id'] );
		$this->assertSame( $movement['id'], $again->get_data()['data']['movement_id'] );

		$this->assert_error( $this->record_sale( $second_session, $order->get_id() ), 409, 'woocommerce_rest_cash_source_already_recorded' );
		$this->assertSame( '0.00', $this->request( 'GET', self::BASE . "/$second_session" )->get_data()['cash_sales_total'] );
	}

	/**
	 * @testdox Should record partial refunds as separate movements and full refunds of cash orders.
	 */
	public function test_partial_and_full_refunds(): void {
		$session_id = $this->open_session( array( 'opening_amount' => '100.00' ) )->get_data()['id'];
		$order      = $this->create_cash_order( '30.00' );
		$this->record_sale( $session_id, $order->get_id() );

		$first  = $this->create_refund( $order, '5.00' );
		$second = $this->create_refund( $order, '7.50' );

		$this->assertSame( 201, $this->record_refund( $session_id, $order->get_id(), $first->get_id() )->get_status() );
		$response = $this->record_refund( $session_id, $order->get_id(), $second->get_id() );
		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'out', $response->get_data()['direction'] );
		$this->assertSame( '7.50', $response->get_data()['amount'] );
		$this->assertSame( $second->get_id(), $response->get_data()['refund_id'] );

		$this->assert_error( $this->record_refund( $session_id, $order->get_id(), $first->get_id() ), 409, 'woocommerce_rest_cash_source_already_recorded' );

		$full_order = $this->create_cash_order( '20.00' );
		$full       = $this->create_refund( $full_order, '20.00' );
		$this->assertSame( 'refunded', wc_get_order( $full_order->get_id() )->get_status() );
		$this->assertSame( 201, $this->record_refund( $session_id, $full_order->get_id(), $full->get_id() )->get_status() );

		$session = $this->request( 'GET', self::BASE . "/$session_id" )->get_data();
		$this->assertSame( '32.50', $session['cash_refunds_total'] );
		$this->assertSame( '97.50', $session['expected_amount'] );
	}

	/**
	 * @testdox Should reject refunds that do not belong to the given order or are not cash.
	 */
	public function test_refund_ownership(): void {
		$session_id = $this->open_session()->get_data()['id'];
		$order      = $this->create_cash_order( '30.00' );
		$other      = $this->create_cash_order( '30.00' );
		$refund     = $this->create_refund( $other, '5.00' );

		$this->assert_error( $this->record_refund( $session_id, $order->get_id(), $refund->get_id() ), 400, 'woocommerce_rest_cash_invalid_source' );
		$this->assert_error( $this->record_refund( $session_id, $order->get_id(), $order->get_id() ), 400, 'woocommerce_rest_cash_invalid_source' );

		$card        = $this->create_cash_order( '30.00', array( 'change' => null ) );
		$card_refund = $this->create_refund( $card, '5.00' );
		$this->assert_error( $this->record_refund( $session_id, $card->get_id(), $card_refund->get_id() ), 400, 'woocommerce_rest_cash_invalid_source' );
	}

	/**
	 * @testdox Should record a refund in the targeted open session even when the sale is in a closed session.
	 */
	public function test_refund_goes_to_targeted_session(): void {
		$old_session = $this->open_session( array( 'device_id' => 'ipad-1' ) )->get_data()['id'];
		$order       = $this->create_cash_order( '30.00' );
		$this->record_sale( $old_session, $order->get_id() );
		$this->close_session( $old_session, 2, '30.00' );

		$new_session = $this->open_session( array( 'device_id' => 'ipad-1' ) )->get_data()['id'];
		$refund      = $this->create_refund( $order, '10.00' );

		$this->assertSame( 201, $this->record_refund( $new_session, $order->get_id(), $refund->get_id() )->get_status() );
		$this->assertSame( '10.00', $this->request( 'GET', self::BASE . "/$new_session" )->get_data()['cash_refunds_total'] );
		$old = $this->request( 'GET', self::BASE . "/$old_session" )->get_data();
		$this->assertSame( '0.00', $old['cash_refunds_total'] );
		$this->assertSame( '0.00', $old['variance'] );
	}

	/**
	 * @testdox Should replay movement retries, reject changed payloads, and replay after close.
	 */
	public function test_movement_retries(): void {
		$session_id = $this->open_session()->get_data()['id'];
		$body       = array(
			'request_id' => wp_generate_uuid4(),
			'type'       => 'paid_in',
			'amount'     => '5.00',
			'reason'     => 'Float top-up',
		);

		$first = $this->request( 'POST', self::BASE . "/$session_id/movements", $body );
		$retry = $this->request( 'POST', self::BASE . "/$session_id/movements", array_merge( $body, array( 'amount' => '5' ) ) );
		$this->assertSame( 201, $first->get_status() );
		$this->assertSame( 200, $retry->get_status() );
		$this->assertSame( $first->get_data(), $retry->get_data() );

		$conflict = $this->request( 'POST', self::BASE . "/$session_id/movements", array_merge( $body, array( 'amount' => '6.00' ) ) );
		$this->assert_error( $conflict, 409, 'woocommerce_rest_cash_request_conflict' );

		$this->close_session( $session_id, 2, '5.00' );
		$after = $this->request( 'POST', self::BASE . "/$session_id/movements", $body );
		$this->assertSame( 200, $after->get_status() );
		$this->assertSame( $first->get_data(), $after->get_data() );

		$new = $this->request( 'POST', self::BASE . "/$session_id/movements", array_merge( $body, array( 'request_id' => wp_generate_uuid4() ) ) );
		$this->assert_error( $new, 409, 'woocommerce_rest_cash_session_closed' );
		$this->assertSame( 2, $this->count_rows( 'movements' ) );
	}

	/**
	 * @testdox Should list movements oldest first with pagination headers.
	 */
	public function test_list_movements(): void {
		$session_id = $this->open_session()->get_data()['id'];
		for ( $i = 1; $i <= 3; $i++ ) {
			$this->record_movement(
				$session_id,
				array(
					'type'   => 'paid_in',
					'amount' => "$i",
					'reason' => "r$i",
				)
			);
		}

		$page = $this->request(
			'GET',
			self::BASE . "/$session_id/movements",
			array(
				'per_page' => 3,
				'page'     => 1,
			)
		);
		$this->assertSame( array( 'opening_float', 'paid_in', 'paid_in' ), array_column( $page->get_data(), 'type' ) );
		$this->assertSame( array( '1.00', '2.00' ), array_slice( array_column( $page->get_data(), 'amount' ), 1 ) );
		$this->assertSame( '4', $page->get_headers()['X-WP-Total'] );
		$this->assertSame( '2', $page->get_headers()['X-WP-TotalPages'] );
	}

	/**
	 * @testdox Should close with the cashier count and compute expected cash and a signed variance.
	 *
	 * @testWith ["95.00", "95.00", "0.00"]
	 *           ["90.5", "90.50", "-4.50"]
	 *           ["100.00", "100.00", "5.00"]
	 *           ["0", "0.00", "-95.00"]
	 *
	 * @param string $counted         Test value.
	 * @param string $formatted_count Test value.
	 * @param string $variance        Test value.
	 */
	public function test_close_computes_variance( string $counted, string $formatted_count, string $variance ): void {
		$session_id = $this->open_session( array( 'opening_amount' => '100.00' ) )->get_data()['id'];
		$this->record_movement(
			$session_id,
			array(
				'type'   => 'paid_out',
				'amount' => '5.00',
				'reason' => 'Coffee',
			)
		);

		$response = $this->request(
			'POST',
			self::BASE . "/$session_id/close",
			array(
				'request_id'        => wp_generate_uuid4(),
				'expected_revision' => 2,
				'counted_amount'    => $counted,
				'note'              => 'Shift A',
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), (string) wp_json_encode( $data ) );
		$this->assertSame( 'closed', $data['status'] );
		$this->assertSame( '95.00', $data['expected_amount'] );
		$this->assertSame( $formatted_count, $data['counted_amount'] );
		$this->assertSame( $variance, $data['variance'] );
		$this->assertSame( 'Shift A', $data['note'] );
		$this->assertSame( $this->manager_id, $data['closed_by'] );
		$this->assertSame( 'Store Manager', $data['closed_by_name'] );
		$this->assertMatchesRegularExpression( '/Z$/', $data['date_closed_gmt'] );
	}

	/**
	 * @testdox Should reject a close at a stale revision without closing.
	 */
	public function test_close_stale_revision(): void {
		$session_id = $this->open_session()->get_data()['id'];
		$this->record_movement(
			$session_id,
			array(
				'type'   => 'paid_in',
				'amount' => '1',
				'reason' => 'x',
			)
		);

		$response = $this->close_session( $session_id, 1, '1.00' );

		$this->assert_error( $response, 409, 'woocommerce_rest_cash_session_revision_conflict' );
		$this->assertSame( 2, $response->get_data()['data']['current_revision'] );
		$this->assertSame( $session_id, $response->get_data()['data']['session_id'] );
		$this->assertSame( 'open', $this->request( 'GET', self::BASE . "/$session_id" )->get_data()['status'] );
	}

	/**
	 * @testdox Should replay a close, reject a changed close payload, and reject a second close.
	 */
	public function test_close_retries(): void {
		$session_id = $this->open_session()->get_data()['id'];
		$body       = array(
			'request_id'        => wp_generate_uuid4(),
			'expected_revision' => 1,
			'counted_amount'    => '0.00',
		);

		$first = $this->request( 'POST', self::BASE . "/$session_id/close", $body );
		$retry = $this->request( 'POST', self::BASE . "/$session_id/close", $body );
		$this->assertSame( 200, $retry->get_status() );
		$this->assertSame( $first->get_data(), $retry->get_data() );

		$this->assert_error(
			$this->request( 'POST', self::BASE . "/$session_id/close", array_merge( $body, array( 'counted_amount' => '1.00' ) ) ),
			409,
			'woocommerce_rest_cash_request_conflict'
		);
		$this->assert_error(
			$this->request( 'POST', self::BASE . "/$session_id/close", array_merge( $body, array( 'request_id' => wp_generate_uuid4() ) ) ),
			409,
			'woocommerce_rest_cash_session_closed'
		);
	}

	/**
	 * @testdox Should reject a counted amount that is negative or too precise.
	 */
	public function test_close_validates_counted_amount(): void {
		$session_id = $this->open_session()->get_data()['id'];

		$this->assertSame( 400, $this->close_session( $session_id, 1, '-1.00' )->get_status() );
		$this->assertSame( 400, $this->close_session( $session_id, 1, '1.001' )->get_status() );
		$this->assertSame( 'open', $this->request( 'GET', self::BASE . "/$session_id" )->get_data()['status'] );
	}

	/**
	 * @testdox Should keep closed session data immutable.
	 */
	public function test_closed_session_is_immutable(): void {
		$session_id = $this->open_session(
			array(
				'opening_amount' => '10.00',
				'drawer_id'      => 'Till',
			)
		)->get_data()['id'];
		$closed     = $this->close_session( $session_id, 1, '9.00' )->get_data();

		$order = $this->create_cash_order( '5.00' );
		$this->assert_error( $this->record_sale( $session_id, $order->get_id() ), 409, 'woocommerce_rest_cash_session_closed' );
		$this->assert_error(
			$this->record_drawer_event( $session_id, array() ),
			409,
			'woocommerce_rest_cash_session_closed'
		);

		update_option( 'woocommerce_price_num_decimals', '3' );
		$this->assertSame( $closed, $this->request( 'GET', self::BASE . "/$session_id" )->get_data() );
		$this->assertSame( '-1.00', $closed['variance'] );
	}

	/**
	 * @testdox Should record drawer events without changing totals or revision.
	 */
	public function test_drawer_events_do_not_touch_accounting(): void {
		$session_id = $this->open_session(
			array(
				'opening_amount' => '10.00',
				'drawer_id'      => 'Front counter',
			)
		)->get_data()['id'];
		$before     = $this->request( 'GET', self::BASE . "/$session_id" )->get_data();

		$correlation = wp_generate_uuid4();
		$requested   = $this->record_drawer_event(
			$session_id,
			array(
				'type'           => 'open_requested',
				'reason'         => 'no_sale',
				'correlation_id' => $correlation,
			)
		);
		$opened      = $this->record_drawer_event(
			$session_id,
			array(
				'type'           => 'opened',
				'reason'         => 'no_sale',
				'correlation_id' => $correlation,
				'drawer_id'      => ' FRONT counter',
				'occurred_at'    => '2026-09-25T15:00:05+03:00',
			)
		);

		$this->assertSame( 201, $requested->get_status(), (string) wp_json_encode( $requested->get_data() ) );
		$this->assertSame( 'Front counter', $requested->get_data()['drawer_id'] );
		$this->assertSame( 201, $opened->get_status() );
		$this->assertSame( 'Front counter', $opened->get_data()['drawer_id'], 'Events keep the session spelling' );
		$this->assertSame( '2026-09-25T12:00:05Z', $opened->get_data()['occurred_at'] );
		$this->assertSame( $correlation, $opened->get_data()['correlation_id'] );
		$this->assertSame( $this->manager_id, $opened->get_data()['created_by'] );
		$this->assertSame( $before, $this->request( 'GET', self::BASE . "/$session_id" )->get_data() );
		$this->assertSame( 1, $this->count_rows( 'movements' ) );

		$list = $this->request( 'GET', self::BASE . "/$session_id/drawer-events", array( 'per_page' => 1 ) );
		$this->assertSame( array( $requested->get_data()['id'] ), array_column( $list->get_data(), 'id' ) );
		$this->assertSame( '2', $list->get_headers()['X-WP-Total'] );
	}

	/**
	 * @testdox Should validate the drawer binding and the references of drawer events.
	 */
	public function test_drawer_event_validation(): void {
		$bound   = $this->open_session(
			array(
				'device_id' => 'ipad-1',
				'drawer_id' => 'Front counter',
			)
		)->get_data()['id'];
		$unbound = $this->open_session( array( 'device_id' => 'ipad-2' ) )->get_data()['id'];

		$this->assert_error( $this->record_drawer_event( $bound, array( 'drawer_id' => 'Back office' ) ), 400, 'woocommerce_rest_cash_drawer_mismatch' );
		$this->assert_error( $this->record_drawer_event( $unbound, array() ), 400, 'woocommerce_rest_cash_drawer_required' );
		$this->assert_error( $this->record_drawer_event( $unbound, array( 'drawer_id' => 'Front counter' ) ), 400, 'woocommerce_rest_cash_drawer_mismatch' );

		$other_movement = $this->request( 'GET', self::BASE . "/$unbound/movements" )->get_data()[0]['id'];
		$this->assertSame( 400, $this->record_drawer_event( $bound, array( 'movement_id' => $other_movement ) )->get_status() );

		$order  = $this->create_cash_order( '10.00' );
		$other  = $this->create_cash_order( '10.00' );
		$refund = $this->create_refund( $other, '1.00' );
		$this->assertSame(
			400,
			$this->record_drawer_event(
				$bound,
				array(
					'order_id'  => $order->get_id(),
					'refund_id' => $refund->get_id(),
				)
			)->get_status()
		);
		$this->assertSame( 400, $this->record_drawer_event( $bound, array( 'refund_id' => $refund->get_id() ) )->get_status() );
		$this->assertSame( 400, $this->record_drawer_event( $bound, array( 'type' => 'opened_maybe' ) )->get_status() );
		$this->assertSame( 400, $this->record_drawer_event( $bound, array( 'occurred_at' => null ) )->get_status() );
		$this->assertSame( 0, $this->count_rows( 'drawer_events' ) );
	}

	/**
	 * @testdox Should replay drawer event retries, including after close.
	 */
	public function test_drawer_event_retries(): void {
		$session_id = $this->open_session( array( 'drawer_id' => 'Till' ) )->get_data()['id'];
		$body       = array(
			'request_id'  => wp_generate_uuid4(),
			'type'        => 'open_failed',
			'reason'      => 'cash_sale',
			'occurred_at' => '2026-09-25T12:00:00Z',
		);

		$first = $this->request( 'POST', self::BASE . "/$session_id/drawer-events", $body );
		$this->close_session( $session_id, 1, '0' );
		$retry = $this->request( 'POST', self::BASE . "/$session_id/drawer-events", $body );

		$this->assertSame( 201, $first->get_status() );
		$this->assertSame( 200, $retry->get_status() );
		$this->assertSame( $first->get_data(), $retry->get_data() );
		$this->assert_error(
			$this->request( 'POST', self::BASE . "/$session_id/drawer-events", array_merge( $body, array( 'reason' => 'no_sale' ) ) ),
			409,
			'woocommerce_rest_cash_request_conflict'
		);
	}

	/**
	 * @testdox Should require authentication on every route, including retries.
	 */
	public function test_guest_is_rejected(): void {
		$body       = $this->open_body();
		$session_id = $this->request( 'POST', self::BASE, $body )->get_data()['id'];

		wp_set_current_user( 0 );

		$this->assertSame( 401, $this->request( 'POST', self::BASE, $body )->get_status(), 'A retry needs authentication too' );
		$this->assertSame( 401, $this->request( 'GET', self::BASE )->get_status() );
		$this->assertSame( 401, $this->request( 'GET', self::BASE . "/$session_id" )->get_status() );
		$this->assertSame( 401, $this->request( 'GET', self::BASE . "/$session_id/movements" )->get_status() );
		$this->assertSame( 401, $this->request( 'GET', self::BASE . "/$session_id/drawer-events" )->get_status() );
		$this->assertSame( 401, $this->close_session( $session_id, 1, '0' )->get_status() );
		$this->assertSame(
			401,
			$this->record_movement(
				$session_id,
				array(
					'type'   => 'paid_in',
					'amount' => '1',
					'reason' => 'x',
				)
			)->get_status()
		);
		$this->assertSame( 401, $this->record_drawer_event( $session_id, array() )->get_status() );
	}

	/**
	 * @testdox Should allow POS cashiers, refuse customers, and require refund rights for cash refunds.
	 */
	public function test_capabilities(): void {
		$order      = $this->create_cash_order( '10.00' );
		$refund     = $this->create_refund( $order, '2.00' );
		$session_id = $this->open_session()->get_data()['id'];

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'customer' ) ) );
		$this->assertSame( 403, $this->request( 'GET', self::BASE )->get_status() );
		$this->assertSame( 403, $this->open_session( array( 'device_id' => 'ipad-9' ) )->get_status() );

		$cashier = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $cashier )->add_cap( Capabilities::CAP_PROCESS_SALES );
		wp_set_current_user( $cashier );

		$this->assertSame( 200, $this->request( 'GET', self::BASE . "/$session_id" )->get_status() );
		$this->assertSame( 201, $this->open_session( array( 'device_id' => 'ipad-9' ) )->get_status() );
		$this->assertSame( 201, $this->record_sale( $session_id, $order->get_id() )->get_status() );
		$this->assertSame( 403, $this->record_refund( $session_id, $order->get_id(), $refund->get_id() )->get_status() );

		wp_get_current_user()->add_cap( Capabilities::CAP_ISSUE_REFUNDS );
		$this->assertSame( 201, $this->record_refund( $session_id, $order->get_id(), $refund->get_id() )->get_status() );
	}

	/**
	 * Build a valid open request body.
	 *
	 * @param array<string, mixed> $overrides Values to override.
	 * @return array<string, mixed>
	 */
	private function open_body( array $overrides = array() ): array {
		return array_merge(
			array(
				'request_id'     => wp_generate_uuid4(),
				'device_id'      => 'ipad-1',
				'opening_amount' => '0.00',
			),
			$overrides
		);
	}

	/**
	 * Open a session.
	 *
	 * @param array<string, mixed> $overrides Values to override.
	 * @return WP_REST_Response
	 */
	private function open_session( array $overrides = array() ): WP_REST_Response {
		return $this->request( 'POST', self::BASE, $this->open_body( $overrides ) );
	}

	/**
	 * Close a session.
	 *
	 * @param int    $session_id Session ID.
	 * @param int    $revision   Expected revision.
	 * @param string $counted    Counted amount.
	 * @return WP_REST_Response
	 */
	private function close_session( int $session_id, int $revision, string $counted ): WP_REST_Response {
		return $this->request(
			'POST',
			self::BASE . "/$session_id/close",
			array(
				'request_id'        => wp_generate_uuid4(),
				'expected_revision' => $revision,
				'counted_amount'    => $counted,
			)
		);
	}

	/**
	 * Record a movement with a fresh request ID.
	 *
	 * @param int                  $session_id Session ID.
	 * @param array<string, mixed> $body       Movement fields.
	 * @return WP_REST_Response
	 */
	private function record_movement( int $session_id, array $body ): WP_REST_Response {
		return $this->request( 'POST', self::BASE . "/$session_id/movements", array_merge( array( 'request_id' => wp_generate_uuid4() ), $body ) );
	}

	/**
	 * Record a cash sale.
	 *
	 * @param int $session_id Session ID.
	 * @param int $order_id   Order ID.
	 * @return WP_REST_Response
	 */
	private function record_sale( int $session_id, int $order_id ): WP_REST_Response {
		return $this->record_movement(
			$session_id,
			array(
				'type'     => 'cash_sale',
				'order_id' => $order_id,
			)
		);
	}

	/**
	 * Record a cash refund.
	 *
	 * @param int $session_id Session ID.
	 * @param int $order_id   Order ID.
	 * @param int $refund_id  Refund ID.
	 * @return WP_REST_Response
	 */
	private function record_refund( int $session_id, int $order_id, int $refund_id ): WP_REST_Response {
		return $this->record_movement(
			$session_id,
			array(
				'type'      => 'cash_refund',
				'order_id'  => $order_id,
				'refund_id' => $refund_id,
			)
		);
	}

	/**
	 * Record a drawer event with defaults.
	 *
	 * @param int                  $session_id Session ID.
	 * @param array<string, mixed> $overrides  Values to override; null removes a default.
	 * @return WP_REST_Response
	 */
	private function record_drawer_event( int $session_id, array $overrides ): WP_REST_Response {
		$body = array_merge(
			array(
				'request_id'  => wp_generate_uuid4(),
				'type'        => 'open_requested',
				'reason'      => 'no_sale',
				'occurred_at' => '2026-09-25T12:00:00Z',
			),
			$overrides
		);
		return $this->request( 'POST', self::BASE . "/$session_id/drawer-events", array_filter( $body, fn( $value ) => null !== $value ) );
	}

	/**
	 * Create a paid POS cash order.
	 *
	 * @param string               $total   Order total.
	 * @param array<string, mixed> $options status, currency and change (null for a non-cash order).
	 * @return WC_Order
	 */
	private function create_cash_order( string $total, array $options = array() ): WC_Order {
		$options = array_merge(
			array(
				'status'   => 'completed',
				'currency' => 'EUR',
				'change'   => '1.00',
			),
			$options
		);

		$order = wc_create_order();
		$order->set_created_via( 'pos-rest-api' );
		$order->set_currency( $options['currency'] );
		$order->set_payment_method( 'cod' );
		$order->set_total( $total );
		$order->set_date_paid( '2026-09-25T09:30:00+00:00' );
		if ( null !== $options['change'] ) {
			$order->update_meta_data( '_cash_change_amount', $options['change'] );
		}
		$order->set_status( $options['status'] );
		$order->save();

		return $order;
	}

	/**
	 * Create a refund for an order.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $amount Refund amount.
	 * @return WC_Order_Refund
	 */
	private function create_refund( WC_Order $order, string $amount ): WC_Order_Refund {
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => $amount,
			)
		);
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		return $refund;
	}

	/**
	 * Dispatch a REST request.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $path   Route.
	 * @param array<string, mixed> $params Query parameters for GET, JSON body otherwise.
	 * @return WP_REST_Response
	 */
	private function request( string $method, string $path, array $params = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $path );
		if ( 'GET' === $method ) {
			$request->set_query_params( $params );
		} else {
			$request->set_header( 'content-type', 'application/json' );
			$request->set_body( (string) wp_json_encode( $params ) );
		}
		return $this->server->dispatch( $request );
	}

	/**
	 * Assert an error response.
	 *
	 * @param WP_REST_Response $response Response.
	 * @param int              $status   Expected HTTP status.
	 * @param string           $code     Expected error code.
	 */
	private function assert_error( WP_REST_Response $response, int $status, string $code ): void {
		$this->assertSame( $status, $response->get_status(), (string) wp_json_encode( $response->get_data() ) );
		$this->assertSame( $code, $response->get_data()['code'] );
	}

	/**
	 * Count rows in one of the cash tables.
	 *
	 * @param string $table One of sessions, movements, drawer_events.
	 * @return int
	 */
	private function count_rows( string $table ): int {
		global $wpdb;
		$data_store = wc_get_container()->get( CashSessionsDataStore::class );
		$name       = array(
			'sessions'      => $data_store->get_sessions_table(),
			'movements'     => $data_store->get_movements_table(),
			'drawer_events' => $data_store->get_drawer_events_table(),
		)[ $table ];
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $name" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
