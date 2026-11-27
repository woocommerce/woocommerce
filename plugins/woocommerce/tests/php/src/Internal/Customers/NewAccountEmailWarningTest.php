<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Customers;

use Automattic\WooCommerce\Internal\Customers\NewAccountEmailWarning;
use WC_Unit_Test_Case;

/**
 * Tests for the NewAccountEmailWarning class.
 */
class NewAccountEmailWarningTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NewAccountEmailWarning
	 */
	private $sut;

	/**
	 * The New account email, so its enabled state can be set and restored.
	 *
	 * @var \WC_Email
	 */
	private $email;

	/**
	 * The email's enabled state before the test.
	 *
	 * @var string
	 */
	private $original_enabled;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut              = new NewAccountEmailWarning();
		$this->email            = WC()->mailer()->get_emails()['WC_Email_Customer_New_Account'];
		$this->original_enabled = $this->email->enabled;

		foreach ( array( 'woocommerce_enable_signup_and_login_from_checkout', 'woocommerce_enable_myaccount_registration', 'woocommerce_enable_delayed_account_creation' ) as $option ) {
			update_option( $option, 'no' );
		}
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->email->enabled = $this->original_enabled;
		parent::tearDown();
	}

	/**
	 * @testdox No warning while the New account email is enabled.
	 */
	public function test_no_warning_when_email_enabled(): void {
		$this->email->enabled = 'yes';
		update_option( 'woocommerce_enable_myaccount_registration', 'yes' );

		$this->assertFalse( $this->sut->should_warn() );
	}

	/**
	 * @testdox No warning when the email is disabled but customers can't create accounts.
	 */
	public function test_no_warning_when_account_creation_is_off(): void {
		$this->email->enabled = 'no';

		$this->assertFalse( $this->sut->should_warn() );
	}

	/**
	 * @testdox Warns when the email is disabled and any account creation option is on.
	 *
	 * @testWith ["woocommerce_enable_signup_and_login_from_checkout"]
	 *           ["woocommerce_enable_myaccount_registration"]
	 *           ["woocommerce_enable_delayed_account_creation"]
	 *
	 * @param string $option Account creation option to turn on.
	 */
	public function test_warns_when_email_disabled_and_accounts_can_be_created( string $option ): void {
		$this->email->enabled = 'no';
		update_option( $option, 'yes' );

		$this->assertTrue( $this->sut->should_warn() );
	}
}
