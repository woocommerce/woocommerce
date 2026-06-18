<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsRemoteNoteService;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsRemoteNoteService class.
 */
class WooPaymentsRemoteNoteServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should create WooPayments remote inbox notes with mapped actions.
	 */
	public function test_put_note_creates_remote_note_with_mapped_actions(): void {
		$note_slug = 'h30-note-' . wp_generate_uuid4();
		$sut       = new WooPaymentsRemoteNoteService();

		$result = $sut->put_note(
			array(
				'name'    => $note_slug,
				'title'   => 'Account notice',
				'content' => 'Refresh your account details.',
				'actions' => array(
					'settings' => array(
						'label'   => 'Open settings',
						'url'     => 'wcpay_settings',
						'primary' => true,
					),
					'orders'   => array(
						'label'        => 'Open orders',
						'url'          => 'admin.php?page=wc-orders',
						'url_is_admin' => true,
						'status'       => Note::E_WC_ADMIN_NOTE_UNACTIONED,
					),
				),
			)
		);

		$note = Notes::get_note_by_name( WooPaymentsRemoteNoteService::NOTE_NAME_PREFIX . $note_slug );

		$this->assertTrue( $result );
		$this->assertInstanceOf( Note::class, $note );
		$this->assertSame( 'Account notice', $note->get_title() );
		$this->assertSame( 'Refresh your account details.', $note->get_content() );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_INFORMATIONAL, $note->get_type() );
		$this->assertSame( 'woocommerce-payments', $note->get_source() );

		$actions = $note->get_actions( 'edit' );
		$this->assertCount( 2, $actions );
		$this->assertSame( WooPaymentsRemoteNoteService::NOTE_NAME_PREFIX . $note_slug . '-settings', $actions[0]->name );
		$this->assertStringContainsString( 'page=wc-settings', $actions[0]->query );
		$this->assertStringContainsString( 'tab=checkout', $actions[0]->query );
		$this->assertSame( WooPaymentsRemoteNoteService::NOTE_NAME_PREFIX . $note_slug . '-orders', $actions[1]->name );
		$this->assertStringContainsString( 'admin.php?page=wc-orders', $actions[1]->query );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $actions[1]->status );
	}

	/**
	 * @testdox Should dedupe remote notes by their generated note name.
	 */
	public function test_put_note_dedupes_by_note_name(): void {
		$note_slug = 'h30-dedupe-' . wp_generate_uuid4();
		$sut       = new WooPaymentsRemoteNoteService();
		$payload   = array(
			'name'    => $note_slug,
			'title'   => 'Duplicate notice',
			'content' => 'Only one copy should be stored.',
		);

		$this->assertTrue( $sut->put_note( $payload ) );
		$this->assertFalse( $sut->put_note( $payload ) );
	}

	/**
	 * @testdox Should generate note names when a remote name is not provided.
	 */
	public function test_put_note_generates_note_name_from_title_and_content(): void {
		$sut      = new WooPaymentsRemoteNoteService();
		$title    = 'Generated notice';
		$content  = 'Generated note content.';
		$expected = WooPaymentsRemoteNoteService::NOTE_NAME_PREFIX . md5( $title . $content );

		$sut->put_note(
			array(
				'title'   => $title,
				'content' => $content,
			)
		);

		$this->assertInstanceOf( Note::class, Notes::get_note_by_name( $expected ) );
	}

	/**
	 * @testdox Should fail closed for invalid remote notes.
	 */
	public function test_put_note_fails_closed_for_invalid_payloads(): void {
		$sut = new WooPaymentsRemoteNoteService();

		$this->expectException( InvalidArgumentException::class );

		$sut->put_note(
			array(
				'title'   => 'Invalid action',
				'content' => 'Action URL is not allowed.',
				'actions' => array(
					'external' => array(
						'label' => 'External',
						'url'   => 'https://example.com',
					),
				),
			)
		);
	}

	/**
	 * @testdox Should fail closed when a note cannot be persisted.
	 */
	public function test_put_note_fails_closed_when_persistence_fails(): void {
		$sut = new class() extends WooPaymentsRemoteNoteService {
			/**
			 * Simulate a failed note-store create call.
			 *
			 * @param Note $note Note object.
			 * @return void
			 */
			protected function persist_note( Note $note ): void {}
		};

		$this->expectException( \RuntimeException::class );

		$sut->put_note(
			array(
				'name'    => 'h30-failed-persist-' . wp_generate_uuid4(),
				'title'   => 'Lost note',
				'content' => 'This note should fail closed.',
			)
		);
	}
}
