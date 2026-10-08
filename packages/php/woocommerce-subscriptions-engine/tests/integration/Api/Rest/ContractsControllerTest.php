<?php
/**
 * Integration tests for the contracts REST controller.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api\Rest;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\ContractsController;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\ContractsController
 */
class ContractsControllerTest extends EngineIntegrationTestCase {

	/**
	 * The controller registers no routes under its base.
	 */
	public function test_registers_no_contract_routes(): void {
		add_action(
			'rest_api_init',
			static function (): void {
				( new ContractsController() )->register_routes();
			}
		);
		do_action( 'rest_api_init' );

		$routes = array_filter(
			array_keys( rest_get_server()->get_routes() ),
			static function ( string $route ): bool {
				return 0 === strpos( $route, '/wc/v3/subscriptions-engine/contracts' );
			}
		);

		$this->assertSame( array(), array_values( $routes ) );
	}
}
