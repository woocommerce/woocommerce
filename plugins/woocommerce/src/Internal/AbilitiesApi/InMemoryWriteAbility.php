<?php
/**
 * In-memory write ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * A write ability, registered with this class or a subclass as its
 * `ability_class`. Execute applies the change in memory, runs the extension
 * fields and the object validators, and saves once. No step saves.
 *
 * @since 11.3.0
 */
abstract class InMemoryWriteAbility extends PolyfilledAbility {

	/**
	 * Object type the write changes. The fields and validators registered for it run on the applied object.
	 */
	abstract public static function object_type(): string;

	/**
	 * Load the object to change. Never saves.
	 *
	 * @param array $input Ability input.
	 * @return object|\WP_Error|null
	 */
	abstract public function subject( array $input );

	/**
	 * Change the object in memory. Never saves.
	 *
	 * @param object $subject Object.
	 * @param array  $input   Ability input.
	 * @return mixed A WP_Error to refuse the change.
	 */
	abstract public function apply( $subject, array $input );

	/**
	 * Check the input against the object.
	 *
	 * @param object $subject Object.
	 * @param array  $input   Ability input.
	 * @return true|\WP_Error
	 */
	public function validate( $subject, array $input ) {
		return true;
	}

	/**
	 * The output for the saved object, or null for the object's snapshot.
	 *
	 * @param object $subject Saved object.
	 * @return mixed
	 */
	public function respond( $subject ) {
		return null;
	}

	/**
	 * Declared effects and whether the change can be undone.
	 *
	 * @return array{effects: string[], undoable: bool}
	 */
	public function hints(): array {
		return array(
			'effects'  => array(),
			'undoable' => false,
		);
	}

	/**
	 * The ability call that undoes this write, read from the object before
	 * apply(), or null when it cannot be undone.
	 *
	 * @param object $subject Object, before the change.
	 * @param array  $input   Ability input.
	 * @return array{ability: string, input: array}|null
	 */
	public function inverse( $subject, array $input ): ?array {
		return null;
	}

	/**
	 * Run the write.
	 *
	 * @param mixed $input Ability input.
	 * @return mixed
	 */
	public function run_write( $input = null ) {
		return InMemoryWriteRunner::run(
			$this->get_name(),
			array(
				'object_type' => static::object_type(),
				'subject'     => array( $this, 'subject' ),
				'validate'    => array( $this, 'validate' ),
				'apply'       => array( $this, 'apply' ),
				'respond'     => array( $this, 'respond' ),
			),
			is_array( $input ) ? $input : array()
		);
	}

	/**
	 * Use the write as the execute callback.
	 *
	 * @param array $args Ability arguments.
	 * @return array
	 */
	protected function prepare_properties( array $args ): array {
		$args['execute_callback'] = array( $this, 'run_write' );
		return parent::prepare_properties( $args );
	}
}
