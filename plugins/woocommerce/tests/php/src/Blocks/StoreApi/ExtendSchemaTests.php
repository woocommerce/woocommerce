<?php

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;

/**
 * Tests Extend Schema Functionality and helpers.
 *
 * @since $VID:$
 */
class ExtendSchemaTests extends TestCase {
	/**
	 * Extend mock.
	 *
	 * @var ExtendSchema
	 */
	private $mock_extend;

	/**
	 * Dummy function to ensure API gives the same function back.
	 * @var \Closure
	 */
	private $dummy;

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );
		$this->mock_extend = new ExtendSchema( $formatters );
		$this->dummy       = function () {
			return null;
		};

		// ExtendSchema only rethrows callback errors for admins with WP_DEBUG on; logged out is the production path.
		wp_set_current_user( 0 );
	}

	/**
	 * Test that we can register a callback and the same function is returned.
	 */
	public function test_register_callback() {
		$this->mock_extend->register_update_callback(
			array(
				'namespace' => 'test-plugin',
				'callback'  => $this->dummy,
			)
		);
		$this->assertSame( $this->dummy, $this->mock_extend->get_update_callback( 'test-plugin' ) );
	}

	/**
	 * Test that we can register a callback and the same function is returned.
	 */
	public function test_fail_register_callback() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'You must provide a plugin namespace when extending a Store REST endpoint.' );
		$this->mock_extend->register_update_callback(
			array(
				'callback' => $this->dummy,
			)
		);
	}

	/**
	 * Test that we can register a callback and the same function is returned.
	 */
	public function test_fail_get_callback() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'There is no such namespace registered: nonexistent-plugin.' );
		$this->mock_extend->register_update_callback(
			array(
				'namespace' => 'test-plugin',
				'callback'  => $this->dummy,
			)
		);
		$this->mock_extend->get_update_callback( 'nonexistent-plugin' );
	}

	/**
	 * Test that we can register a callback and the same function is returned.
	 */
	public function test_fail_get_callback_with_uncallable() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'There is no valid callback supplied to register_update_callback.' );
		$this->mock_extend->register_update_callback(
			array(
				'namespace' => 'test-plugin',
			)
		);
		$this->mock_extend->get_update_callback( 'nonexistent-plugin' );
	}

	/**
	 * @testdox A namespace whose data callback throws gets an empty payload, not the previous namespace's data.
	 */
	public function test_get_endpoint_data_does_not_leak_data_between_namespaces() {
		$this->register_endpoint_data(
			'first-plugin',
			function () {
				return array( 'token' => 'abc' );
			}
		);
		$this->register_endpoint_data(
			'second-plugin',
			function () {
				throw new \Exception( 'Callback failed.' );
			}
		);

		$data = $this->mock_extend->get_endpoint_data( 'cart' );

		$this->assertSame( array( 'token' => 'abc' ), $data->{'first-plugin'} );
		$this->assertSame( array(), $data->{'second-plugin'}, 'A failed namespace must not receive another namespace\'s data' );
	}

	/**
	 * @testdox The first namespace gets an empty payload when its data callback throws.
	 */
	public function test_get_endpoint_data_when_the_first_namespace_throws() {
		$this->register_endpoint_data(
			'first-plugin',
			function () {
				throw new \Exception( 'Callback failed.' );
			}
		);
		$this->register_endpoint_data(
			'second-plugin',
			function () {
				return array( 'token' => 'abc' );
			}
		);

		$data = $this->mock_extend->get_endpoint_data( 'cart' );

		$this->assertSame( array(), $data->{'first-plugin'} );
		$this->assertSame( array( 'token' => 'abc' ), $data->{'second-plugin'} );
	}

	/**
	 * @testdox A namespace whose schema callback throws gets empty properties, not the previous namespace's schema.
	 */
	public function test_get_endpoint_schema_does_not_leak_schema_between_namespaces() {
		$this->register_endpoint_data(
			'first-plugin',
			null,
			function () {
				return array( 'token' => array( 'type' => 'string' ) );
			}
		);
		$this->register_endpoint_data(
			'second-plugin',
			null,
			function () {
				throw new \Exception( 'Callback failed.' );
			}
		);

		$schema = $this->mock_extend->get_endpoint_schema( 'cart' );

		$this->assertSame( array( 'token' => array( 'type' => 'string' ) ), $schema->{'first-plugin'}['properties'] );
		$this->assertSame( array(), $schema->{'second-plugin'}['properties'], 'A failed namespace must not receive another namespace\'s schema' );
	}

	/**
	 * Registers cart endpoint data for a namespace.
	 *
	 * @param string        $plugin_namespace Plugin namespace.
	 * @param callable|null $data_callback    Callback returning endpoint data.
	 * @param callable|null $schema_callback  Callback returning endpoint schema.
	 */
	private function register_endpoint_data( $plugin_namespace, $data_callback = null, $schema_callback = null ) {
		$this->mock_extend->register_endpoint_data(
			array(
				'endpoint'        => 'cart',
				'namespace'       => $plugin_namespace,
				'data_callback'   => $data_callback,
				'schema_callback' => $schema_callback,
			)
		);
	}
}
