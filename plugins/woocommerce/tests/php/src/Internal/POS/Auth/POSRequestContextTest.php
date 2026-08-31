<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Auth;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use WC_Unit_Test_Case;

/**
 * Tests for POSRequestContext — the POS-originated request detector.
 */
class POSRequestContextTest extends WC_Unit_Test_Case {

	/**
	 * Saved $_SERVER keys to restore in tearDown.
	 *
	 * @var array<string, mixed>
	 */
	private array $server_backup = array();

	/**
	 * Saved rest_route query var.
	 *
	 * @var mixed
	 */
	private $rest_route_backup;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}

		$this->server_backup     = $_SERVER;
		$this->rest_route_backup = $GLOBALS['wp']->query_vars['rest_route'] ?? null;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_SERVER = $this->server_backup;
		if ( null === $this->rest_route_backup ) {
			unset( $GLOBALS['wp']->query_vars['rest_route'] );
		} else {
			$GLOBALS['wp']->query_vars['rest_route'] = $this->rest_route_backup;
		}
		parent::tearDown();
	}

	/**
	 * Build a SUT with the feature flag stubbed.
	 *
	 * @param bool $staff_enabled Whether the point_of_sale_staff flag is on.
	 * @return POSRequestContext
	 */
	private function make_sut( bool $staff_enabled = true ): POSRequestContext {
		$features = $this->createMock( FeaturesController::class );
		$features->method( 'feature_is_enabled' )->willReturnMap(
			array(
				array( 'point_of_sale_staff', $staff_enabled ),
			)
		);

		$sut = new POSRequestContext();
		$sut->init( $features );
		return $sut;
	}

	/**
	 * Arrange the current request: route, method, and headers.
	 *
	 * @param string                $route   REST route (e.g. /wc/v3/orders).
	 * @param string                $method  HTTP method.
	 * @param array<string, string> $headers Map of HTTP_* server keys to values.
	 */
	private function arrange_request( string $route, string $method = 'POST', array $headers = array() ): void {
		$GLOBALS['wp']->query_vars['rest_route'] = $route;
		$_SERVER['REQUEST_METHOD']               = $method;
		foreach ( $headers as $key => $value ) {
			$_SERVER[ $key ] = $value;
		}
	}

	/**
	 * The full POS headers: staff-id credential (no PIN check yet) + the POS-request marker.
	 *
	 * @param int $staff_id Staff user id.
	 * @return array<string, string>
	 */
	private function pos_headers( int $staff_id = 42 ): array {
		return array(
			'HTTP_X_WC_POS_STAFF_ID' => (string) $staff_id,
			'HTTP_X_WC_POS_REQUEST'  => '1',
		);
	}

	/**
	 * @testdox An allowlisted POST /wc/v3/orders with the POS headers is a POS-originated order create.
	 */
	public function test_order_create_is_pos_request(): void {
		$this->arrange_request( '/wc/v3/orders', 'POST', $this->pos_headers() );

		$sut = $this->make_sut();

		$this->assertTrue( $sut->is_pos_request() );
		$this->assertSame( 42, $sut->get_staff_id() );
		$this->assertSame( POSRequestContext::INTENT_ORDER_CREATE, $sut->get_intent() );
	}

	/**
	 * @testdox A wc/v3 refund route resolves the refund.create intent.
	 */
	public function test_refund_create_intent(): void {
		$this->arrange_request( '/wc/v3/orders/123/refunds', 'POST', $this->pos_headers() );

		$sut = $this->make_sut();

		$this->assertTrue( $sut->is_pos_request() );
		$this->assertSame( POSRequestContext::INTENT_REFUND_CREATE, $sut->get_intent() );
	}

	/**
	 * @testdox A wc/v3 write without the POS marker header is not POS-originated.
	 */
	public function test_requires_marker_header(): void {
		$this->arrange_request( '/wc/v3/orders', 'POST', array( 'HTTP_X_WC_POS_STAFF_ID' => '42' ) );

		$this->assertFalse( $this->make_sut()->is_pos_request(), 'Missing X-WC-POS-Request must not be POS-originated' );
	}

	/**
	 * @testdox A request missing the staff-id header is not POS-originated.
	 */
	public function test_requires_staff_id_header(): void {
		$this->arrange_request( '/wc/v3/orders', 'POST', array( 'HTTP_X_WC_POS_REQUEST' => '1' ) );

		$this->assertFalse( $this->make_sut()->is_pos_request() );
	}

	/**
	 * @testdox A non-allowlisted wc/v3 route is never POS-originated even with the POS headers.
	 */
	public function test_non_allowlisted_v3_route_is_not_pos(): void {
		$this->arrange_request( '/wc/v3/products', 'POST', $this->pos_headers() );

		$this->assertFalse( $this->make_sut()->is_pos_request(), 'Products endpoint is not an attributable POS write' );
	}

	/**
	 * @testdox A POS-namespace read route is not POS-originated (reads need no swap).
	 */
	public function test_pos_namespace_read_is_not_pos(): void {
		$this->arrange_request( '/wc/pos/v1/staff', 'GET', $this->pos_headers() );

		$this->assertFalse(
			$this->make_sut()->is_pos_request(),
			'The /wc/pos/v1 read endpoints run as the device admin and are not swap targets'
		);
	}

	/**
	 * @testdox A Store API route is never POS-originated.
	 */
	public function test_store_api_route_is_never_pos(): void {
		$this->arrange_request( '/wc/store/v1/checkout', 'POST', $this->pos_headers() );

		$this->assertFalse( $this->make_sut()->is_pos_request(), 'Storefront/guest traffic must never be treated as POS' );
	}

	/**
	 * @testdox Detection is off when the point_of_sale_staff flag is disabled.
	 */
	public function test_disabled_flag_is_not_pos(): void {
		$this->arrange_request( '/wc/v3/orders', 'POST', $this->pos_headers() );

		$this->assertFalse( $this->make_sut( false )->is_pos_request(), 'Flag off must disable detection' );
	}

	/**
	 * @testdox A non-numeric staff-id header resolves to no staff and is not POS-originated.
	 */
	public function test_invalid_staff_id_is_not_pos(): void {
		$this->arrange_request(
			'/wc/v3/orders',
			'POST',
			array(
				'HTTP_X_WC_POS_STAFF_ID' => 'abc',
				'HTTP_X_WC_POS_REQUEST'  => '1',
			)
		);

		$this->assertFalse( $this->make_sut()->is_pos_request() );
	}

	/**
	 * @testdox A negative computed before the route is parsed is not memoized.
	 */
	public function test_pre_route_negative_is_not_memoized(): void {
		// Simulate an early wp_get_current_user() (before REST dispatch parsed the route): the
		// credentials are present but no route is resolvable yet.
		unset( $GLOBALS['wp']->query_vars['rest_route'] );
		$_SERVER['REQUEST_METHOD'] = 'POST';
		foreach ( $this->pos_headers() as $key => $value ) {
			$_SERVER[ $key ] = $value;
		}

		$sut = $this->make_sut();
		$this->assertFalse( $sut->is_pos_request(), 'With no route yet, detection must report a transient false' );

		// REST dispatch now sets the route; the same instance must re-evaluate, not return the
		// stale false (this is the bug that 403'd the live swap).
		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/v3/orders';
		$this->assertTrue( $sut->is_pos_request(), 'Once the route exists the same instance must re-evaluate to true' );
	}

	/**
	 * @testdox get_initiator_id reads the X-WC-POS-Initiator-Id header on a POS request.
	 */
	public function test_get_initiator_id_reads_header(): void {
		$this->arrange_request( '/wc/v3/orders', 'POST', $this->pos_headers() + array( 'HTTP_X_WC_POS_INITIATOR_ID' => '7' ) );

		$this->assertSame( 7, $this->make_sut()->get_initiator_id() );
	}

	/**
	 * @testdox get_initiator_id is null on a POS request when the header is absent.
	 */
	public function test_get_initiator_id_absent_is_null(): void {
		$this->arrange_request( '/wc/v3/orders', 'POST', $this->pos_headers() );

		$this->assertNull( $this->make_sut()->get_initiator_id() );
	}

	/**
	 * @testdox get_initiator_id is null on a non-POS request even if the header is present.
	 */
	public function test_get_initiator_id_is_null_on_non_pos_request(): void {
		// Only the initiator header, no POS request shape: it's attribution context for a POS request,
		// so there's no initiator to report outside one.
		$this->arrange_request( '/wc/v3/orders', 'POST', array( 'HTTP_X_WC_POS_INITIATOR_ID' => '7' ) );

		$this->assertNull( $this->make_sut()->get_initiator_id() );
	}
}
