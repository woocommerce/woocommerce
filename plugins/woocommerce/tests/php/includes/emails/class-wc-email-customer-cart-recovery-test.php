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
	 * @testdox Should return an empty list for a non-array multiselect value.
	 */
	public function test_validators_handle_non_array(): void {
		$this->assertSame( array(), $this->sut->validate_excluded_categories_field( 'excluded_categories', 'x' ) );
		$this->assertSame( array(), $this->sut->validate_excluded_roles_field( 'excluded_roles', null ) );
	}
}
