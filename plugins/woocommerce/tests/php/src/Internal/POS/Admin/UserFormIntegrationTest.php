<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Admin;

use Automattic\WooCommerce\Internal\POS\Admin\POSAccessFields;
use Automattic\WooCommerce\Internal\POS\Admin\UserFormIntegration;
use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the wp-admin → Users → Add New POS integration.
 *
 * Each test seeds the relevant superglobals to mimic the POST that wp-admin
 * would deliver to user_profile_update_errors / user_register / wp_redirect,
 * exercises the corresponding hook handler, and asserts the resulting WP state.
 */
class UserFormIntegrationTest extends WC_Unit_Test_Case {

	/**
	 * @var UserFormIntegration
	 */
	private UserFormIntegration $sut;

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * Users created mid-test that should be cleaned up.
	 *
	 * @var int[]
	 */
	private array $extra_user_ids = array();

	/**
	 * Set up the SUT and a fresh PIN service per test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->pin_service = new POSPinService();
		$this->sut         = new UserFormIntegration();
		$this->sut->init( $this->pin_service );
	}

	/**
	 * Delete users created mid-test and clear POS-related superglobal state.
	 */
	public function tearDown(): void {
		foreach ( $this->extra_user_ids as $id ) {
			wp_delete_user( $id );
		}
		$this->extra_user_ids = array();

		unset(
			$_GET[ UserFormIntegration::REQUEST_FLAG_PARAM ],
			$_POST[ UserFormIntegration::REQUEST_FLAG_PARAM ],
			$_POST[ POSAccessFields::FIELD_PRESET ],
			$_POST[ POSAccessFields::FIELD_PIN ],
			$_POST[ UserFormIntegration::NONCE_FIELD_NAME ]
		);

		parent::tearDown();
	}

	/**
	 * Populate $_POST with a valid POS add submission for the given user.
	 *
	 * @param string $preset The preset to assign.
	 * @param string $pin    The PIN to set.
	 */
	private function seed_valid_post( string $preset, string $pin ): void {
		$_POST[ UserFormIntegration::REQUEST_FLAG_PARAM ] = '1';
		$_POST[ POSAccessFields::FIELD_PRESET ]           = $preset;
		$_POST[ POSAccessFields::FIELD_PIN ]              = $pin;
		$_POST[ UserFormIntegration::NONCE_FIELD_NAME ]   = wp_create_nonce( UserFormIntegration::NONCE_ACTION );
	}

