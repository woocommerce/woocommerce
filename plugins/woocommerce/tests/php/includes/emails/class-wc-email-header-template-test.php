<?php
declare( strict_types = 1 );

/**
 * `email-header.php` test.
 *
 * @covers `email-header.php` template
 */
class WC_Email_Header_Template_Test extends \WC_Unit_Test_Case {
	/**
	 * @testdox Email header template includes blog name when store name is not set.
	 */
	public function test_html_includes_blog_name_when_store_name_is_not_set() {
		// Given blog name.
		update_option( 'blogname', 'Online Store' );

		// When getting content from email header.
		$content = wc_get_template_html( 'emails/email-header.php', array( 'email_heading' => 'Test email heading' ) );

		// Then email header should include blog name.
		$this->assertStringContainsString( '<title>Online Store</title>', $content );
	}

	/**
	 * @testdox Email header template includes store name, not blog name, when store name is set.
	 */
	public function test_html_includes_store_name_when_store_name_is_set() {
		// Given blog name.
		update_option( 'blogname', 'Online Store' );

		// When getting content from email header.
		$content = wc_get_template_html(
			'emails/email-header.php',
			array(
				'email_heading' => 'Test email heading',
				'store_name'    => 'Another store',
			)
		);

		// Then email header should include blog name.
		$this->assertStringContainsString( '<title>Another store</title>', $content );
		$this->assertStringNotContainsString( '<title>Online Store</title>', $content );
	}
}
