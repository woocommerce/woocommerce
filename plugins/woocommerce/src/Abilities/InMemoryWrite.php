<?php
/**
 * In-memory write interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * A write ability split into steps any caller can run without saving.
 *
 * @since 11.3.0
 */
interface InMemoryWrite extends AbilityDefinition {

	/**
	 * Kind of object the write changes, such as order, subscription, product or a
	 * kind an extension defines. The validators registered for it run on the
	 * applied subject.
	 *
	 * @since 11.3.0
	 */
	public static function subject_type(): string;

	/**
	 * Load the object to change. Never saves.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Data|null
	 *
	 * @since 11.3.0
	 */
	public static function subject( array $input );

	/**
	 * Check the input against the object.
	 *
	 * @param \WC_Data $subject Object.
	 * @param array    $input   Ability input.
	 * @return true|\WP_Error
	 *
	 * @since 11.3.0
	 */
	public static function validate( $subject, array $input );

	/**
	 * Change the object in memory. Never saves.
	 *
	 * @param \WC_Data $subject Object.
	 * @param array    $input   Ability input.
	 *
	 * @since 11.3.0
	 */
	public static function apply( $subject, array $input ): void;

	/**
	 * Declared effects and whether the change can be undone.
	 *
	 * @return array{effects: string[], undoable: bool}
	 *
	 * @since 11.3.0
	 */
	public static function hints(): array;

	/**
	 * The ability call that undoes this write, read from the object before
	 * apply(). It may name a different ability. Null when the write cannot be
	 * undone.
	 *
	 * @param \WC_Data $subject Object, before the change.
	 * @param array    $input   Ability input.
	 * @return array{ability: string, input: array}|null
	 *
	 * @since 11.3.0
	 */
	public static function inverse( $subject, array $input ): ?array;

	/**
	 * The ability's response for the saved object, in the ability's own output shape.
	 *
	 * @param \WC_Data $subject Object, after the save.
	 * @return array
	 *
	 * @since 11.3.0
	 */
	public static function respond( $subject ): array;
}
