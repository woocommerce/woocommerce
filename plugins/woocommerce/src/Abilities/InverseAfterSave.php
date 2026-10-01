<?php
/**
 * Inverse-after-save interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * An in-memory write whose undo is only known after the save, for example a
 * write that creates an object and gets its ID from the save. Implement it
 * alongside InMemoryWrite. WooCommerce never runs undo: a caller that runs the
 * write's steps itself takes SubjectSnapshot::of( $subject ) before apply(),
 * saves, then asks this method for the undo call.
 *
 * @since 11.3.0
 */
interface InverseAfterSave {

	/**
	 * The ability call that undoes this write, or null when it cannot be undone.
	 * It may name a different ability.
	 *
	 * @param array                    $before        SubjectSnapshot::of() the subject, taken before apply().
	 * @param \WC_Data|InMemorySubject $saved_subject The subject, after the save.
	 * @param array                    $input         Ability input.
	 * @return array{ability: string, input: array}|null
	 *
	 * @since 11.3.0
	 */
	public static function inverse_after_save( array $before, $saved_subject, array $input ): ?array;
}
