<?php
declare( strict_types = 1 );

/**
 * WC_Email_Customer_Cart_Recovery test.
 *
 * @covers WC_Email_Customer_Cart_Recovery
 */
class WC_Email_Customer_Cart_Recovery_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Email_Customer_Cart_Recovery
	 */
	private $sut;

	/**
	 * Enable the flag and load the email class, which WC_Emails only registers when the flag is on.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_cart_recovery_enabled', 'yes' );

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-cart-recovery.php';

		WC()->mailer()->init();

		$this->sut = new WC_Email_Customer_Cart_Recovery();
	}

	/**
	 * Disable the flag and rebuild the mailer registry without the cart recovery email.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_cart_recovery_enabled' );
		WC()->mailer()->emails = array();
		WC()->mailer()->init();

		parent::tearDown();
	}

	/**
	 * @testdox Should be disabled by default.
	 */
	public function test_disabled_by_default(): void {
		$this->assertFalse( $this->sut->is_enabled(), 'The email must be opt-in' );
	}

	/**
	 * @testdox Should be registered with the mailer when the feature flag is on.
	 */
	public function test_is_registered_with_wc_emails(): void {
		$emails = WC()->mailer()->get_emails();

		$this->assertArrayHasKey( 'WC_Email_Customer_Cart_Recovery', $emails, 'Email should be registered while the flag is on' );
	}

	/**
	 * @testdox Should expose the recovery settings fields.
	 */
	public function test_form_fields(): void {
		$fields = $this->sut->get_form_fields();

		foreach ( array( 'enabled', 'delay_minutes', 'min_cart_total', 'excluded_categories', 'excluded_roles' ) as $key ) {
			$this->assertArrayHasKey( $key, $fields, "Missing settings field {$key}" );
		}
		$this->assertSame( '15', $fields['delay_minutes']['custom_attributes']['min'] );
		$this->assertSame( '1380', $fields['delay_minutes']['custom_attributes']['max'] );
	}

	/**
	 * @testdox Should keep only existing product category IDs.
	 */
	public function test_validate_excluded_categories_drops_unknown_terms(): void {
		$term = wp_insert_term( 'Gift cards', 'product_cat' );

		$result = $this->sut->validate_excluded_categories_field( 'excluded_categories', array( (string) $term['term_id'], '999999', 'abc' ) );

		$this->assertSame( array( (int) $term['term_id'] ), $result );
	}

	/**
	 * @testdox Should keep only registered role slugs.
	 */
	public function test_validate_excluded_roles_drops_unknown_roles(): void {
		$result = $this->sut->validate_excluded_roles_field( 'excluded_roles', array( 'customer', 'not_a_role' ) );

		$this->assertSame( array( 'customer' ), $result );
	}

	/**
	 * @testdox Should load the multiselect options once the REST API has initialized, which the REST validator needs.
	 */
	public function test_options_loaded_after_rest_api_init(): void {
		wp_insert_term( 'Gift cards', 'product_cat' );
		rest_get_server();

		$fields = ( new WC_Email_Customer_Cart_Recovery() )->get_form_fields();

		$this->assertNotEmpty( $fields['excluded_categories']['options'] );
		$this->assertArrayHasKey( 'customer', $fields['excluded_roles']['options'] );
	}

	/**
	 * @testdox Should return an empty list for a non-array multiselect value.
	 */
	public function test_validators_handle_non_array(): void {
		$this->assertSame( array(), $this->sut->validate_excluded_categories_field( 'excluded_categories', 'x' ) );
		$this->assertSame( array(), $this->sut->validate_excluded_roles_field( 'excluded_roles', null ) );
	}

	/**
	 * @testdox Should clamp the send delay to the allowed range.
	 *
	 * @testWith ["0", "15"]
	 *           ["-5", "15"]
	 *           ["90", "90"]
	 *           ["99999", "1380"]
	 *           ["abc", "60"]
	 *
	 * @param string $value    Posted value.
	 * @param string $expected Saved value.
	 */
	public function test_validate_delay_minutes_clamps( string $value, string $expected ): void {
		$this->assertSame( $expected, $this->sut->validate_delay_minutes_field( 'delay_minutes', $value ) );
	}

	/**
	 * @testdox Should not send when no item has a product or the recovery link is missing.
	 */
	public function test_trigger_rejects_missing_product_or_link(): void {
		$this->sut->enabled = 'yes';

		$recovery                        = $this->get_recovery();
		$recovery['items'][0]['product'] = false;
		$this->assertFalse( $this->sut->trigger( $recovery ), 'A deleted product must not reach the templates' );

		$recovery = $this->get_recovery();
		unset( $recovery['recovery_url'] );
		$this->assertFalse( $this->sut->trigger( $recovery ), 'An email without a recovery link must not send' );
	}

	/**
	 * Build a recovery payload with one saved simple product.
	 *
	 * @return array
	 */
	private function get_recovery(): array {
		$product = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Blue <b>mug</b>' ) );

		return array(
			'email'        => 'shopper@example.com',
			'items'        => array(
				array(
					'product'  => $product,
					'quantity' => 2,
				),
			),
			'recovery_url' => 'https://example.org/?checkout-link=true&products=' . $product->get_id() . ':2',
		);
	}

	/**
	 * @testdox Should send to the recipient with escaped item names and the recovery link.
	 */
	public function test_trigger_sends_email(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';
		$mailer             = tests_retrieve_phpmailer_instance();

		$sent = $this->sut->trigger( $this->get_recovery() );

		$this->assertTrue( $sent, 'trigger() should report a successful send' );
		$message = $mailer->get_sent();
		$this->assertSame( 'shopper@example.com', $message->to[0][0] );
		$this->assertStringContainsString( 'Blue &lt;b&gt;mug&lt;/b&gt;', $message->body, 'Product names must be escaped' );
		$this->assertStringContainsString( 'checkout-link=true', $message->body, 'Body must contain the recovery link' );
	}

	/**
	 * @testdox Should not send when the email is disabled.
	 */
	public function test_trigger_is_noop_when_disabled(): void {
		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$sent = $this->sut->trigger( $this->get_recovery() );

		$this->assertFalse( $sent );
		$this->assertCount( $before, $mailer->mock_sent, 'A disabled email must not send' );
	}

	/**
	 * @testdox Should not send without items or with a malformed payload.
	 *
	 * @testWith [{"email": "shopper@example.com", "items": [], "recovery_url": "https://example.org"}]
	 *           [{"email": "not-an-email", "items": [], "recovery_url": "https://example.org"}]
	 *           ["not-an-array"]
	 *
	 * @param mixed $recovery Payload.
	 */
	public function test_trigger_rejects_bad_payload( $recovery ): void {
		$this->sut->enabled = 'yes';

		$this->assertFalse( $this->sut->trigger( $recovery ) );
	}

	/**
	 * @testdox Should fill dummy items and a link when prepared for preview.
	 */
	public function test_prepare_preview_fills_dummy_data(): void {
		$result = $this->sut->handle_woocommerce_prepare_email_for_preview( $this->sut );

		$this->assertSame( $this->sut, $result );
		$this->assertNotEmpty( $this->sut->items, 'Preview needs items to render' );
		$this->assertInstanceOf( WC_Product::class, $this->sut->items[0]['product'] );
		$this->assertNotSame( '', $this->sut->recovery_url );
	}

	/**
	 * @testdox Should leave other emails untouched when prepared for preview.
	 */
	public function test_prepare_preview_ignores_other_emails(): void {
		$other = new WC_Email_Customer_Processing_Order();

		$this->sut->handle_woocommerce_prepare_email_for_preview( $other );

		$this->assertSame( array(), $this->sut->items );
	}

	/**
	 * @testdox Should render items only for this email in the block content area.
	 */
	public function test_block_content_only_for_own_email(): void {
		$recovery                = $this->get_recovery();
		$this->sut->items        = $recovery['items'];
		$this->sut->recovery_url = $recovery['recovery_url'];

		ob_start();
		$this->sut->handle_woocommerce_email_general_block_content( false, false, new WC_Email_Customer_Processing_Order() );
		$other = ob_get_clean();

		ob_start();
		$this->sut->handle_woocommerce_email_general_block_content( false, false, new WC_Email_Customer_Cart_Recovery() );
		$other_instance = ob_get_clean();

		ob_start();
		$this->sut->handle_woocommerce_email_general_block_content( false, false, $this->sut );
		$own = ob_get_clean();

		$this->assertSame( '', $other, 'Must not print into other emails' );
		$this->assertSame( '', $other_instance, 'Must not print for another instance of this email' );
		$this->assertStringContainsString( 'checkout-link=true', $own );
	}
}
