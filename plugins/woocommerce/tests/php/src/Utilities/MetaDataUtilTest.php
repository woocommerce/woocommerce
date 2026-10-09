<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\MetaDataUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the MetaDataUtil class.
 */
class MetaDataUtilTest extends WC_Unit_Test_Case {

	/**
	 * @testdox `normalize` keeps complete entries and applies all fields.
	 */
	public function test_normalize_keeps_complete_entries(): void {
		$result = MetaDataUtil::normalize(
			array(
				array(
					'key'   => 'color',
					'value' => 'red',
					'id'    => 42,
				),
			)
		);

		$this->assertCount( 1, $result );
		$this->assertSame( 'color', $result[0]['key'] );
		$this->assertSame( 'red', $result[0]['value'] );
		$this->assertSame( 42, $result[0]['id'] );
	}

	/**
	 * @testdox `normalize` filters out entries without a key.
	 */
	public function test_normalize_filters_entries_without_key(): void {
		$result = MetaDataUtil::normalize(
			array(
				array( 'value' => 'orphan' ),
				array(),
				array(
					'key'   => 'valid',
					'value' => 'val',
				),
			)
		);

		$this->assertCount( 1, $result );
		$this->assertSame( 'valid', $result[0]['key'] );
	}

	/**
	 * @testdox `normalize` filters out entries where key is explicitly null.
	 */
	public function test_normalize_filters_entries_with_null_key(): void {
		$result = MetaDataUtil::normalize(
			array(
				array(
					'key'   => null,
					'value' => 'val',
				),
			)
		);

		$this->assertCount( 0, $result );
	}

	/**
	 * @testdox `normalize` defaults missing value to null.
	 */
	public function test_normalize_defaults_missing_value_to_null(): void {
		$result = MetaDataUtil::normalize(
			array(
				array( 'key' => 'flag' ),
			)
		);

		$this->assertCount( 1, $result );
		$this->assertNull( $result[0]['value'] );
	}

	/**
	 * @testdox `normalize` defaults missing id to empty string.
	 */
	public function test_normalize_defaults_missing_id_to_empty_string(): void {
		$result = MetaDataUtil::normalize(
			array(
				array(
					'key'   => 'k',
					'value' => 'v',
				),
			)
		);

		$this->assertSame( '', $result[0]['id'] );
	}

	/**
	 * @testdox `normalize` uses the provided default_id.
	 */
	public function test_normalize_uses_custom_default_id(): void {
		$result = MetaDataUtil::normalize(
			array(
				array(
					'key'   => 'k',
					'value' => 'v',
				),
			),
			0
		);

		$this->assertSame( 0, $result[0]['id'] );
	}

	/**
	 * @testdox `update` calls update_meta_data on a WC_Data object for each valid entry.
	 */
	public function test_update_with_wc_data_object(): void {
		$order = wc_create_order();

		MetaDataUtil::update(
			array(
				array( 'value' => 'orphan' ),
				array(
					'key'   => 'color',
					'value' => 'blue',
				),
			),
			$order
		);

		$meta_by_key = array();
		foreach ( $order->get_meta_data() as $meta ) {
			$meta_by_key[ $meta->key ] = $meta->value;
		}

		$this->assertArrayHasKey( 'color', $meta_by_key );
		$this->assertSame( 'blue', $meta_by_key['color'] );
		$this->assertArrayNotHasKey( '', $meta_by_key, 'Keyless entry should not be processed' );
	}

	/**
	 * @testdox `update` does nothing when meta_data is not an array.
	 */
	public function test_update_ignores_non_array_meta_data(): void {
		$order = wc_create_order();
		$order->update_meta_data( 'color', 'blue' );

		// get_meta_data() hands back the order's own WC_Meta_Data objects, so an in-place change
		// would compare equal to itself. get_data() does not help either: it returns the last
		// applied values, not pending ones. Copy each entry's current id, key and value instead.
		$meta_snapshot      = static function ( \WC_Order $order ): array {
			return array_map(
				static function ( $meta ) {
					return array(
						'id'    => $meta->id,
						'key'   => $meta->key,
						'value' => $meta->value,
					);
				},
				$order->get_meta_data()
			);
		};
		$original_meta_data = $meta_snapshot( $order );

		MetaDataUtil::update( null, $order );
		MetaDataUtil::update( 'string', $order );

		$this->assertSame( $original_meta_data, $meta_snapshot( $order ), 'Non-array meta_data should leave existing order metadata unchanged.' );
	}

	/**
	 * @testdox `update` throws TypeError when target is not a WC_Data instance.
	 */
	public function test_update_throws_for_invalid_target(): void {
		$this->expectException( \TypeError::class );

		MetaDataUtil::update( array(), 'not_a_wc_data_object' );
	}

	/**
	 * @testdox `update` uses default_id for entries without an id, updating that metadata row in place.
	 */
	public function test_update_uses_default_id_to_rewrite_the_row_in_place(): void {
		$order = wc_create_order();
		$order->add_meta_data( 'original_key', 'original_value' );
		$order->save();

		$rows_with_key = static function ( \WC_Order $order, string $key ): array {
			return array_values(
				array_filter(
					$order->get_meta_data(),
					static function ( $meta ) use ( $key ): bool {
						return $key === $meta->key;
					}
				)
			);
		};
		$existing_rows = $rows_with_key( $order, 'original_key' );
		$this->assertCount( 1, $existing_rows, 'The seeded metadata row should exist before the update.' );
		$existing_id = $existing_rows[0]->id;

		// The id has to name a real row. An id that matches nothing makes update_meta_data() add
		// a new row, which is also what it does when no id arrives at all.
		MetaDataUtil::update(
			array(
				array(
					'key'   => 'k',
					'value' => 'v',
				),
			),
			$order,
			$existing_id
		);

		$updated_rows = $rows_with_key( $order, 'k' );
		$this->assertCount( 1, $updated_rows );
		$this->assertSame( $existing_id, $updated_rows[0]->id, 'The entry should update the row named by default_id rather than add a new one.' );
		$this->assertSame( 'v', $updated_rows[0]->value );
		$this->assertCount( 0, $rows_with_key( $order, 'original_key' ), 'The row named by default_id should have been rewritten in place.' );
	}
}
