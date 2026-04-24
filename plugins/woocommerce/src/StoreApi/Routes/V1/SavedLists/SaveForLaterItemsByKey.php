<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\SavedLists;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;
use Automattic\WooCommerce\StoreApi\Utilities\SavedListsStore;

/**
 * SaveForLaterItemsByKey route.
 *
 * Removes a single item from the current user's save-for-later list without restoring it to the cart.
 */
class SaveForLaterItemsByKey extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'saved-lists-save-for-later-items-by-key';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'saved-list-item';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/saved-lists/save-for-later/items/(?P<key>[a-f0-9]{32})';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			'args'   => array(
				'key' => array(
					'description' => __( 'Unique identifier for the item within the saved list.', 'woocommerce' ),
					'type'        => 'string',
				),
			),
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_user_logged_in' ),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Gate access to logged-in users only.
	 *
	 * @return bool
	 */
	public function check_user_logged_in() {
		return is_user_logged_in();
	}

	/**
	 * Remove an item from the save-for-later list.
	 *
	 * @throws RouteException If the item does not exist.
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	protected function get_route_delete_response( \WP_REST_Request $request ) {
		$store = new SavedListsStore();
		$store->remove_item( SavedListsStore::SAVE_FOR_LATER, (string) $request['key'] );

		return new \WP_REST_Response( null, 204 );
	}
}
