<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1\AI;

use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;

/**
 * StoreTitleSchema class.
 *
 * @internal
 */
class StoreTitleSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'ai/store-title';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ai/store-title';

	/**
	 * Business Description schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [];
	}

	/**
	 * Get the Business Description response.
	 *
	 * @param array $item Item to get response for.
	 *
	 * @return array
	 */
	public function get_item_response( $item ) {
		return [
			'ai_content_generated' => true,
		];
	}
}
