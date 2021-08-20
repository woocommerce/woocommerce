<?php


namespace Automattic\WooCommerce\Blocks\Tests\Library;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException;
use Exception;
use Automattic\WooCommerce\Blocks\Domain\Services\FeatureGating;
use Automattic\WooCommerce\Blocks\Domain\Package as DomainPackage;

/**
 * Tests Delete Draft Orders functionality
 *
 * @since $VID:$
 */
class TestExtendRestApi extends TestCase {
	private $mock_extend;

	/**
	 * Dummy function to ensure API gives the same function back.
	 * @var \Closure
	 */
	private $dummy;

	/**
	 * Tracking caught exceptions from API.
	 */
	private $caught_exception;

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();
		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );
		$this->mock_extend = new ExtendRestApi( new DomainPackage( '', '', new FeatureGating( 2 ) ), $formatters );
		$this->dummy       = function () {
			return null;
		};
		// set listening for exceptions
		add_action( 'woocommerce_caught_exception', function($exception_object){
			$this->caught_exception = $exception_object;
			throw $exception_object;
		});
	}

	/**
	 * Test that we can register a callback and the same function is returned.
	 */
	public function test_register_callback() {
		$this->mock_extend->register_update_callback(
			[
				'namespace' => 'test-plugin',
				'callback'  => $this->dummy,
			]
		);
		$this->assertSame( $this->dummy, $this->mock_extend->get_update_callback( 'test-plugin' ) );
	}

	/**
	 * Test that we can register a callback and the same function is returned.
	 */
	public function test_fail_register_callback() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage('You must provide a plugin namespace when extending a Store REST endpoint.');
		$this->mock_extend->register_update_callback(
			[
				'callback' => $this->dummy,
			]
		);
	}

	/**
	 * Test that we can register a callback and the same function is returned.
	 */
	public function test_fail_get_callback() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage('There is no such namespace registered: nonexistent-plugin.');
		$this->mock_extend->register_update_callback(
			[
				'namespace' => 'test-plugin',
				'callback'  => $this->dummy,
			]
		);
		$this->mock_extend->get_update_callback( 'nonexistent-plugin' );
	}

	/**
	 * Test that we can register a callback and the same function is returned.
	 */
	public function test_fail_get_callback_with_uncallable() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage('There is no valid callback supplied to register_update_callback.');
		$this->mock_extend->register_update_callback(
			[
				'namespace' => 'test-plugin',
			]
		);
		$this->mock_extend->get_update_callback( 'nonexistent-plugin' );
	}
}
