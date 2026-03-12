<?php
declare( strict_types = 1 );

/**
 * `email-header.php` test.
 *
 * @covers `email-header.php` template
 */
class WC_Email_Header_Template_Test extends \WC_Unit_Test_Case {

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'woocommerce_email_header_image' );
		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );
		remove_all_filters( 'woocommerce_email_header_image_url' );
	}

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

	/**
	 * @testdox Header image is wrapped in a link to home_url() when improvements are disabled.
	 */
	public function test_header_image_wrapped_in_link() {
		update_option( 'woocommerce_email_header_image', 'https://example.com/logo.png' );

		$content = wc_get_template_html( 'emails/email-header.php', array( 'email_heading' => 'Test' ) );

		$this->assertStringContainsString( '<a ', $content );
		$this->assertStringContainsString( 'target="_blank"', $content );
		$this->assertStringContainsString( 'src="https://example.com/logo.png"', $content );
	}

	/**
	 * @testdox Header image is wrapped in a link to home_url() when improvements are enabled.
	 */
	public function test_header_image_wrapped_in_link_with_improvements_enabled() {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );
		update_option( 'woocommerce_email_header_image', 'https://example.com/logo.png' );

		$content = wc_get_template_html( 'emails/email-header.php', array( 'email_heading' => 'Test' ) );

		$this->assertStringContainsString( '<a ', $content );
		$this->assertStringContainsString( 'target="_blank"', $content );
		$this->assertStringContainsString( 'src="https://example.com/logo.png"', $content );
	}

	/**
	 * @testdox Text fallback is wrapped in a link when improvements are enabled and no image is set.
	 */
	public function test_text_fallback_wrapped_in_link_with_improvements_enabled() {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );
		update_option( 'blogname', 'My Store' );
		delete_option( 'woocommerce_email_header_image' );

		$content = wc_get_template_html( 'emails/email-header.php', array( 'email_heading' => 'Test' ) );

		$this->assertStringContainsString( 'email-logo-text', $content );
		$this->assertStringContainsString( '<a href="' . esc_url( home_url() ) . '"', $content );
		$this->assertStringContainsString( 'My Store</a>', $content );
	}

	/**
	 * @testdox No text fallback or link is rendered when improvements are disabled and no image is set.
	 */
	public function test_no_text_fallback_without_improvements() {
		delete_option( 'woocommerce_email_header_image' );

		$content = wc_get_template_html( 'emails/email-header.php', array( 'email_heading' => 'Test' ) );

		$this->assertStringNotContainsString( 'email-logo-text', $content );
		$this->assertStringNotContainsString( '<a href="' . esc_url( home_url() ) . '" style="color: inherit', $content );
	}

	/**
	 * @testdox Header image URL is filterable via woocommerce_email_header_image_url.
	 */
	public function test_header_image_url_is_filterable() {
		update_option( 'woocommerce_email_header_image', 'https://example.com/logo.png' );
		add_filter(
			'woocommerce_email_header_image_url',
			function () {
				return 'https://custom-url.com/shop';
			}
		);

		$content = wc_get_template_html( 'emails/email-header.php', array( 'email_heading' => 'Test' ) );

		$this->assertStringContainsString( '<a href="https://custom-url.com/shop"', $content );
	}

	/**
	 * @testdox No link wraps image when filter returns empty string and improvements are enabled.
	 */
	public function test_no_link_when_filter_returns_empty_with_improvements_enabled() {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );
		update_option( 'woocommerce_email_header_image', 'https://example.com/logo.png' );
		add_filter(
			'woocommerce_email_header_image_url',
			function () {
				return '';
			}
		);

		$content = wc_get_template_html( 'emails/email-header.php', array( 'email_heading' => 'Test' ) );

		$this->assertStringContainsString( '<img src="https://example.com/logo.png"', $content );
		$this->assertStringNotContainsString( '<a href=', $content );
	}

	/**
	 * @testdox Text fallback has no link when filter returns empty string and improvements are enabled.
	 */
	public function test_text_fallback_no_link_when_filter_returns_empty_with_improvements_enabled() {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );
		update_option( 'blogname', 'My Store' );
		delete_option( 'woocommerce_email_header_image' );
		add_filter(
			'woocommerce_email_header_image_url',
			function () {
				return '';
			}
		);

		$content = wc_get_template_html( 'emails/email-header.php', array( 'email_heading' => 'Test' ) );

		$this->assertStringContainsString( 'email-logo-text', $content );
		$this->assertStringContainsString( 'My Store', $content );
		$this->assertStringNotContainsString( '<a href=', $content );
	}

	/**
	 * @testdox No link is rendered when filter returns empty string.
	 */
	public function test_no_link_when_filter_returns_empty() {
		update_option( 'woocommerce_email_header_image', 'https://example.com/logo.png' );
		add_filter(
			'woocommerce_email_header_image_url',
			function () {
				return '';
			}
		);

		$content = wc_get_template_html( 'emails/email-header.php', array( 'email_heading' => 'Test' ) );

		$this->assertStringContainsString( '<img src="https://example.com/logo.png"', $content );
		$this->assertStringNotContainsString( '<a href=', $content );
	}
}
