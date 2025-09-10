<?php
/**
 * Abstract REST Schema.
 *
 * Holds schema for REST API routes.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Schemas\V4;

defined( 'ABSPATH' ) || exit;

/**
 * Orders Controller.
 */
abstract class AbstractSchema {

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = '';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_properties() {
		return array();
	}
}
