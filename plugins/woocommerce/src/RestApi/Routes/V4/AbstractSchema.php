<?php
/**
 * Abstract REST Schema.
 *
 * Holds schema for REST API routes.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract REST Schema for WooCommerce REST API V4.
 *
 * Provides common functionality for all V4 schema controllers including
 * property generation, context filtering, and validation.
 *
 * @since 10.2.0
 */
abstract class AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 * @since 10.2.0
	 */
	const IDENTIFIER = '';

	/**
	 * Context for the item schema - view, edit, and embed.
	 *
	 * @var array
	 * @since 10.2.0
	 */
	const VIEW_EDIT_EMBED_CONTEXT = array( 'view', 'edit', 'embed' );

	/**
	 * Context for the item schema - view and edit only.
	 *
	 * @var array
	 * @since 10.2.0
	 */
	const VIEW_EDIT_CONTEXT = array( 'view', 'edit' );

	/**
	 * Get the item schema.
	 *
	 * @return array The item schema.
	 * @since 10.2.0
	 */
	public static function get_item_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => static::IDENTIFIER,
			'type'       => 'object',
			'properties' => static::get_item_schema_properties(),
		);
	}

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array The schema properties.
	 * @since 10.2.0
	 */
	public static function get_item_schema_properties(): array {
		return array();
	}
}
