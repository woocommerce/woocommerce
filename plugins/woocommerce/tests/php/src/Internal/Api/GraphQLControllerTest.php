<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Internal\Api\GraphQLController;
use Automattic\WooCommerce\Internal\Api\Main;
use WC_REST_Unit_Test_Case;

/**
 * Tests for the GraphQLController class — specifically the HTTP methods
 * registered on the /wc/graphql route based on the GET endpoint option.
 */
class GraphQLControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var GraphQLController
	 */
	private $sut;

	/**
	 * Set up before each test.
	 *
	 * Skips on PHP < 8.1 because GraphQLController uses PHP 8.0+ syntax in its
	 * source file (named arguments). In production the class is only loaded
	 * after {@see Main::is_enabled()} gates on PHP 8.1+; these tests bypass
	 * that gate by hitting the DI container directly, so we replicate it here.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( PHP_VERSION_ID < 80100 ) {
			$this->markTestSkipped( 'GraphQLController requires PHP 8.1+.' );
		}

		$this->sut = wc_get_container()->get( GraphQLController::class );
	}

	/**
	 * Clean up the GET endpoint option between tests.
	 */
	public function tearDown(): void {
		delete_option( Main::OPTION_GET_ENDPOINT_ENABLED );
		parent::tearDown();
	}

	/**
	 * @testdox register exposes POST only when the GET endpoint option is disabled.
	 */
	public function test_register_exposes_post_only_when_get_disabled(): void {
		update_option( Main::OPTION_GET_ENDPOINT_ENABLED, 'no' );

		$this->sut->register();

		$handlers = rest_get_server()->get_routes()['/wc/graphql'];
		$this->assertCount( 1, $handlers, 'Exactly one handler should be registered for /wc/graphql.' );
		$methods = $handlers[0]['methods'];
		$this->assertTrue( $methods['POST'] ?? false );
		$this->assertFalse( $methods['GET'] ?? false );
	}

	/**
	 * @testdox register exposes GET and POST when the GET endpoint option is enabled.
	 */
	public function test_register_exposes_get_and_post_when_get_enabled(): void {
		update_option( Main::OPTION_GET_ENDPOINT_ENABLED, 'yes' );

		$this->sut->register();

		$handlers = rest_get_server()->get_routes()['/wc/graphql'];
		$this->assertCount( 1, $handlers, 'Exactly one handler should be registered for /wc/graphql.' );
		$methods = $handlers[0]['methods'];
		$this->assertTrue( $methods['GET'] ?? false );
		$this->assertTrue( $methods['POST'] ?? false );
	}
}
