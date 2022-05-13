<?php
/**
 * Test facets of the abstract settings API class.
 *
 * @package WooCommerce\Tests\Abstracts
 */

/**
 * Tests relating to WC_Settings_API.
 */
class WC_Settings_API_Test extends WC_Unit_Test_Case {
	/**
	 * Subject under test (WC_Settings_API).
	 *
	 * @var WC_Settings_API
	 */
	private $sut;

	/**
	 * Create mock for the abstract WC_Settings_API class.
	 *
	 * @throws ReflectionException
	 */
	public function set_up() {
		$this->sut = $this->getMockForAbstractClass( WC_Settings_API::class );
	}

	/**
	 * @testdox Test inputs and outputs for the validate_safe_text_field() method.
	 * @dataProvider safe_text_field_expectations
	 */
	public function test_safe_text_field( string $test_string, string $expected, string $explanation ) {
		$this->assertEquals( $expected, $this->sut->validate_safe_text_field( '', $test_string ), $explanation );
	}

	/**
	 * Describes expectations for the validate_safe_text_field() method.
	 *
	 * @return string[][]
	 */
	public function safe_text_field_expectations() {
		return array(
			array( 'Simple Text', 'Simple Text', 'Plain text without HTML tags passes through unchanged.' ),
			array( ' Leading/trailing whitespace ', 'Leading/trailing whitespace', 'Leading and trailing whitespace will be removed.' ),
			array( '<p>Paragraph</p>', '<p>Paragraph</p>', 'Paragraph tags are allowed' ),
			array( '<div><p><i>Paragraph</i></p></div>', '<p>Paragraph</p>', 'Disallowed tags are removed, allowed tags remain.' ),
			array( '</p> <p><img src="http://bar/icon.png" /> Purchase</p>', ' <p><img src="http://bar/icon.png" /> Purchase</p>', 'Unbalanced tags are removed.' ),
		);
	}
}
