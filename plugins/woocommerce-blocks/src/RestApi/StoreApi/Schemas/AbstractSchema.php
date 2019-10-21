<?php
/**
 * Abstract Schema.
 *
 * Rest API schema class.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractBlock class.
 */
abstract class AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'Schema';

	/**
	 * Returns the full item schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->title,
			'type'       => 'object',
			'properties' => $this->get_properties(),
		);
	}

	/**
	 * Returns schema properties.
	 *
	 * @return array
	 */
	abstract protected function get_properties();
}
