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
}
