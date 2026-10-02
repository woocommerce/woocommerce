<?php
/**
 * Object change ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * An ability that changes one object, registered with this class or a
 * subclass as its `ability_class`. Execute changes the object in memory, runs
 * the extension fields and the object validators, and saves once. No method
 * saves.
 *
 * Fixed facts about the ability, such as whether it is destructive, belong in
 * `meta.annotations`.
 *
 * @since 11.3.0
 */
abstract class ObjectChangeAbility extends PolyfilledAbility {

	/**
	 * Object type the ability changes. The fields and validators registered for it run on the changed object.
	 */
	abstract public static function object_type(): string;

	/**
	 * Load the object to change. Never saves.
	 *
	 * @param array $input Ability input.
	 * @return object|\WP_Error|null
	 */
	abstract public function load( array $input );

	/**
	 * Change the object in memory. It must not save, send email or make HTTP
	 * requests. Nothing is saved until every step passes.
	 *
	 * @param object $subject Object.
	 * @param array  $input   Ability input.
	 * @return mixed A WP_Error to reject the change. Nothing is saved.
	 */
	abstract public function change( $subject, array $input );

	/**
	 * The output for the saved object, or null for the object's data.
	 *
	 * @param object $subject Saved object.
	 * @return mixed
	 */
	public function prepare_response( $subject ) {
		return null;
	}

	/**
	 * The ability call that undoes this change, read from the object before
	 * change(), or null when it cannot be undone. It is not run.
	 *
	 * @param object $subject Object, before the change.
	 * @param array  $input   Ability input.
	 * @return array{ability: string, input: array}|null
	 */
	public function undo( $subject, array $input ): ?array {
		return null;
	}

	/**
	 * Short sentences that describe the effects of this call, such as "Emails the customer".
	 *
	 * @param object $subject Object, before the change.
	 * @param array  $input   Ability input.
	 * @return string[]
	 */
	public function side_effects( $subject, array $input ): array {
		return array();
	}

	/**
	 * Run the change.
	 *
	 * @param mixed $input Ability input.
	 * @return mixed
	 */
	public function run_change( $input = null ) {
		return InMemoryWriteRunner::run(
			$this->get_name(),
			array(
				'object_type'      => static::object_type(),
				'load'             => array( $this, 'load' ),
				'change'           => array( $this, 'change' ),
				'prepare_response' => array( $this, 'prepare_response' ),
			),
			is_array( $input ) ? $input : array()
		);
	}

	/**
	 * Use the change as the execute callback.
	 *
	 * @param array $args Ability arguments.
	 * @return array
	 */
	protected function prepare_properties( array $args ): array {
		$args['execute_callback'] = array( $this, 'run_change' );
		return parent::prepare_properties( $args );
	}
}