	/**
	 * @testdox validate_profile_errors does nothing when the request is not flagged as POS.
	 */
	public function test_validate_skips_non_pos_request(): void {
		$errors = new WP_Error();
		$this->sut->validate_profile_errors( $errors, false, new \stdClass() );

		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * @testdox validate_profile_errors does nothing when the action is editing an existing user.
	 */
	public function test_validate_skips_existing_user_update(): void {
		$this->seed_valid_post( Capabilities::POS_PRESET_CASHIER, '1234' );

		$errors = new WP_Error();
		$this->sut->validate_profile_errors( $errors, true, new \stdClass() );

		$this->assertFalse(
			$errors->has_errors(),
			'Existing-user updates must not run POS validation.'
		);
	}

	/**
	 * @testdox validate_profile_errors rejects an invalid preset.
	 */
	public function test_validate_rejects_invalid_preset(): void {
		$this->seed_valid_post( 'bogus_preset', '1234' );

		$errors = new WP_Error();
		$this->sut->validate_profile_errors( $errors, false, new \stdClass() );

		$this->assertTrue( $errors->has_errors() );
		$this->assertNotEmpty( $errors->get_error_messages( 'woocommerce_pos_invalid_preset' ) );
	}

	/**
	 * @testdox validate_profile_errors rejects a malformed PIN.
	 */
	public function test_validate_rejects_bad_pin_format(): void {
		$this->seed_valid_post( Capabilities::POS_PRESET_CASHIER, 'abc' );

		$errors = new WP_Error();
		$this->sut->validate_profile_errors( $errors, false, new \stdClass() );

		$this->assertNotEmpty( $errors->get_error_messages( 'woocommerce_pos_invalid_pin_format' ) );
	}

	/**
	 * @testdox validate_profile_errors rejects a PIN already used by another POS-access user.
	 */
	public function test_validate_rejects_pin_in_use(): void {
		$existing               = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $existing;
		Capabilities::set_pos_preset( $existing, Capabilities::POS_PRESET_CASHIER );
		$this->pin_service->set_pin( $existing, '1234' );

		$this->seed_valid_post( Capabilities::POS_PRESET_CASHIER, '1234' );

		$errors = new WP_Error();
		$this->sut->validate_profile_errors( $errors, false, new \stdClass() );

		$this->assertNotEmpty( $errors->get_error_messages( 'woocommerce_pos_pin_in_use' ) );
	}

	/**
	 * @testdox validate_profile_errors rejects when the POS nonce is missing or invalid.
	 */
	public function test_validate_rejects_invalid_nonce(): void {
		$_POST[ UserFormIntegration::REQUEST_FLAG_PARAM ] = '1';
		$_POST[ POSAccessFields::FIELD_PRESET ]           = Capabilities::POS_PRESET_CASHIER;
		$_POST[ POSAccessFields::FIELD_PIN ]              = '1234';
		// Deliberately omit the nonce field.

		$errors = new WP_Error();
		$this->sut->validate_profile_errors( $errors, false, new \stdClass() );

		$this->assertNotEmpty( $errors->get_error_messages( 'woocommerce_pos_invalid_nonce' ) );
	}

	/**
	 * @testdox apply_pos_settings persists the preset and PIN on a freshly created user.
	 */
	public function test_apply_persists_preset_and_pin(): void {
		$user_id                = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $user_id;

		$this->seed_valid_post( Capabilities::POS_PRESET_MANAGER, '5678' );

		$this->sut->apply_pos_settings( $user_id );

		$this->assertSame(
			Capabilities::POS_PRESET_MANAGER,
			Capabilities::get_pos_preset( $user_id )
		);
		$this->assertTrue( $this->pin_service->has_pin( $user_id ) );
		$this->assertTrue( Capabilities::has_pos_access( $user_id ) );
	}

	/**
	 * @testdox apply_pos_settings no-ops when the request is not flagged as POS.
	 */
	public function test_apply_skips_non_pos_request(): void {
		$user_id                = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $user_id;

		// No flag, no preset, no nonce in $_POST.
		$this->sut->apply_pos_settings( $user_id );

		$this->assertNull( Capabilities::get_pos_preset( $user_id ) );
		$this->assertFalse( $this->pin_service->has_pin( $user_id ) );
	}

	/**
	 * @testdox apply_pos_settings leaves the user without POS access if the PIN write fails.
	 */
	public function test_apply_does_not_set_preset_when_pin_write_fails(): void {
		// Seed a conflicting PIN on an existing POS user.
		$existing               = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $existing;
		Capabilities::set_pos_preset( $existing, Capabilities::POS_PRESET_CASHIER );
		$this->pin_service->set_pin( $existing, '4321' );

		$new_user_id            = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $new_user_id;

		$this->seed_valid_post( Capabilities::POS_PRESET_MANAGER, '4321' );

		$this->sut->apply_pos_settings( $new_user_id );

		$this->assertNull(
			Capabilities::get_pos_preset( $new_user_id ),
			'Preset must not be set when the PIN write fails.'
		);
		$this->assertFalse(
			$this->pin_service->has_pin( $new_user_id ),
			'PIN must not be persisted when there is a uniqueness collision.'
		);
	}

	/**
	 * @testdox filter_default_role returns pos_staff for the flagged request, otherwise passes through.
	 */
	public function test_filter_default_role_overrides_only_for_pos_request(): void {
		$this->assertFalse(
			$this->sut->filter_default_role( false ),
			'Without the POS flag the filter must pass through the original value.'
		);

		$_GET[ UserFormIntegration::REQUEST_FLAG_PARAM ] = '1';

		$this->assertSame(
			Capabilities::POS_STAFF_ROLE,
			$this->sut->filter_default_role( false ),
			'With the POS flag the default_role lookup must short-circuit to pos_staff.'
		);
	}

	/**
	 * @testdox filter_post_add_redirect rewrites only after a successful apply.
	 */
	public function test_filter_post_add_redirect_only_after_successful_apply(): void {
		// Without a prior successful apply, the redirect is left alone.
		$pass_through = 'users.php?update=add&id=42';
		$this->assertSame( $pass_through, $this->sut->filter_post_add_redirect( $pass_through ) );

		// Drive a successful apply, which arms the redirect filter.
		$user_id                = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $user_id;
		$this->seed_valid_post( Capabilities::POS_PRESET_CASHIER, '2468' );
		$this->sut->apply_pos_settings( $user_id );

		$rewritten = $this->sut->filter_post_add_redirect( $pass_through );

		$this->assertStringContainsString( 'page=wc-settings', $rewritten );
		$this->assertStringContainsString( 'tab=point-of-sale', $rewritten );
		$this->assertStringContainsString( 'section=staff', $rewritten );
		$this->assertStringContainsString( 'added=1', $rewritten );

		// The filter must consume the one-shot flag — a second call passes through.
		$this->assertSame( $pass_through, $this->sut->filter_post_add_redirect( $pass_through ) );
	}
}
