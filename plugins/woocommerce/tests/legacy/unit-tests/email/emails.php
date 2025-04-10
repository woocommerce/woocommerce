<?php
/**
 * Test for the email class.
 * @package WooCommerce\Tests\Emails
 */

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * WC_Tests_WC_Emails.
 *
 * @covers \WC_Email
 */
class WC_Tests_WC_Emails extends WC_Unit_Test_Case {

	/**
	 * Setup tests.
	 */
	public function setUp(): void {
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
	 * Test that we remove elements with style display none from html mails.
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

	/**
	 * Test that headers are properly generated.
	 */
	public function test_headers() {
		$email             = new WC_Email();
		$email->id         = 'headers_test';
		$email->email_type = 'html';

		$result = $email->get_headers();
		$this->assertTrue( false !== strpos( $result, 'Content-Type: text/html' ) );
		$this->assertTrue( false === strpos( $result, 'Cc:' ) );
		$this->assertTrue( false === strpos( $result, 'Bcc:' ) );
	}

	/**
	 * Test that headers are properly generated in email improvements.
	 */
	public function test_headers_with_enabled_email_improvements() {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$original_value      = $features_controller->feature_is_enabled( 'email_improvements' );
		$features_controller->change_feature_enable( 'email_improvements', true );

		$email             = new WC_Email();
		$email->id         = 'headers_test';
		$email->email_type = 'plain';
		$email->cc         = '   cc@example.com, invalid$&*^&%_email,      valid@email.com    ';
		$email->bcc        = '     invalid value, header should be skipped     ';

		$result = $email->get_headers();
		$this->assertTrue( false !== strpos( $result, 'Content-Type: text/plain' ) );
		$this->assertTrue( false !== strpos( $result, 'Cc: cc@example.com, valid@email.com' ) );
		$this->assertTrue( false === strpos( $result, 'Bcc:' ) );

		$features_controller->change_feature_enable( 'email_improvements', $original_value );
	}

}
