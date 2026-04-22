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
	 * Clean up GraphQL options between tests.
	 */
	public function tearDown(): void {
		delete_option( Main::OPTION_GET_ENDPOINT_ENABLED );
		delete_option( Main::OPTION_MAX_QUERY_DEPTH );
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

	/**
	 * @testdox get_max_query_depth returns the default when the option is unset.
	 */
	public function test_get_max_query_depth_returns_default_when_option_unset(): void {
		delete_option( Main::OPTION_MAX_QUERY_DEPTH );
		$this->assertSame(
			GraphQLController::DEFAULT_MAX_QUERY_DEPTH,
			GraphQLController::get_max_query_depth()
		);
	}

	/**
	 * @testdox get_max_query_depth returns the option value when it is a positive integer.
	 */
	public function test_get_max_query_depth_returns_option_value_when_positive(): void {
		update_option( Main::OPTION_MAX_QUERY_DEPTH, '7' );
		$this->assertSame( 7, GraphQLController::get_max_query_depth() );
	}

	/**
	 * @testdox get_max_query_depth falls back to the default when the option is empty, zero, or negative.
	 * @dataProvider provider_non_positive_option_values
	 *
	 * @param string $value The non-positive option value.
	 */
	public function test_get_max_query_depth_falls_back_on_non_positive( string $value ): void {
		update_option( Main::OPTION_MAX_QUERY_DEPTH, $value );
		$this->assertSame(
			GraphQLController::DEFAULT_MAX_QUERY_DEPTH,
			GraphQLController::get_max_query_depth()
		);
	}

	/**
	 * Non-positive values that the getter should replace with the default.
	 *
	 * @return array<string, array{string}>
	 */
	public function provider_non_positive_option_values(): array {
		return array(
			'empty string' => array( '' ),
			'zero'         => array( '0' ),
			'negative'     => array( '-5' ),
		);
	}

	/**
	 * @testdox compute_query_depth returns both the query-limit-comparable depth and the total field-chain depth.
	 * @dataProvider provider_query_depth_cases
	 *
	 * @param string $source         The GraphQL query to parse.
	 * @param int    $expected_query Depth under the query-limit semantics (only fields with children).
	 * @param int    $expected_total Depth counting every field in the deepest chain.
	 */
	public function test_compute_query_depth_returns_both_metrics( string $source, int $expected_query, int $expected_total ): void {
		$method   = new \ReflectionMethod( $this->sut, 'compute_query_depth' );
		$document = \GraphQL\Language\Parser::parse( $source );

		$result = $method->invoke( $this->sut, $document, null );

		$this->assertSame( $expected_query, $result['query'], "query metric for: $source" );
		$this->assertSame( $expected_total, $result['total'], "total metric for: $source" );
	}

	/**
	 * Queries paired with each metric's expected value.
	 *
	 * @return array<string, array{string, int, int}>
	 */
	public function provider_query_depth_cases(): array {
		return array(
			// [ source, query, total ]
			'all leaves at root'          => array( '{ a b c }', 0, 1 ),
			'one nested object'           => array( '{ a { b } }', 0, 2 ),
			'two-level nesting'           => array( '{ a { b { c } } }', 1, 3 ),
			'three-level nesting'         => array( '{ a { b { c { d } } } }', 2, 4 ),
			'inline fragment passthrough' => array( '{ a { ... on T { b { c } } } }', 1, 3 ),
		);
	}
}
