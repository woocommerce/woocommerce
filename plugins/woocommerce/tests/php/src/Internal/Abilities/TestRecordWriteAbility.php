<?php
/**
 * Test record write ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\ObjectChangeAbility;

/**
 * A plugin's ability that renames a record.
 */
class TestRecordWriteAbility extends ObjectChangeAbility {

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
	public function load( array $input ) {
		return TestRecord::load( $input['id'] );
	}

	/**
	 * Rename in memory, refusing an empty title.
	 *
	 * @param TestRecord $subject Record.
	 * @param array      $input   Ability input.
	 * @return null|\WP_Error
	 * @throws \RuntimeException When the title asks for it.
	 */
	public function change( $subject, array $input ) {
		if ( '' === $input['title'] ) {
			return new \WP_Error( 'test_empty_title', 'Title is empty.' );
		}
		if ( 'throw' === $input['title'] ) {
			throw new \RuntimeException( 'Apply failed.' );
		}
		$subject->title = $input['title'];
		return null;
	}

	/**
	 * The renamed record.
	 *
	 * @param TestRecord $subject Saved record.
	 * @return array
	 */
	public function prepare_response( $subject ) {
		return array(
			'record' => array(
				'id'    => $subject->id,
				'title' => $subject->title,
			),
		);
	}

	/**
	 * Rename back.
	 *
	 * @param TestRecord $subject Record, before the change.
	 * @param array      $input   Ability input.
	 * @return array{ability: string, input: array}
	 */
	public function undo( $subject, array $input ): ?array {
		return array(
			'ability' => $this->get_name(),
			'input'   => array(
				'id'    => $subject->id,
				'title' => $subject->title,
			),
		);
	}

	/**
	 * Effects of the rename.
	 *
	 * @param TestRecord $subject Record, before the change.
	 * @param array      $input   Ability input.
	 * @return string[]
	 */
	public function side_effects( $subject, array $input ): array {
		return array( 'Renames the record.' );
	}
}
