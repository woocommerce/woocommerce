<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1\AI;

use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;

/**
 * PatternsSchema class.
 *
 * @internal
 */
class PatternsSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'ai/patterns';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ai/patterns';

	/**
	 * Patterns schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [];
	}

	/**
	 * Get the Patterns response.
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
