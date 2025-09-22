<?php
/**
 * OrderNoteSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\OrderNotes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;

/**
 * OrderNoteSchema class.
 */
class OrderNoteSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order_note';

	/**
	 * Return all properties for the item schema.
	 *
	 * Note that context determines under which context data should be visible. For example, edit would be the context
	 * used when getting records with the intent of editing them. embed context allows the data to be visible when the
	 * item is being embedded in another response.
	 *
	 * @return array
	 */
	public static function get_item_schema_properties(): array {
		$schema = array(
			'id'               => array(
				'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'order_id'         => array(
				'description' => __( 'Order ID the note belongs to.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'author'           => array(
				'description' => __( 'Order note author.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'date_created'     => array(
				'description' => __( "The date the order note was created, in the site's timezone.", 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'date_created_gmt' => array(
				'description' => __( 'The date the order note was created, as GMT.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'note'             => array(
				'description' => __( 'Order note content.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'required'    => true,
			),
			'is_customer_note' => array(
				'description' => __( 'If true, the note will be shown to customers. If false, the note will be for admin reference only.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
			),
		);

		return $schema;
	}
}
