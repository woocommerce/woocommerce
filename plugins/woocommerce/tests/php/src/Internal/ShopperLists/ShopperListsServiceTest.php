<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ShopperLists;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListsService;
use WC_Unit_Test_Case;

/**
 * Tests for ShopperListsService.
 */
class ShopperListsServiceTest extends WC_Unit_Test_Case {
	private const SAVED_FOR_LATER_SLUG = 'saved-for-later';

	/**
	 * @testdox deleting a user removes their stored shopper-list option.
	 */
	public function test_deleted_user_hook_wipes_list_options(): void {
		$sut         = new ShopperListsService();
		$user_id     = $this->factory->user->create( array( 'role' => 'customer' ) );
		$option_name = ShopperList::OPTION_PREFIX . $user_id . '_' . self::SAVED_FOR_LATER_SLUG;
		update_option(
			$option_name,
			array(
				'slug'  => self::SAVED_FOR_LATER_SLUG,
				'items' => array(),
			),
			false
		);

		try {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );

			$this->assertFalse( get_option( $option_name, false ), 'wp_delete_user should trigger cleanup of the list option.' );
		} finally {
			remove_action( 'deleted_user', array( $sut, 'on_user_deleted' ) );
		}
	}
}
