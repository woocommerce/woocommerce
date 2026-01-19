<?php
/**
 * Cart Fraud Protection Tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Internal\FraudProtection\BlockedSessionNotice;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Cart Fraud Protection Tests.
 *
 * Tests that cart modification routes are blocked when a session is blocked by fraud protection.
 * All cart routes extend AbstractCartRoute which checks session status before processing mutations.
 */
class CartFraudProtection extends ControllerTestCase {

	/**
	 * Test product.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Mock FraudProtectionController.
	 *
	 * @var FraudProtectionController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $fraud_controller_mock;

	/**
	 * Mock SessionClearanceManager.
	 *
	 * @var SessionClearanceManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $session_manager_mock;

	/**
	 * Mock BlockedSessionNotice.
	 *
	 * @var BlockedSessionNotice|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $blocked_notice_mock;

	/**
	 * Setup test product data.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures      = new FixtureData();
		$this->product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);

		wc_empty_cart();

		$this->fraud_controller_mock = $this->createMock( FraudProtectionController::class );
		$this->session_manager_mock  = $this->createMock( SessionClearanceManager::class );
		$this->blocked_notice_mock   = $this->createMock( BlockedSessionNotice::class );

		wc_get_container()->replace( FraudProtectionController::class, $this->fraud_controller_mock );
		wc_get_container()->replace( SessionClearanceManager::class, $this->session_manager_mock );
		wc_get_container()->replace( BlockedSessionNotice::class, $this->blocked_notice_mock );
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		wc_get_container()->reset_replacement( FraudProtectionController::class );
		wc_get_container()->reset_replacement( SessionClearanceManager::class );
		wc_get_container()->reset_replacement( BlockedSessionNotice::class );

		parent::tearDown();
	}

	/**
	 * @testdox Cart mutations return 403 with blocked message when session is blocked.
	 */
	public function test_cart_mutations_blocked_when_session_blocked(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( true );
		$this->session_manager_mock->method( 'is_session_blocked' )->willReturn( true );
		$this->blocked_notice_mock->method( 'get_message_plaintext' )->willReturn( 'Session blocked message' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->product->get_id(),
				'quantity' => 1,
			)
		);

		$this->assertApiResponse(
			$request,
			403,
			array(
				'code'    => 'woocommerce_rest_cart_error',
				'message' => 'Session blocked message',
			)
		);
	}

	/**
	 * @testdox Cart mutations succeed when session is allowed.
	 */
	public function test_cart_mutations_allowed_when_session_allowed(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( true );
		$this->session_manager_mock->method( 'is_session_blocked' )->willReturn( false );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->product->get_id(),
				'quantity' => 1,
			)
		);

		$this->assertApiResponse( $request, 201 );
	}

	/**
	 * @testdox Cart mutations succeed when fraud protection is disabled.
	 */
	public function test_cart_mutations_allowed_when_feature_disabled(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( false );
		$this->session_manager_mock->expects( $this->never() )->method( 'is_session_blocked' );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->product->get_id(),
				'quantity' => 1,
			)
		);

		$this->assertApiResponse( $request, 201 );
	}

	/**
	 * @testdox GET requests are not blocked (read-only).
	 */
	public function test_get_requests_allowed_when_session_blocked(): void {
		$this->fraud_controller_mock->method( 'feature_is_enabled' )->willReturn( true );
		$this->session_manager_mock->method( 'is_session_blocked' )->willReturn( true );

		$this->assertApiResponse( '/wc/store/v1/cart', 200 );
	}
}
