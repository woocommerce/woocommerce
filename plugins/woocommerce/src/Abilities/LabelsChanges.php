<?php
/**
 * Labels-changes interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * An in-memory write that labels what it changes, for merchant-facing
 * previews. Implement it alongside InMemoryWrite. WooCommerce never calls it;
 * it is for callers that show a change before or after it runs.
 *
 * @since 11.3.0
 */
interface LabelsChanges {

	/**
	 * Translatable labels for the subject's prop or meta keys the write changes,
	 * keyed by prop or meta key, e.g. `status`, `next_payment` or
	 * `_subscription_trial_length`.
	 *
	 * @return array<string, string>
	 *
	 * @since 11.3.0
	 */
	public static function change_labels(): array;

	/**
	 * A short, translatable noun phrase for the subject, e.g. "Subscription #221".
	 *
	 * @param \WC_Data|InMemorySubject $subject Subject.
	 * @return string
	 *
	 * @since 11.3.0
	 */
	public static function subject_label( $subject ): string;
}
