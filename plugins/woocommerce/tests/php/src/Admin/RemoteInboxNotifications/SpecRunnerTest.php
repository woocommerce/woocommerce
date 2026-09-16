<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\RemoteInboxNotifications;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner;
use WC_Unit_Test_Case;

/**
 * Tests for the end date a spec leaves on the note it creates.
 *
 * @covers \Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner::run_spec
 */
class SpecRunnerTest extends WC_Unit_Test_Case {

	private const SLUG = 'phpunit-spec-runner-note';

	/**
	 * @testdox A spec with an end date leaves it on the note.
	 */
	public function test_publish_before_time_is_stored_on_the_note(): void {
		$note = $this->run_spec(
			array(
				array(
					'type'           => 'publish_before_time',
					'publish_before' => '2999-01-01 00:00:00',
				),
			)
		);

		$this->assertSame(
			'2999-01-01 00:00:00',
			$note->get_content_data()->publish_before,
			'The note should keep the end date so it can expire without the spec.'
		);
	}

	/**
	 * @testdox A spec with no end date leaves none on the note.
	 */
	public function test_no_end_date_is_stored_when_the_spec_has_no_publish_before_time(): void {
		$note = $this->run_spec(
			array(
				array(
					'type'          => 'publish_after_time',
					'publish_after' => '2020-01-01 00:00:00',
				),
			)
		);

		$this->assertFalse(
			property_exists( $note->get_content_data(), 'publish_before' ),
			'A spec without an end date has nothing to store.'
		);
	}

	/**
	 * @testdox An end date nested in an or rule is not read as the spec's own.
	 */
	public function test_nested_publish_before_time_is_ignored(): void {
		$note = $this->run_spec(
			array(
				array(
					'type'     => 'or',
					'operands' => array(
						array(
							array(
								'type'           => 'publish_before_time',
								'publish_before' => '2999-01-01 00:00:00',
							),
						),
					),
				),
			)
		);

		$this->assertFalse(
			property_exists( $note->get_content_data(), 'publish_before' ),
			'A nested rule describes one branch, not when the spec itself ends.'
		);
	}

	/**
	 * @testdox An end date in the spec's content data is not read as the spec's own.
	 */
	public function test_publish_before_in_content_data_is_dropped(): void {
		$note = $this->run_spec(
			array(
				array(
					'type'          => 'publish_after_time',
					'publish_after' => '2020-01-01 00:00:00',
				),
			),
			array( 'publish_before' => '2999-01-01 00:00:00' )
		);

		$this->assertFalse(
			property_exists( $note->get_content_data(), 'publish_before' ),
			'The end date comes from the spec rules, not from content data.'
		);
	}

	/**
	 * @testdox A spec served again without its end date drops the date from the note.
	 */
	public function test_end_date_is_dropped_when_the_spec_stops_carrying_it(): void {
		$this->run_spec(
			array(
				array(
					'type'           => 'publish_before_time',
					'publish_before' => '2999-01-01 00:00:00',
				),
			)
		);

		$note = $this->run_spec( array() );

		$this->assertFalse(
			property_exists( $note->get_content_data(), 'publish_before' ),
			'A spec that no longer ends should leave no end date behind.'
		);
	}

	/**
	 * Run a spec with the given rules and return the note it created.
	 *
	 * @param array $rules        Spec rules.
	 * @param array $content_data Spec content data.
	 * @return Note The created note.
	 */
	private function run_spec( array $rules, array $content_data = array() ): Note {
		$spec = json_decode(
			(string) wp_json_encode(
				array(
					'slug'         => self::SLUG,
					'content_data' => (object) $content_data,
					'type'         => Note::E_WC_ADMIN_NOTE_UPDATE,
					'status'       => Note::E_WC_ADMIN_NOTE_UNACTIONED,
					'source'       => 'woocommerce.com',
					'locales'      => array(
						array(
							'locale'  => 'en_US',
							'title'   => 'Test note',
							'content' => 'Test content',
						),
					),
					'actions'      => array(),
					'rules'        => $rules,
				)
			)
		);

		SpecRunner::run_spec( $spec, new \stdClass() );

		$note = Notes::get_note_by_name( self::SLUG );
		$this->assertInstanceOf( Note::class, $note, 'The spec should have created a note.' );

		return $note;
	}
}
