<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Suggestions\Incentives;

use Automattic\WooCommerce\Internal\Admin\Suggestions\Incentives\Incentive;
use Automattic\WooCommerce\Internal\Admin\Suggestions\Incentives\WooPayments;
use WC_Unit_Test_Case;

/**
 * WooPayments incentive provider test.
 *
 * @class WooPayments
 */
class WooPaymentsTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var WooPayments
	 */
	protected $sut;

	/**
	 * The incentive's suggestion ID.
	 *
	 * @var string
	 */
	protected string $suggestion_id;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * Response mock.
	 *
	 * @var callable
	 */
	private $response_mock_ref;

	/**
	 * Error response mock.
	 *
	 * @var callable
	 */
	private $error_response_mock_ref;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->suggestion_id = 'suggestion1';

		$this->sut = $this->getMockBuilder( WooPayments::class )
			->setConstructorArgs( array( $this->suggestion_id ) )
			->onlyMethods( array( 'is_extension_active' ) )
			->getMock();

		// Mock the response from the API.
		$this->response_mock_ref = function ( $preempt, $parsed_args, $url ) {
			if ( str_contains( $url, 'https://public-api.wordpress.com/wpcom/v2/wcpay/incentives' ) ) {
				return array(
					'success'  => true,
					'body'     => wp_json_encode(
						array(
							array(
								'id'        => 'incentive1',
								'promo_id'  => 'promo_id',
								'type'      => 'type1',
								'something' => 'else',
							),
							array(), // Invalid empty incentive.
							array(
								'id' => 'id', // Invalid incentive that is missing promo ID and type.
							),
							array(
								'id'        => 'incentive2',
								'promo_id'  => 'promo_id',
								'type'      => 'type2',
								'something' => 'else',
							),
							array(
								'type' => 'type', // Invalid incentive that is missing ID and promo ID.
							),
							array(
								'id'       => 'incentive3',
								'promo_id' => 'promo_id', // Invalid incentive that is missing type.
							),
						)
					),
					'response' => array(
						'code' => 200,
					),
				);
			}

			return $preempt;
		};

		$this->error_response_mock_ref = function ( $preempt, $parsed_args, $url ) {
			if ( str_contains( $url, 'https://public-api.wordpress.com/wpcom/v2/wcpay/incentives' ) ) {
				return new \WP_Error( 'http_request_failed', 'Error.' );
			}

			return $preempt;
		};

		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );

		$this->sut->clear_cache();
	}

	/**
	 * Test getting all incentives caches remote response.
	 */
	public function test_get_all_caches_remote_response() {
		// Arrange.
		add_filter( 'pre_http_request', $this->response_mock_ref, 10, 3 );

		// Act.
		$result = $this->sut->get_all( 'US' );

		// Assert.
		$this->assertCount( 2, $result );
		$this->assertSame( 'incentive1', $result[0]['id'] );
		$this->assertSame( 'incentive2', $result[1]['id'] );

		// Test that the memo is used.
		// Arrange.
		// No further requests should be made.
		add_filter( 'pre_http_request', fn() => $this->fail( 'wp_remote_get should not be called' ), 99, 3 );

		// Act.
		$result = $this->sut->get_all( 'US' );

		// Assert.
		$this->assertCount( 2, $result );
		$this->assertSame( 'incentive1', $result[0]['id'] );
		$this->assertSame( 'incentive2', $result[1]['id'] );

		// Test that the DB cache is used.
		// Arrange.
		// Remove the request filter to test that a new request should return the cached data.
		remove_filter( 'pre_http_request', $this->response_mock_ref );
		$this->sut->reset_memo();

		// Act.
		$result = $this->sut->get_all( 'US' );

		// Assert.
		$this->assertCount( 2, $result );
	}

	/**
	 * Test getting all incentives caches remote response error.
	 */
	public function test_get_all_caches_error() {
		// Arrange.
		remove_filter( 'pre_http_request', $this->response_mock_ref );
		add_filter( 'pre_http_request', $this->error_response_mock_ref, 10, 3 );

		// Act.
		$result = $this->sut->get_all( 'US' );

		// Assert.
		$this->assertCount( 0, $result );

		// Test that the memo is used.
		// Arrange.
		// No further requests should be made.
		add_filter( 'pre_http_request', fn() => $this->fail( 'wp_remote_get should not be called' ), 99, 3 );

		// Act.
		$result = $this->sut->get_all( 'US' );

		// Assert.
		$this->assertCount( 0, $result );

		// Test that the DB cache is used, even for errors.
		// Arrange.
		// Remove the request filter to test that a new request should return the cached data.
		remove_filter( 'pre_http_request', $this->error_response_mock_ref );
		$this->sut->reset_memo();

		// Act.
		$result = $this->sut->get_all( 'US' );

		// Assert.
		$this->assertCount( 0, $result );
	}

	/**
	 * Test is_visible skips extension active check.
	 */
	public function test_is_visible_skips_extension_active_check() {
		// Arrange.
		$this->sut
			->expects( $this->never() )
			->method( 'is_extension_active' );

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array( 'manage_woocommerce' => true );
		add_filter( 'user_has_cap', $filter_callback );

		add_filter( 'pre_http_request', $this->response_mock_ref, 10, 3 );

		// Act.
		$result = $this->sut->is_visible( 'incentive1', 'US', true );

		// Assert.
		$this->assertTrue( $result );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test is_visible when WooPayments is active and has no account data.
	 */
	public function test_is_visible_with_extension_active_and_no_account_data() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'is_extension_active' )
			->willReturn( true );

		add_filter( 'pre_http_request', $this->response_mock_ref, 10, 3 );

		// Act.
		$result = $this->sut->is_visible( 'incentive1', 'US' );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test is_visible when WooPayments is active and has account data.
	 */
	public function test_is_visible_with_extension_active_and_has_account_data() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'is_extension_active' )
			->willReturn( true );

		add_filter( 'pre_http_request', $this->response_mock_ref, 10, 3 );

		update_option( 'wcpay_account_data', array( 'data' => array( 'account_id' => '123' ) ) );

		// Act.
		$result = $this->sut->is_visible( 'incentive1', 'US' );

		// Assert.
		$this->assertFalse( $result );

		// Clean up.
		delete_option( 'wcpay_account_data' );
	}
}
