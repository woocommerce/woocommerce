<?php
/**
 * Test for the email class.
 * @package WooCommerce\Tests\Emails
 */

/**
 * WC_Tests_WC_Emails.
 *
 * @covers \WC_Email
 */
class WC_Tests_WC_Emails extends WC_Unit_Test_Case {

	/**
	 * Setup tests.
	 */
	public function setUp() {
		parent::setUp();

		// Load email classes.
		$emails = new WC_Emails();
		$emails->init();
	}

	/**
	 * Test get and set items.
	 */
	public function test_style_inline() {
		$email = new WC_Email();

		// Test HTML email with inline styles.
		$email->email_type = 'html';

		// Set some content to get converted.
		$result = $email->style_inline( '<p class="text">Hello World!</p>' );

		ob_start();
		include WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/sample-email.html';
		$expected = ob_get_clean();

		$this->assertEquals( $expected, $result );

		// Test plain text email.
		$email->email_type = 'plain';

		// Set some content to get converted.
		$result   = $email->style_inline( '<p class="text">Hello World!</p>' );
		$expected = '<p class="text">Hello World!</p>';

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test that we remove elemets with style display none from html mails.
	 */
	public function test_remove_display_none_elements() {
		$email = new WC_Email();
		$email->email_type = 'html';
		$str_present = 'Should be present!';
		$str_removed = 'Should be removed!';
		$result = $email->style_inline( "<div><div class='text'>$str_present</div><div style='display: none'>$str_removed</div> </div>" );
		$this->assertTrue( false !== strpos( $result, $str_present ) );
		$this->assertTrue( false === strpos( $result, $str_removed ) );
	}

}
