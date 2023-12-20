<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1\AI;

use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;

/**
 * StoreInfoSchema class.
 *
 * @internal
 */
class StoreInfoSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'ai/store-info';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ai/store-info';

	/**
	 * Store Info schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [];
	}
}
