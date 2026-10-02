<?php
/**
 * Test record write ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\InMemoryWriteAbility;

/**
 * A plugin's write ability that renames a record.
 */
class TestRecordWriteAbility extends InMemoryWriteAbility {

	/**
	 * Object type.
	 */
	public static function object_type(): string {
		return 'test_record';
	}

	/**
	 * Load the record.
	 *
	 * @param array $input Ability input.
	 * @return TestRecord|null
	 */
	public function subject( array $input ) {
		return TestRecord::load( $input['id'] );
	}

	/**
	 * Refuse an empty title.
	 *
	 * @param TestRecord $subject Record.
	 * @param array      $input   Ability input.
	 * @return true|\WP_Error
	 */
	public function validate( $subject, array $input ) {
		return '' === $input['title'] ? new \WP_Error( 'test_empty_title', 'Title is empty.' ) : true;
	}

	/**
	 * Rename in memory.
	 *
	 * @param TestRecord $subject Record.
	 * @param array      $input   Ability input.
	 * @throws \RuntimeException When the title asks for it.
	 */
	public function apply( $subject, array $input ) {
		if ( 'throw' === $input['title'] ) {
			throw new \RuntimeException( 'Apply failed.' );
		}
		$subject->title = $input['title'];
	}

	/**
	 * The renamed record.
	 *
	 * @param TestRecord $subject Saved record.
	 * @return array
	 */
	public function respond( $subject ) {
		return array(
			'record' => array(
				'id'    => $subject->id,
				'title' => $subject->title,
			),
		);
	}
}
