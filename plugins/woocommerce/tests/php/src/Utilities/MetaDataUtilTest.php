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

		MetaDataUtil::update( null, $order );
		MetaDataUtil::update( 'string', $order );

		$this->assertEmpty( $order->get_meta_data(), 'No meta should be added for non-array meta_data' );
	}

	/**
	 * @testdox `update` throws TypeError when target is not a WC_Data instance.
	 */
	public function test_update_throws_for_invalid_target(): void {
		$this->expectException( \TypeError::class );

		MetaDataUtil::update( array(), 'not_a_wc_data_object' );
	}

	/**
	 * @testdox `update` passes custom default_id through to normalize.
	 */
	public function test_update_passes_default_id(): void {
		$order = wc_create_order();

		MetaDataUtil::update(
			array(
				array(
					'key'   => 'k',
					'value' => 'v',
				),
			),
			$order,
			99
		);

		$meta_data = $order->get_meta_data();
		$this->assertCount( 1, $meta_data );
		$this->assertSame( 'k', $meta_data[0]->key );
		$this->assertSame( 'v', $meta_data[0]->value );
	}
}
