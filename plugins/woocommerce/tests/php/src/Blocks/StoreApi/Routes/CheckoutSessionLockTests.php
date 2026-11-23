<?php
/**
 * Checkout session lock tests.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\Checkout as CheckoutRoute;
use Automattic\WooCommerce\StoreApi\Utilities\SessionConcurrencyGuard;

/**
 * Tests for checkout state after order-lock contention.
 */
class CheckoutSessionLockTests extends \WC_Unit_Test_Case {

	/**
	 * @testdox Checkout retains the active session baseline when another handler captures state.
	 */
	public function test_checkout_rejects_changed_persisted_session(): void {
		$sut              = ( new \ReflectionClass( CheckoutRoute::class ) )->newInstanceWithoutConstructor();
		$assert_unchanged = new \ReflectionMethod( CheckoutRoute::class, 'throw_if_persisted_session_changed' );
		$assert_unchanged->setAccessible( true );

		$original_session = WC()->session;
		$session          = new class() extends \WC_Session_Handler {
			/**
			 * Simulated persisted session data.
			 *
			 * @var array
			 */
			public $persisted_data = array( 'version' => 'external' );

			/**
			 * Get the simulated persisted session data.
			 *
			 * @return array
			 */
			public function get_session_data_from_storage(): array {
				return $this->persisted_data;
			}
		};
		WC()->session     = $session;
		SessionConcurrencyGuard::capture( $session, array( 'version' => 'loaded' ) );
		SessionConcurrencyGuard::capture( new \WC_Session_Handler(), array( 'version' => 'temporary-handler' ) );

		try {
			try {
				$assert_unchanged->invoke( $sut );
				$this->fail( 'Checkout should reject a session changed by another request.' );
			} catch ( RouteException $error ) {
				$this->assertSame( 'woocommerce_rest_checkout_session_changed', $error->getErrorCode() );
				$this->assertSame( 409, $error->getCode() );
			}
		} finally {
			WC()->session = $original_session;
		}
	}

	/**
	 * @testdox Checkout allows current-request changes when persisted session data is unchanged.
	 */
	public function test_checkout_allows_unchanged_persisted_session(): void {
		$sut              = ( new \ReflectionClass( CheckoutRoute::class ) )->newInstanceWithoutConstructor();
		$assert_unchanged = new \ReflectionMethod( CheckoutRoute::class, 'throw_if_persisted_session_changed' );
		$assert_unchanged->setAccessible( true );

		$original_session = WC()->session;
		$session          = new class() extends \WC_Session_Handler {
			/**
			 * Get the unchanged simulated persisted session data.
			 *
			 * @return array
			 */
			public function get_session_data_from_storage(): array {
				return array( 'version' => 'loaded' );
			}
		};
		WC()->session     = $session;
		SessionConcurrencyGuard::capture( $session, array( 'version' => 'loaded' ) );

		try {
			$assert_unchanged->invoke( $sut );
			$this->assertTrue( true );
		} finally {
			WC()->session = $original_session;
		}
	}
}
