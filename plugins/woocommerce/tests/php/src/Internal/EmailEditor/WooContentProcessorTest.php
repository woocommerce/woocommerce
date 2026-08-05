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
	 * @testdox Should strip typography properties from WooCommerce CSS.
	 */
	public function test_prepare_css_strips_typography_properties(): void {
		$css = 'h2 { color: red; font-family: Arial; font-size: 20px; font-weight: bold; line-height: 1.5; letter-spacing: -1px; margin: 0; }';

		$result = $this->woo_content_processor->prepare_css( $css );

		$this->assertStringNotContainsString( 'color', $result, 'Color should be stripped' );
		$this->assertStringNotContainsString( 'font-family', $result, 'Font-family should be stripped' );
		$this->assertStringNotContainsString( 'font-size', $result, 'Font-size should be stripped' );
		$this->assertStringNotContainsString( 'font-weight', $result, 'Font-weight should be stripped' );
		$this->assertStringNotContainsString( 'line-height', $result, 'Line-height should be stripped' );
		$this->assertStringNotContainsString( 'letter-spacing', $result, 'Letter-spacing should be stripped' );
		$this->assertStringContainsString( 'margin', $result, 'Non-typography properties should be preserved' );
	}

	/**
	 * @testdox Should return WooCommerce content styles with correct order totals CSS.
	 */
	public function test_get_woo_content_styles_contains_order_totals_css(): void {
		$reflection = new \ReflectionClass( $this->woo_content_processor );
		$method     = $reflection->getMethod( 'get_woo_content_styles' );
		$method->setAccessible( true );

		$css = $method->invoke( $this->woo_content_processor );

		$this->assertStringContainsString( '.order-totals th', $css, 'Should target order totals headers' );
		$this->assertStringContainsString( '.order-totals-total th', $css, 'Should target total row header' );
		$this->assertStringContainsString( '.order-totals-total td', $css, 'Should target total row value' );
		$this->assertStringContainsString( 'font-weight: 400', $css, 'Non-total rows should be regular weight' );
		$this->assertStringContainsString( 'font-weight: 700', $css, 'Total row should be bold' );
		$this->assertStringContainsString( 'font-size: 20px', $css, 'Total value should be 20px' );
		$this->assertStringContainsString( '.email-order-item-meta', $css, 'Should target order item metadata' );
		$this->assertStringContainsString( 'h2.email-order-detail-heading', $css, 'Should target section headings' );
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

		$wc_email                   = new \WC_Email_Customer_New_Account();
		$wc_email->user_login       = 'testuser';
		$wc_email->user_email       = 'test@example.com';
		$wc_email->user_pass        = 'testpass';
		$wc_email->set_password_url = 'https://example.com/set-password';

		$content = $this->woo_content_processor->get_woo_content( $wc_email );

		$this->assertNotEmpty( $content );
		$this->assertStringContainsString( 'Set your new password', $content );
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
