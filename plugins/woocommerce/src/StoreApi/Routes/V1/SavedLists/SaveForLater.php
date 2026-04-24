<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\SavedLists;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;
use Automattic\WooCommerce\StoreApi\Utilities\SavedListsStore;

/**
 * SaveForLater route.
 *
 * Returns the current user's save-for-later list.
 */
class SaveForLater extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'saved-lists-save-for-later';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'saved-list';

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
		return '/saved-lists/save-for-later';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_user_logged_in' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
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
	 * Return the current user's save-for-later list.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$store = new SavedListsStore();
		$items = $store->get_items( SavedListsStore::SAVE_FOR_LATER );

		return new \WP_REST_Response(
			$this->schema->get_item_response(
				array(
					'list_id' => SavedListsStore::SAVE_FOR_LATER,
					'items'   => $items,
				)
			)
		);
	}
}
