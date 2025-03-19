<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\Internal\EmailEditor\WooContentProcessor;

/**
 * Tests for the BlockEmailRenderer class.
 */
class WooContentProcessorTest extends \WC_Unit_Test_Case {
	/**
	 * @var WooContentProcessor $woo_content_processor
	 */
	private WooContentProcessor $woo_content_processor;
	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->woo_content_processor = wc_get_container()->get( WooContentProcessor::class );
		\WC_Emails::instance()->init();
	}

	/**
	 * Test that the BlockEmailRenderer can render email and replaces Woo Content.
	 */
	public function testItCapturesWooContent(): void {
		// Register header and footer content to test it gets excluded.
		add_filter(
			'woocommerce_email_header',
			function () {
				echo 'Test email header';
			}
		);
		add_filter(
			'woocommerce_email_footer',
			function () {
				echo 'Test email footer';
			}
		);

		$wc_email             = new \WC_Email_Customer_New_Account();
		$wc_email->user_login = 'testuser';
		$wc_email->user_email = 'test@example.com';
		$wc_email->user_pass  = 'testpass';

		$content = $this->woo_content_processor->get_woo_content( $wc_email );

		$this->assertNotEmpty( $content );
		$this->assertStringContainsString( 'Thanks for creating an account on', $content );
		$this->assertStringContainsString( 'Your username is', $content );
		$this->assertStringContainsString( 'testuser', $content );
		$this->assertStringNotContainsString( 'Test email header', $content );
		$this->assertStringNotContainsString( 'Test email footer', $content );
		$this->assertStringNotContainsString( '<body>', $content );
		$this->assertStringNotContainsString( 'DOCTYPE', $content );

		// Test that the original content is not affected and contains header and footer.
		$original_content = $wc_email->get_content_html();
		$this->assertStringContainsString( 'Thanks for creating an account on', $original_content );
		$this->assertStringContainsString( 'Your username is', $original_content );
		$this->assertStringContainsString( 'testuser', $original_content );
		$this->assertStringContainsString( 'Test email header', $original_content );
		$this->assertStringContainsString( 'Test email footer', $original_content );
	}
}
