<?php
/**
 * Commits-after-save interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * An in-memory write with a step outside its subject, such as a record in
 * another table or a remote call. Implement it alongside InMemoryWrite. The
 * executor calls commit() after the subject's save and before respond(). A
 * preview never calls it.
 *
 * @since 11.3.0
 */
interface CommitsAfterSave {

	/**
	 * Run the step that follows the save. The subject is already saved when
	 * this returns an error.
	 *
	 * @param \WC_Data|InMemorySubject $subject Saved subject.
	 * @param array                    $input   Ability input.
	 * @return true|\WP_Error
	 *
	 * @since 11.3.0
	 */
	public static function commit( $subject, array $input );
}
