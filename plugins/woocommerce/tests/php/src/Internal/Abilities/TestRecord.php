<?php
/**
 * Test record class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\InMemorySubject;

/**
 * A record a plugin stores in an option, changed through an in-memory write.
 */
class TestRecord implements InMemorySubject {

	/**
	 * Number of saves since the last reset.
	 *
	 * @var int
	 */
	public static $saves = 0;

	/**
	 * Record ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Note set through the extension field.
	 *
	 * @var string
	 */
	public $note;

	/**
	 * Create a record.
	 *
	 * @param int    $id    Record ID.
	 * @param string $title Title.
	 * @param string $note  Note.
	 */
	public function __construct( int $id, string $title, string $note = '' ) {
		$this->id    = $id;
		$this->title = $title;
		$this->note  = $note;
	}

	/**
	 * Load a stored record.
	 *
	 * @param mixed $id Record ID.
	 * @return self|null
	 */
	public static function load( $id ): ?self {
		$stored = get_option( 'test_record_' . (int) $id );
		return is_array( $stored ) ? new self( (int) $id, $stored['title'], $stored['note'] ) : null;
	}

	/**
	 * Current state.
	 *
	 * @return array
	 */
	public function snapshot(): array {
		return array(
			'id'    => $this->id,
			'title' => $this->title,
			'note'  => $this->note,
		);
	}

	/**
	 * Store the record.
	 *
	 * @return true
	 */
	public function save() {
		++self::$saves;
		update_option(
			'test_record_' . $this->id,
			array(
				'title' => $this->title,
				'note'  => $this->note,
			)
		);
		return true;
	}
}
