<?php
/**
 * Subject snapshot class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use WC_Data;

defined( 'ABSPATH' ) || exit;

/**
 * The state of an in-memory write's subject, used to compare it before and
 * after a change.
 *
 * @since 11.3.0
 */
final class SubjectSnapshot {

	/**
	 * Snapshot a subject. A WC_Data object gives get_data() with each meta entry
	 * as `id`, `key` and `value`; an InMemorySubject gives snapshot().
	 *
	 * @param WC_Data|InMemorySubject $subject Subject.
	 * @return array
	 *
	 * @since 11.3.0
	 */
	public static function of( $subject ): array {
		if ( $subject instanceof InMemorySubject ) {
			return $subject->snapshot();
		}

		$data              = $subject->get_data();
		$data['meta_data'] = array_map(
			static function ( $meta ) {
				return $meta->get_data();
			},
			$subject->get_meta_data()
		);
		return $data;
	}
}
