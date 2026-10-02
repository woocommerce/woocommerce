<?php
/**
 * Test notify ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\DryRunAbility;

/**
 * An ability that emails the merchant, with a hand-written dry run.
 */
class TestNotifyAbility extends DryRunAbility {

	/**
	 * Say what execute would do, without sending anything.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	protected function do_dry_run( array $input ) {
		return array(
			'ability'      => $this->get_name(),
			'object_type'  => null,
			'object_id'    => null,
			'object_label' => null,
			'changes'      => array(),
			'side_effects' => array( 'Emails the merchant: ' . $input['message'] ),
			'undo'         => null,
		);
	}
}
