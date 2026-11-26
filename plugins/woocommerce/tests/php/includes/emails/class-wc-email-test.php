<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
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
	 * @testdox With the block email editor, the subject keeps text after a "<" that does not start a tag and decodes entities.
	 */
	public function test_block_editor_subject_keeps_text_after_less_than_sign(): void {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$was_enabled         = $features_controller->feature_is_enabled( 'block_email_editor' );
		$features_controller->change_feature_enable( 'block_email_editor', true );
		$email  = new WC_Email_Customer_Processing_Order();
		$filter = function () {
			return '<strong>I <3 Shoes</strong> &amp; more';
		};
		add_filter( 'woocommerce_email_subject_' . $email->id, $filter );

		try {
			$this->assertSame( 'I <3 Shoes & more', $email->get_subject() );
		} finally {
			remove_filter( 'woocommerce_email_subject_' . $email->id, $filter );
			$features_controller->change_feature_enable( 'block_email_editor', $was_enabled );
		}
	}

	/**
	 * @testdox With the block email editor, tag values in the subject and preheader are not parsed as HTML a second time.
	 */
	public function test_block_editor_subject_and_preheader_keep_literal_tag_text_from_values(): void {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$was_enabled         = $features_controller->feature_is_enabled( 'block_email_editor' );
		$features_controller->change_feature_enable( 'block_email_editor', true );
		$registry = Email_Editor_Container::container()->get( Personalization_Tags_Registry::class );
		$registry->register(
			new Personalization_Tag(
				'Sale',
				'[wc-email-test/sale]',
				'Test',
				function () {
					return '&lt;em&gt;Sale&lt;/em&gt;';
				}
			)
		);
		$email  = new WC_Email_Customer_Processing_Order();
		$filter = function () {
			return '<strong>Big</strong> <!--[wc-email-test/sale]-->';
		};
		add_filter( 'woocommerce_email_subject_' . $email->id, $filter );
		add_filter( 'woocommerce_email_preheader' . $email->id, $filter );

		try {
			$this->assertSame( 'Big <em>Sale</em>', $email->get_subject() );
			$this->assertSame( 'Big <em>Sale</em>', $email->get_preheader() );

			// The merchant's own text is not parsed as HTML again either, so a "<" in it can't hide a tag.
			$filter_text = function () {
				return '<strong>Big</strong> a&lt;b <!-- [wc-email-test/sale] -->';
			};
			add_filter( 'woocommerce_email_subject_' . $email->id, $filter_text, 20 );
			add_filter( 'woocommerce_email_preheader' . $email->id, $filter_text, 20 );
			$this->assertSame( 'Big a<b <em>Sale</em>', $email->get_subject() );
			$this->assertSame( 'Big a<b <em>Sale</em>', $email->get_preheader() );
			remove_filter( 'woocommerce_email_subject_' . $email->id, $filter_text, 20 );
			remove_filter( 'woocommerce_email_preheader' . $email->id, $filter_text, 20 );
		} finally {
			remove_filter( 'woocommerce_email_subject_' . $email->id, $filter );
			remove_filter( 'woocommerce_email_preheader' . $email->id, $filter );
			$registry->unregister( '[wc-email-test/sale]' );
			$features_controller->change_feature_enable( 'block_email_editor', $was_enabled );
		}
	}

	/**
	 * @testdox With the block email editor, getting a subject without tags still sets the personalization context the body uses.
	 */
	public function test_block_editor_subject_without_tags_sets_personalization_context(): void {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$was_enabled         = $features_controller->feature_is_enabled( 'block_email_editor' );
		$features_controller->change_feature_enable( 'block_email_editor', true );
		$personalizer = Email_Editor_Container::container()->get( Personalizer::class );
		$personalizer->set_context( array() );
		$order         = WC_Helper_Order::create_order();
		$email         = new WC_Email_Customer_Processing_Order();
		$email->object = $order;
		$filter        = function () {
			return 'Your order';
		};
		add_filter( 'woocommerce_email_subject_' . $email->id, $filter );

		try {
			$this->assertSame( 'Your order', $email->get_subject() );
			$this->assertSame( $order, $personalizer->get_context()['order'] ?? null );
		} finally {
			remove_filter( 'woocommerce_email_subject_' . $email->id, $filter );
			$personalizer->set_context( array() );
			$features_controller->change_feature_enable( 'block_email_editor', $was_enabled );
		}
	}

	/**
	 * @testdox With the block email editor, emails that build their own subject also get it as plain text.
	 */
	public function test_block_editor_invoice_subject_is_plain_text(): void {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$was_enabled         = $features_controller->feature_is_enabled( 'block_email_editor' );
		$features_controller->change_feature_enable( 'block_email_editor', true );
		update_option( 'woocommerce_customer_invoice_settings', array( 'subject' => '<strong>Invoice</strong> a&lt;b' ) );
		$email         = new WC_Email_Customer_Invoice();
		$email->object = WC_Helper_Order::create_order();

		try {
			$this->assertSame( 'Invoice a<b', $email->get_subject() );
		} finally {
			delete_option( 'woocommerce_customer_invoice_settings' );
			$features_controller->change_feature_enable( 'block_email_editor', $was_enabled );
		}
	}

	/**
	 * @testdox With the block email editor, the POS refunded order subject is filled in and converted to plain text.
	 */
	public function test_block_editor_pos_refunded_order_subject_is_plain_text(): void {
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$was_enabled         = $features_controller->feature_is_enabled( 'block_email_editor' );
		$features_controller->change_feature_enable( 'block_email_editor', true );
		$registry = Email_Editor_Container::container()->get( Personalization_Tags_Registry::class );
		$registry->register(
			new Personalization_Tag(
				'Store',
				'[wc-email-test/store]',
				'Test',
				function () {
					return 'Tom &amp; Jerry';
				}
			)
		);
		update_option( 'woocommerce_customer_pos_refunded_order_settings', array( 'subject_full' => '<strong>Refund</strong> a&lt;b from <!--[wc-email-test/store]-->' ) );
		$email         = new WC_Email_Customer_POS_Refunded_Order();
		$email->object = WC_Helper_Order::create_order();

		try {
			$this->assertSame( 'Refund a<b from Tom & Jerry', $email->get_subject() );
		} finally {
			delete_option( 'woocommerce_customer_pos_refunded_order_settings' );
			$registry->unregister( '[wc-email-test/store]' );
			$features_controller->change_feature_enable( 'block_email_editor', $was_enabled );
		}
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
