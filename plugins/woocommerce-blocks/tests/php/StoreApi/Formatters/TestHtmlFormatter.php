<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Formatters;

use Automattic\WooCommerce\Blocks\StoreApi\Formatters\HtmlFormatter;

/**
 * TestHtmlFormatter tests.
 */
class TestHtmlFormatter extends \WP_UnitTestCase {

	private $mock_formatter;

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
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
