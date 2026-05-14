<?php
/**
 * Class WC_Form_Handler_Save_Account_Details_Test file.
 *
 * @package WooCommerce\Tests\WC_Form_Handler.
 */

/**
 * Tests for WC_Form_Handler::save_account_details() display-name-as-email validation.
 *
 * Regression coverage for https://github.com/woocommerce/woocommerce/issues/32001 :
 * when a user registers with their email as their username, the display name is
 * pre-populated from the username (i.e. the email address). Submitting the
 * account-details form without altering the display name should not be blocked
 * by the privacy-concern check, because the display name is not actually being
 * changed to an email.
 */
class WC_Form_Handler_Save_Account_Details_Test extends \WC_Unit_Test_Case {

	/**
	 * Backup of the $_POST and $_REQUEST superglobals.
	 *
	 * @var array
	 */
	private $post_backup;

	/**
	 * Backup of the $_REQUEST superglobal.
	 *
	 * @var array
	 */
	private $request_backup;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->post_backup    = $_POST;
		$this->request_backup = $_REQUEST;

		wc_clear_notices();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_POST    = $this->post_backup;
		$_REQUEST = $this->request_backup;

		wc_clear_notices();
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Build a valid $_POST payload for the save_account_details handler.
	 *
	 * @param array $overrides Field overrides.
	 * @return array
	 */
	private function build_post_payload( array $overrides = array() ): array {
		$defaults = array(
			'action'                          => 'save_account_details',
			'_wpnonce'                        => wp_create_nonce( 'save_account_details' ),
			'save-account-details-nonce'      => wp_create_nonce( 'save_account_details' ),
			'account_first_name'              => 'Jane',
			'account_last_name'               => 'Doe',
			'account_display_name'            => 'Jane',
			'account_email'                   => 'jane@example.com',
			'password_current'                => '',
			'password_1'                      => '',
			'password_2'                      => '',
		);

		return array_merge( $defaults, $overrides );
	}

	/**
	 * Invoke the form handler via its public static entry point.
	 */
	private function invoke_handler(): void {
		WC_Form_Handler::save_account_details();
	}

	/**
	 * Returns true if the privacy-concern notice has been queued.
	 *
	 * @return bool
	 */
	private function has_display_name_email_error(): bool {
		$notices = wc_get_notices( 'error' );
		foreach ( $notices as $notice ) {
			$message = is_array( $notice ) ? ( $notice['notice'] ?? '' ) : $notice;
			if ( false !== strpos( (string) $message, 'Display name cannot be changed to email address' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @testdox Should not emit the email-as-display-name error when the display name is unchanged and already an email.
	 */
	public function test_unchanged_email_display_name_does_not_trigger_error(): void {
		$email   = 'qa-test-user@example.com';
		$user_id = self::factory()->user->create(
			array(
				'user_login'   => $email,
				'user_email'   => $email,
				'display_name' => $email,
				'role'         => 'customer',
			)
		);
		wp_set_current_user( $user_id );

		$_POST = $_REQUEST = $this->build_post_payload(
			array(
				'account_first_name'   => 'Jane',
				'account_last_name'    => 'Doe',
				'account_display_name' => $email,
				'account_email'        => $email,
			)
		);

		$this->invoke_handler();

		$this->assertFalse(
			$this->has_display_name_email_error(),
			'Unchanged email-as-display-name submissions must not trigger the privacy-concern notice.'
		);

		$refreshed = get_user_by( 'id', $user_id );
		$this->assertSame( 'Jane', $refreshed->first_name, 'First name should be saved when display name is unchanged.' );
		$this->assertSame( 'Doe', $refreshed->last_name, 'Last name should be saved when display name is unchanged.' );
	}

	/**
	 * @testdox Should emit the email-as-display-name error when the display name is actively being changed to an email.
	 */
	public function test_changing_display_name_to_email_triggers_error(): void {
		$user_id = self::factory()->user->create(
			array(
				'user_login'   => 'janedoe',
				'user_email'   => 'jane@example.com',
				'display_name' => 'Jane Doe',
				'role'         => 'customer',
			)
		);
		wp_set_current_user( $user_id );

		$_POST = $_REQUEST = $this->build_post_payload(
			array(
				'account_first_name'   => 'Jane',
				'account_last_name'    => 'Doe',
				'account_display_name' => 'jane@example.com',
				'account_email'        => 'jane@example.com',
			)
		);

		$this->invoke_handler();

		$this->assertTrue(
			$this->has_display_name_email_error(),
			'Changing the display name to an email address must still trigger the privacy-concern notice.'
		);
	}

	/**
	 * @testdox Should not emit the email-as-display-name error for a normal display-name update.
	 */
	public function test_normal_display_name_update_does_not_trigger_error(): void {
		$user_id = self::factory()->user->create(
			array(
				'user_login'   => 'janedoe',
				'user_email'   => 'jane@example.com',
				'display_name' => 'Jane Doe',
				'role'         => 'customer',
			)
		);
		wp_set_current_user( $user_id );

		$_POST = $_REQUEST = $this->build_post_payload(
			array(
				'account_first_name'   => 'Jane',
				'account_last_name'    => 'Doe',
				'account_display_name' => 'Janey',
				'account_email'        => 'jane@example.com',
			)
		);

		$this->invoke_handler();

		$this->assertFalse(
			$this->has_display_name_email_error(),
			'A plain display-name update must not be flagged as an email.'
		);
	}
}
