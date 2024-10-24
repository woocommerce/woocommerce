<?php

namespace Automattic\WooCommerce\Snapshots;

/**
 * Coupons snapshot controller class.
 *
 * Assumes the following database table exists:

create table wp_wc_shop_coupon_snapshots(
entity_id bigint(20) unsigned NOT NULL,
date_created_gmt datetime NOT NULL,
data_checksum char(32) NOT NULL,
code varchar(200) NOT NULL,
amount decimal(26,8) NOT NULL,
key (entity_id))
 */
class CouponSnapshotsController extends SnapshotsControllerBase {

	public function get_entity_type(): string {
		return 'shop_coupon';
	}

	protected function get_snapshot_table_formats(): ?array {
		return array( '%s', '%f' );
	}

	protected function get_snapshot_data_from_entity( $entity ): array {
		return array(
			'code'   => $entity->get_code(),
			'amount' => $entity->get_amount(),
		);
	}

	protected function get_object_from_snapshot_data( int $entity_id, array $snapshot_data ): object {
		// Pass the id to the constructor to try to read the object from the database,
		// but if the object doesn't exist anymore it's fine, we'll fill in
		// the appropriate fields anyway.
		$coupon = new \WC_Coupon( $entity_id );

		$coupon->set_id( $entity_id );
		$coupon->set_code( $snapshot_data['code'] );
		$coupon->set_amount( $snapshot_data['amount'] );

		return $coupon;
	}
}
