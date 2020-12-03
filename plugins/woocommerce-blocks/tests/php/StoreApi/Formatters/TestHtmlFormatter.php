<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Formatters;

use \WC_REST_Unit_Test_Case as TestCase;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\HtmlFormatter;

/**
 * TestHtmlFormatter tests.
 */
class TestHtmlFormatter extends TestCase {

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
