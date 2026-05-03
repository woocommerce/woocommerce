<?php
/**
 * OrderNoteSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\OrderNotes\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use WP_REST_Request;

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
	public function get_item_schema_properties(): array {
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
			'title'            => array(
				'description' => __( 'The title of the order note group.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
			),
			'group'            => array(
				'description' => __( 'The group of order note.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
				'readonly'    => true,
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

	/**
	 * Get the item response.
	 *
	 * @param WP_Comment      $note Order note object.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $note, WP_REST_Request $request, array $include_fields = array() ): array {
		$group            = get_comment_meta( $note->comment_ID, 'note_group', true );
		$title            = get_comment_meta( $note->comment_ID, 'note_title', true );
		$is_customer_note = wc_string_to_bool( get_comment_meta( $note->comment_ID, 'is_customer_note', true ) );

		if ( $group && ! $title ) {
			$title = OrderNoteGroup::get_default_group_title( $group );
		}

		return array(
			'id'               => (int) $note->comment_ID,
			'order_id'         => (int) $note->comment_post_ID,
			'author'           => $note->comment_author,
			'date_created'     => wc_rest_prepare_date_response( $note->comment_date ),
			'date_created_gmt' => wc_rest_prepare_date_response( $note->comment_date_gmt ),
			'note'             => $note->comment_content,
			'title'            => $title,
			'group'            => $group,
			'is_customer_note' => $is_customer_note,
		);
	}
}
