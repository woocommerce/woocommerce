<?php
/**
 * Test committing option write definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\CommitsAfterSave;

/**
 * Sets an option, then logs the saved value outside the subject.
 */
class TestCommittingOptionWriteDefinition extends TestOptionWriteDefinition implements CommitsAfterSave {

	public const ABILITY_ID = 'test-extension/set-option-and-log';

	public const LOG_OPTION = 'test_in_memory_option_log';

	/**
	 * Get the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return self::ABILITY_ID;
	}

	/**
	 * Log the saved value, refusing `uncommittable` and throwing on
	 * `commit-data-throws` and `commit-throws`.
	 *
	 * @param TestOptionRecord $subject Saved option.
	 * @param array            $input   Ability input.
	 * @return true|\WP_Error
	 * @throws \WC_Data_Exception When the value is `commit-data-throws`.
	 * @throws \RuntimeException  When the value is `commit-throws`.
	 */
	public static function commit( $subject, array $input ) {
		if ( 'commit-data-throws' === $input['value'] ) {
			throw new \WC_Data_Exception( 'test_invalid', 'Invalid data.' );
		}
		if ( 'commit-throws' === $input['value'] ) {
			throw new \RuntimeException( 'Remote is gone.' );
		}
		if ( 'uncommittable' === $input['value'] ) {
			return new \WP_Error( 'test_commit_failed', 'Commit failed.' );
		}
		update_option( self::LOG_OPTION, get_option( $subject->name ) );
		return true;
	}

	/**
	 * The ability's response, with the log.
	 *
	 * @param TestOptionRecord $subject Saved option.
	 * @return array
	 */
	public static function respond( $subject ): array {
		return array_merge( parent::respond( $subject ), array( 'log' => get_option( self::LOG_OPTION ) ) );
	}
}
