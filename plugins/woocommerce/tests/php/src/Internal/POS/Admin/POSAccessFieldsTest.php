<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Admin;

use Automattic\WooCommerce\Internal\POS\Admin\POSAccessFields;
use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the shared POS preset + PIN field helper.
 *
 * Covers validate() input rules and persist() side effects — the two methods
 * each callsite (UserFormIntegration, the staff edit screen) delegates to.
 */
class POSAccessFieldsTest extends WC_Unit_Test_Case {

	/**
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * @var int[]
	 */
	private array $extra_user_ids = array();

	/**
	 * Boot a fresh PIN service per test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->pin_service = new POSPinService();
	}

	/**
	 * Clean up users created during the test.
	 */
	public function tearDown(): void {
		foreach ( $this->extra_user_ids as $id ) {
			wp_delete_user( $id );
		}
		$this->extra_user_ids = array();
		parent::tearDown();
	}

	/**
	 * @testdox render echoes the preset dropdown and PIN input.
	 */
	public function test_render_outputs_preset_and_pin_inputs(): void {
		ob_start();
		POSAccessFields::render();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'name="' . POSAccessFields::FIELD_PRESET . '"', $html );
		$this->assertStringContainsString( 'name="' . POSAccessFields::FIELD_PIN . '"', $html );
		$this->assertStringContainsString( 'required', $html );
	}

	/**
	 * @testdox render in optional-PIN mode drops the required attribute on the PIN input and shows the "leave blank" copy.
	 */
	public function test_render_pin_optional_mode(): void {
		ob_start();
		POSAccessFields::render( Capabilities::POS_PRESET_CASHIER, true, true );
		$html = (string) ob_get_clean();

		// Isolate the PIN <input> markup so the preset <select>'s `required`
		// attribute doesn't confuse the assertion.
		$this->assertSame(
			1,
			preg_match(
				'#<input\b[^>]*\bname="' . preg_quote( POSAccessFields::FIELD_PIN, '#' ) . '"[^>]*>#',
				$html,
				$matches
			),
			'PIN input must be present in the rendered HTML.'
		);
		$this->assertStringNotContainsString(
			'required',
			$matches[0],
			'PIN input must not carry the required attribute when $pin_optional is true.'
		);
		$this->assertStringContainsString( 'Leave blank', $html );
	}

	/**
	 * @testdox validate rejects an invalid preset.
	 */
	public function test_validate_invalid_preset(): void {
		$result = POSAccessFields::validate( $this->pin_service, 'bogus', '1234' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_invalid_preset', $result->get_error_code() );
	}

	/**
	 * @testdox validate rejects a malformed PIN.
	 */
	public function test_validate_bad_pin_format(): void {
		$result = POSAccessFields::validate( $this->pin_service, Capabilities::POS_PRESET_CASHIER, 'abc' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_invalid_pin_format', $result->get_error_code() );
	}

	/**
	 * @testdox validate rejects a PIN already used by another POS user.
	 */
	public function test_validate_pin_in_use(): void {
		$other                  = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $other;
		Capabilities::set_pos_preset( $other, Capabilities::POS_PRESET_CASHIER );
		$this->pin_service->set_pin( $other, '1234' );

		$result = POSAccessFields::validate( $this->pin_service, Capabilities::POS_PRESET_CASHIER, '1234' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_pin_in_use', $result->get_error_code() );
	}

	/**
	 * @testdox validate accepts a blank PIN when pin_optional is true.
	 */
	public function test_validate_blank_pin_optional(): void {
		$result = POSAccessFields::validate(
			$this->pin_service,
			Capabilities::POS_PRESET_CASHIER,
			'',
			0,
			true
		);

		$this->assertNull( $result );
	}

	/**
	 * @testdox validate rejects a blank PIN when pin_optional is false.
	 */
	public function test_validate_blank_pin_required(): void {
		$result = POSAccessFields::validate( $this->pin_service, Capabilities::POS_PRESET_CASHIER, '' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_invalid_pin_format', $result->get_error_code() );
	}

	/**
	 * @testdox validate ignores the candidate user when checking PIN uniqueness.
	 *
	 * Re-saving the same PIN for the same user must succeed in the edit flow,
	 * so the uniqueness scan has to exclude the candidate.
	 */
	public function test_validate_excludes_candidate_user(): void {
		$existing               = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $existing;
		Capabilities::set_pos_preset( $existing, Capabilities::POS_PRESET_MANAGER );
		$this->pin_service->set_pin( $existing, '9999' );

		$result = POSAccessFields::validate(
			$this->pin_service,
			Capabilities::POS_PRESET_MANAGER,
			'9999',
			$existing,
			true
		);

		$this->assertNull( $result, 'Re-saving the same PIN for the same user must validate.' );
	}

	/**
	 * @testdox persist sets the preset and PIN on success.
	 */
	public function test_persist_happy_path(): void {
		$user_id                = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $user_id;

		$result = POSAccessFields::persist( $this->pin_service, $user_id, Capabilities::POS_PRESET_MANAGER, '7777' );

		$this->assertNull( $result );
		$this->assertSame( Capabilities::POS_PRESET_MANAGER, Capabilities::get_pos_preset( $user_id ) );
		$this->assertTrue( $this->pin_service->has_pin( $user_id ) );
		$this->assertTrue( Capabilities::has_pos_access( $user_id ) );
	}

	/**
	 * @testdox persist rejects an invalid preset before touching state.
	 */
	public function test_persist_rejects_invalid_preset(): void {
		$user_id                = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $user_id;

		$result = POSAccessFields::persist( $this->pin_service, $user_id, 'bogus', '1234' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_invalid_preset', $result->get_error_code() );
		$this->assertNull( Capabilities::get_pos_preset( $user_id ) );
		$this->assertFalse( $this->pin_service->has_pin( $user_id ) );
	}

	/**
	 * @testdox persist returns the PIN error and leaves preset alone on PIN write failure.
	 */
	public function test_persist_pin_collision_does_not_set_preset(): void {
		$existing               = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $existing;
		Capabilities::set_pos_preset( $existing, Capabilities::POS_PRESET_CASHIER );
		$this->pin_service->set_pin( $existing, '4242' );

		$new_user               = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $new_user;

		$result = POSAccessFields::persist( $this->pin_service, $new_user, Capabilities::POS_PRESET_MANAGER, '4242' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_pin_in_use', $result->get_error_code() );
		$this->assertNull(
			Capabilities::get_pos_preset( $new_user ),
			'Preset must not be set when the PIN write fails.'
		);
	}

	/**
	 * @testdox persist leaves the existing PIN untouched when the input PIN is blank.
	 *
	 * This is the edit flow: an admin can change just the preset without re-entering
	 * the PIN. persist() should set the new preset and leave the stored PIN as-is.
	 */
	public function test_persist_blank_pin_does_not_touch_existing_pin(): void {
		$user_id                = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->extra_user_ids[] = $user_id;
		Capabilities::set_pos_preset( $user_id, Capabilities::POS_PRESET_CASHIER );
		$this->pin_service->set_pin( $user_id, '1111' );
		$original_record = $this->pin_service->get_public_pin_record( $user_id );

		$result = POSAccessFields::persist( $this->pin_service, $user_id, Capabilities::POS_PRESET_MANAGER, '' );

		$this->assertNull( $result );
		$this->assertSame( Capabilities::POS_PRESET_MANAGER, Capabilities::get_pos_preset( $user_id ) );
		$this->assertSame(
			$original_record,
			$this->pin_service->get_public_pin_record( $user_id ),
			'PIN record must be unchanged when the new PIN is blank.'
		);
	}
}
