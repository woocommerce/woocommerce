<?php
/**
 * WebhookUtil class file.
 */

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * Class with utility methods for dealing with webhooks.
 */
class WebhookUtil {

	use AccessiblePrivateMethods;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		self::add_action( 'deleted_user', array( $this, 'reassign_webhooks_to_new_user_id' ), 10, 2 );
	}

	/**
	 * Whenever a user is deleted, re-assign their webhooks to the new user.
	 *
	 * If re-assignment isn't selected during deletion, assign the webhooks to user_id 0,
	 * so that an admin can edit and re-save them in order to get them to be assigned to a valid user.
	 *
	 * @param int      $old_user_id ID of the deleted user.
	 * @param int|null $new_user_id ID of the user to reassign existing data to, or null if no re-assignment is requested.
	 *
	 * @return void
	 * @since 7.8.0
	 */
	private function reassign_webhooks_to_new_user_id( int $old_user_id, ?int $new_user_id ): void {
		$data_store  = \WC_Data_Store::load( 'webhook' );
		$webhook_ids = $data_store->search_webhooks(
			array(
				'user_id' => $old_user_id ?? 0,
			)
		);

		foreach ( $webhook_ids as $webhook_id ) {
			$webhook = new \WC_Webhook( $webhook_id );
			$webhook->set_user_id( $new_user_id );
			$webhook->save();
		}
	}
}
