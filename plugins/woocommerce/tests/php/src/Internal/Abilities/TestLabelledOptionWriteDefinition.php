<?php
/**
 * Test labelled option write definition class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\LabelsChanges;

/**
 * Sets an option and labels the change for merchants.
 */
class TestLabelledOptionWriteDefinition extends TestOptionWriteDefinition implements LabelsChanges {

	public const ABILITY_ID = 'test-extension/set-labelled-option';

	/**
	 * Get the ability name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return self::ABILITY_ID;
	}

	/**
	 * Labels for what the write changes.
	 *
	 * @return array<string, string>
	 */
	public static function change_labels(): array {
		return array( 'value' => 'Value' );
	}

	/**
	 * Label for the option.
	 *
	 * @param TestOptionRecord $subject Option.
	 */
	public static function subject_label( $subject ): string {
		return "Option {$subject->name}";
	}
}
