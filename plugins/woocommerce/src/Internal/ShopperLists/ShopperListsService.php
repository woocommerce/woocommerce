<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

defined( 'ABSPATH' ) || exit;

/**
 * Cross-cutting hooks for shopper lists (e.g. cleanup on user deletion).
 */
class ShopperListsService {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'deleted_user', array( $this, 'on_user_deleted' ), 10, 1 );
	}

	/**
	 * Delete every list belonging to a removed user.
	 *
	 * @param int $old_user_id ID of the deleted user.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function on_user_deleted( int $old_user_id ): void {
		ShopperList::delete_all_for_user( $old_user_id );
	}
}
