<?php
/**
 * WooPaymentsRemoteNoteService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use InvalidArgumentException;
use RuntimeException;

/**
 * Creates WooPayments remote inbox notes from provider notifications.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsRemoteNoteService {

	/**
	 * Remote note name prefix.
	 *
	 * @var string
	 */
	public const NOTE_NAME_PREFIX = 'wc-payments-remote-notes-';

	/**
	 * Put a remote note in the WooCommerce inbox if it does not already exist.
	 *
	 * @param array<string,mixed> $note_data Note data from the provider notification.
	 * @return bool True when a note was created.
	 * @throws RuntimeException When the note cannot be persisted.
	 */
	public function put_note( array $note_data ): bool {
		$note = $this->create_note( $note_data );

		if ( ! $this->can_note_be_added( $note->get_name() ) ) {
			return false;
		}

		$this->persist_note( $note );
		$stored_note = Notes::get_note_by_name( $note->get_name() );
		if ( ! $stored_note instanceof Note || 0 >= $stored_note->get_id() ) {
			throw new RuntimeException( 'WooPayments remote note could not be stored.' );
		}

		return true;
	}

	/**
	 * Persist the note.
	 *
	 * @param Note $note Note object.
	 * @return void
	 */
	protected function persist_note( Note $note ): void {
		Notes::load_data_store()->create( $note );
	}

	/**
	 * Create a Woo Admin note from provider note data.
	 *
	 * @param array<string,mixed> $note_data Note data.
	 * @return Note
	 * @throws InvalidArgumentException When the note data is invalid.
	 */
	private function create_note( array $note_data ): Note {
		if ( ! isset( $note_data['title'], $note_data['content'] ) || ! is_scalar( $note_data['title'] ) || ! is_scalar( $note_data['content'] ) ) {
			throw new InvalidArgumentException( 'Invalid note.' );
		}

		$title     = (string) $note_data['title'];
		$content   = (string) $note_data['content'];
		$name_part = isset( $note_data['name'] ) && is_scalar( $note_data['name'] )
			? (string) $note_data['name']
			: md5( $title . $content );
		$note_name = self::NOTE_NAME_PREFIX . $name_part;

		$note = new Note();
		$note->set_title( $title );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( $note_name );
		$note->set_source( 'woocommerce-payments' );

		if ( isset( $note_data['actions'] ) ) {
			if ( ! is_array( $note_data['actions'] ) ) {
				throw new InvalidArgumentException( 'Invalid note.' );
			}

			foreach ( $note_data['actions'] as $action_key => $action ) {
				$this->add_action( $note, $note_name, (string) $action_key, $action );
			}
		}

		return $note;
	}

	/**
	 * Add a validated action to a note.
	 *
	 * @param Note   $note       Note object.
	 * @param string $note_name  Note name.
	 * @param string $action_key Action key.
	 * @param mixed  $action     Action data.
	 * @return void
	 * @throws InvalidArgumentException When the action data is invalid.
	 */
	private function add_action( Note $note, string $note_name, string $action_key, $action ): void {
		if ( ! is_array( $action ) || ! isset( $action['label'], $action['url'] ) || ! is_scalar( $action['label'] ) || ! is_scalar( $action['url'] ) ) {
			throw new InvalidArgumentException( 'Invalid note.' );
		}

		if ( 'wcpay_settings' === (string) $action['url'] ) {
			$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woocommerce_payments' );
		} elseif ( ! empty( $action['url_is_admin'] ) ) {
			$url = admin_url( (string) $action['url'] );
		} else {
			throw new InvalidArgumentException( 'Invalid note.' );
		}

		$status  = isset( $action['status'] ) && is_scalar( $action['status'] ) ? (string) $action['status'] : Note::E_WC_ADMIN_NOTE_ACTIONED;
		$primary = (bool) ( $action['primary'] ?? false );

		$note->add_action(
			$note_name . '-' . $action_key,
			(string) $action['label'],
			$url,
			$status,
			$primary
		);
	}

	/**
	 * Tell whether a note name has not already been stored.
	 *
	 * @param string $note_name Note name.
	 * @return bool
	 */
	private function can_note_be_added( string $note_name ): bool {
		return false === Notes::get_note_by_name( $note_name );
	}
}
