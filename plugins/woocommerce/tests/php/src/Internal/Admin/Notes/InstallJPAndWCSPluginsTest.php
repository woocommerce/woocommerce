<?php
/**
 * Tests for InstallJPAndWCSPlugins class.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Internal\Admin\Notes\InstallJPAndWCSPlugins;
use Automattic\WooCommerce\Internal\Admin\Notes\NoteActionForbiddenException;
use WC_Unit_Test_Case;

/**
 * Class InstallJPAndWCSPluginsTest
 */
class InstallJPAndWCSPluginsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var InstallJPAndWCSPlugins
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new InstallJPAndWCSPlugins();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Should throw when triggered by a user without the install_plugins capability (e.g. shop_manager).
	 */
	public function test_install_jp_and_wcs_plugins_throws_when_user_lacks_install_plugins_cap(): void {
		$shop_manager_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $shop_manager_id );

		$this->assertFalse(
			current_user_can( 'install_plugins' ),
			'shop_manager should not have install_plugins capability'
		);

		$note = new Note();
		$note->set_name( InstallJPAndWCSPlugins::NOTE_NAME );

		// The handler must throw the typed exception — a silent return would let
		// Notes::trigger_note_action() continue and persist E_WC_ADMIN_NOTE_ACTIONED on
		// the note despite no install running. Asserting on the specific class is what
		// lets NoteActions::trigger_note_action() map this and only this to a 403.
		// The exception class is the behavioral guarantee; a substring match on the
		// message keeps the test from breaking on legitimate copy rewords.
		$this->expectException( NoteActionForbiddenException::class );
		$this->expectExceptionMessageMatches( '/permissions to manage plugins/' );

		$this->sut->install_jp_and_wcs_plugins( $note );
	}

	/**
	 * @testdox Should not throw NoteActionForbiddenException when triggered by a user with the install_plugins capability (e.g. administrator).
	 */
	public function test_install_jp_and_wcs_plugins_does_not_throw_for_admin_with_cap(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$this->assertTrue(
			current_user_can( 'install_plugins' ),
			'administrator should have install_plugins capability'
		);

		$note = new Note();
		$note->set_name( InstallJPAndWCSPlugins::NOTE_NAME );

		// We assert only on the cap gate: the handler must NOT throw
		// NoteActionForbiddenException for a user that has the capability. The handler
		// continues into install_and_activate_plugin() which calls the real
		// OnboardingPlugins installer and may fail for environmental reasons
		// (network, missing dependencies in the unit-test runtime). Those failures
		// are not under test here; only the cap-check direction matters. The
		// assertTrue() precondition above keeps PHPUnit from marking the test risky.
		try {
			$this->sut->install_jp_and_wcs_plugins( $note );
		} catch ( NoteActionForbiddenException $e ) {
			$this->fail( 'Cap check should pass for admin, but threw NoteActionForbiddenException: ' . $e->getMessage() );
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- intentional: only the cap-check direction is under test, installer failures are out of scope.
			// Installer failures in the unit-test environment are expected and ignored.
		}
	}

	/**
	 * @testdox Should ignore notes whose name does not match the handler's note name.
	 */
	public function test_install_jp_and_wcs_plugins_ignores_wrong_note(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$other_note = new Note();
		$other_note->set_name( 'some-other-note' );

		// Wrong-note guard must short-circuit before any cap check or install attempt.
		// expectNotToPerformAssertions() declares the intent in the Arrange phase:
		// reaching the next line without an exception is the entire success condition.
		$this->expectNotToPerformAssertions();

		$this->sut->install_jp_and_wcs_plugins( $other_note );
	}
}
