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
		$this->expectException( NoteActionForbiddenException::class );
		$this->expectExceptionMessage( 'You do not have permissions to manage plugins. Please contact your site administrator.' );

		$this->sut->install_jp_and_wcs_plugins( $note );
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
		// Reaching the next line at all proves no exception was thrown; we record an
		// explicit assertion to keep PHPUnit from flagging this as a risky test.
		$this->sut->install_jp_and_wcs_plugins( $other_note );

		$this->addToAssertionCount( 1 );
	}
}
