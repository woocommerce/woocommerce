<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * WC_Email test.
 *
 * @covers WC_Email
 */
class WC_Email_Test extends \WC_Unit_Test_Case {

	/**
	 * Original value of the email_improvements feature flag.
	 *
	 * @var bool
	 */
	private $email_improvements_was_enabled;

	/**
	 * Enable email improvements so Cc/Bcc settings would normally be honoured, and load the email classes.
	 */
	public function setUp(): void {
		parent::setUp();

		$features_controller                  = wc_get_container()->get( FeaturesController::class );
		$this->email_improvements_was_enabled = $features_controller->feature_is_enabled( 'email_improvements' );
		$features_controller->change_feature_enable( 'email_improvements', true );

		WC()->mailer();
	}

	/**
	 * Restore the feature flag.
	 */
	public function tearDown(): void {
		wc_get_container()->get( FeaturesController::class )->change_feature_enable( 'email_improvements', $this->email_improvements_was_enabled );

		parent::tearDown();
	}

	/**
	 * @testdox Emails that do not support Cc/Bcc hide the fields and ignore stored or directly set values.
	 */
	public function test_cc_bcc_are_ignored_when_email_does_not_support_them(): void {
		update_option(
			'woocommerce_no_cc_bcc_test_settings',
			array(
				'cc'  => 'cc@example.com',
				'bcc' => 'bcc@example.com',
			)
		);
		$email = $this->create_email_without_cc_bcc_support();

		$this->assertSame( 'cc@example.com', $email->get_option( 'cc' ), 'Fixture: stored value must be readable under this option key' );
		$this->assertFalse( $email->supports_cc_bcc() );
		$this->assertNull( $email->cc, 'Constructor must not load the stored Cc' );
		$this->assertNull( $email->bcc, 'Constructor must not load the stored Bcc' );
		$this->assertArrayNotHasKey( 'cc', $email->get_form_fields() );
		$this->assertArrayNotHasKey( 'bcc', $email->get_form_fields() );
		$this->assertStringNotContainsString( 'Cc:', $email->get_headers(), 'Stored Cc must be ignored' );
		$this->assertStringNotContainsString( 'Bcc:', $email->get_headers(), 'Stored Bcc must be ignored' );

		$email->cc  = 'cc@example.com';
		$email->bcc = 'bcc@example.com';

		$this->assertStringNotContainsString( 'Cc:', $email->get_headers(), 'Cc set on the object must be ignored' );
		$this->assertStringNotContainsString( 'Bcc:', $email->get_headers(), 'Bcc set on the object must be ignored' );
	}

	/**
	 * @testdox The Cc/Bcc recipient filters still apply to emails that do not support Cc/Bcc.
	 *
	 * @testWith ["cc", "Cc"]
	 *           ["bcc", "Bcc"]
	 *
	 * @param string $type   Filter type: cc or bcc.
	 * @param string $header Header name the filtered value must appear under.
	 */
	public function test_cc_bcc_filters_apply_when_email_does_not_support_them( string $type, string $header ): void {
		add_filter(
			"woocommerce_email_{$type}_recipient_no_cc_bcc_test",
			function ( $value ) {
				$this->assertSame( '', $value, 'Filter must not receive a stored value' );
				return 'audit@example.com';
			}
		);
		$email          = $this->create_email_without_cc_bcc_support();
		$email->{$type} = 'stored@example.com';

		$this->assertStringContainsString( "{$header}: audit@example.com", $email->get_headers() );
	}

	/**
	 * @testdox Emails that carry a credential do not support Cc/Bcc.
	 *
	 * @testWith ["WC_Email_Customer_Reset_Password"]
	 *           ["WC_Email_Customer_New_Account"]
	 *           ["Automattic\\WooCommerce\\Internal\\CustomerEmailVerification\\Emails\\CustomerVerifyEmail"]
	 *           ["Automattic\\WooCommerce\\Internal\\StockNotifications\\Emails\\CustomerStockNotificationVerifyEmail"]
	 *
	 * @param string $class_name Email class name.
	 */
	public function test_credential_emails_do_not_support_cc_bcc( string $class_name ): void {
		$this->assertFalse( ( new $class_name() )->supports_cc_bcc() );
	}

	/**
	 * @testdox Plain-text emails keep an apostrophe that the template escaped in the heading.
	 *
	 * @testWith ["Your order's complete"]
	 *           ["L'été est là"]
	 *
	 * @param string $heading Email heading with an apostrophe.
	 */
	public function test_plain_email_keeps_apostrophe_in_heading( string $heading ): void {
		$email             = new WC_Email_Customer_Completed_Order();
		$email->object     = WC_Helper_Order::create_order();
		$email->email_type = 'plain';
		add_filter(
			'woocommerce_email_heading_customer_completed_order',
			function () use ( $heading ) {
				return $heading;
			}
		);

		$this->assertStringContainsString( str_replace( "'", '&#039;', $heading ), $email->get_content_plain(), 'Fixture: the plain template must escape the apostrophe' );
		$this->assertStringContainsString( $heading, $email->get_content() );
	}

	/**
	 * @testdox The plain-text rules turn decimal and hexadecimal apostrophe codes into an apostrophe.
	 *
	 * @testWith ["&#39;"]
	 *           ["&#x27;"]
	 *
	 * @param string $code Apostrophe character reference.
	 */
	public function test_plain_text_rules_turn_numeric_apostrophe_codes_into_apostrophe( string $code ): void {
		$email = new WC_Email_Customer_Completed_Order();

		$this->assertSame( "Your order's complete", preg_replace( $email->plain_search, $email->plain_replace, "Your order{$code}s complete" ) );
	}

	/**
	 * @testdox The plain-text rules still turn &rsquo; into an apostrophe.
	 */
	public function test_plain_text_rules_turn_rsquo_into_apostrophe(): void {
		$email = new WC_Email_Customer_Completed_Order();

		$this->assertSame( "Your order's complete", preg_replace( $email->plain_search, $email->plain_replace, 'Your order&rsquo;s complete' ) );
	}

	/**
	 * Create an email that opts out of Cc/Bcc support.
	 *
	 * @return WC_Email
	 */
	private function create_email_without_cc_bcc_support(): WC_Email {
		return new class() extends WC_Email {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id              = 'no_cc_bcc_test';
				$this->supports_cc_bcc = false;
				parent::__construct();
			}
		};
	}
}
