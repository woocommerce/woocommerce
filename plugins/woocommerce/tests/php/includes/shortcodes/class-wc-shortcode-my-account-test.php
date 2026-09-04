<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Shortcode_My_Account.
 */
class WC_Shortcode_My_Account_Test extends WC_Unit_Test_Case {

	/**
	 * User IDs for which an authentication cookie was generated.
	 *
	 * @var int[]
	 */
	private $auth_cookie_user_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		add_action( 'set_auth_cookie', array( $this, 'record_auth_cookie_user_id' ), 10, 4 );
		add_filter( 'send_auth_cookies', '__return_false' );
		add_filter( 'woocommerce_disable_password_change_notification', '__return_true' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'set_auth_cookie', array( $this, 'record_auth_cookie_user_id' ), 10 );
		remove_filter( 'send_auth_cookies', '__return_false' );
		remove_filter( 'woocommerce_disable_password_change_notification', '__return_true' );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Record the user ID when WordPress generates an authentication cookie.
	 *
	 * @param string $auth_cookie Authentication cookie value.
	 * @param int    $expire      Login grace period expiration.
	 * @param int    $expiration  Authentication cookie expiration.
	 * @param int    $user_id     User ID.
	 */
	public function record_auth_cookie_user_id( $auth_cookie, $expire, $expiration, $user_id ): void {
		$this->auth_cookie_user_ids[] = $user_id;
	}

	/**
	 * Reset a password without emitting a cookie header from the CLI test runner.
	 *
	 * @param WP_User $user     User whose password is being reset.
	 * @param string  $new_pass New password.
	 */
	private function reset_password( $user, $new_pass ): void {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- setcookie() cannot be mocked and the test bootstrap has already sent output.
		@WC_Shortcode_My_Account::reset_password( $user, $new_pass );
	}

	/**
	 * @testdox Password reset preserves the current user's login.
	 */
	public function test_reset_password_preserves_current_user_login(): void {
		$user = self::factory()->user->create_and_get();
		wp_set_current_user( $user->ID );

		$this->reset_password( $user, 'new-password' );

		$this->assertSame( array( $user->ID ), $this->auth_cookie_user_ids );
	}

	/**
	 * @testdox Password reset does not log in a logged-out user.
	 */
	public function test_reset_password_does_not_log_in_logged_out_user(): void {
		$user = self::factory()->user->create_and_get();
		wp_set_current_user( 0 );

		$this->reset_password( $user, 'new-password' );

		$this->assertEmpty( $this->auth_cookie_user_ids );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * @testdox Password reset does not replace a different user's login.
	 */
	public function test_reset_password_does_not_replace_different_user_login(): void {
		$current_user = self::factory()->user->create_and_get();
		$reset_user   = self::factory()->user->create_and_get();
		wp_set_current_user( $current_user->ID );

		$this->reset_password( $reset_user, 'new-password' );

		$this->assertEmpty( $this->auth_cookie_user_ids );
		$this->assertSame( $current_user->ID, get_current_user_id() );
	}
}
