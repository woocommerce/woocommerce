<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Formatters;

use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;

/**
 * TestHtmlFormatter tests.
 */
class TestHtmlFormatter extends \WP_UnitTestCase {

	private $mock_formatter;

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mock_formatter = new HtmlFormatter();
	}

	/**
	 * Test formatting.
	 */
	public function test_format() {
		$this->assertEquals( "&#8220;Quotes&#8221;", $this->mock_formatter->format( '"Quotes"' ) );
	}
}
