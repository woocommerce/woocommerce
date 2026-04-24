<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * SavedListSchema class.
 *
 * Response shape for a saved list (a named collection of saved items for the current user,
 * e.g. save-for-later).
 */
class SavedListSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'saved-list';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'saved-list';

	/**
	 * Saved list item schema instance.
	 *
	 * @var SavedListItemSchema
	 */
	protected $item_schema;

	/**
	 * Constructor.
	 *
	 * @param \Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema $extend Rest Extending instance.
	 * @param \Automattic\WooCommerce\StoreApi\SchemaController     $controller Schema Controller instance.
	 */
	public function __construct( $extend, $controller ) {
		parent::__construct( $extend, $controller );

		$item_schema = $this->controller->get( SavedListItemSchema::IDENTIFIER );
		if ( $item_schema instanceof SavedListItemSchema ) {
			$this->item_schema = $item_schema;
		}
	}

	/**
	 * Saved list schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array(
			'list_id'     => array(
				'description' => __( 'The saved list identifier.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'items_count' => array(
				'description' => __( 'Number of items in the saved list.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'items'       => array(
				'description' => __( 'Items in the saved list.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->force_schema_readonly( $this->item_schema->get_properties() ),
				),
			),
		);
	}

	/**
	 * Format a saved list for response.
	 *
	 * @param array $saved_list Associative array with keys: list_id (string), items (array of stored item payloads).
	 * @return array
	 */
	public function get_item_response( $saved_list ) {
		$items = array_values( (array) ( $saved_list['items'] ?? array() ) );

		return array(
			'list_id'     => (string) ( $saved_list['list_id'] ?? '' ),
			'items_count' => count( $items ),
			'items'       => array_map(
				function ( $item ) {
					return $this->item_schema->get_item_response( $item );
				},
				$items
			),
		);
	}
}
