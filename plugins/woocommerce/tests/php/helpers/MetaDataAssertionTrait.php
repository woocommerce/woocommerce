<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Helpers;

use WC_Data;

/**
 * Trait MetaDataAssertionTrait.
 *
 * Provides shared test input and assertions for verifying that REST API
 * controllers handle incomplete meta_data entries correctly.
 */
trait MetaDataAssertionTrait {

	/**
	 * Returns meta_data input covering all incomplete-entry cases.
	 *
	 * @return array[]
	 */
	private function get_incomplete_meta_data_input(): array {
		return array(
			array( 'value' => 'orphan_value' ),
			array( 'key' => 'key_missing_value' ),
			array(
				'key'   => 'key_explicit_null',
				'value' => null,
			),
			array(),
			array(
				'key'   => 'complete_key',
				'value' => 'complete_value',
			),
		);
	}

	/**
	 * Asserts that a WC_Data object processed incomplete meta_data entries correctly:
	 * - Complete entries are saved.
	 * - Entries without a key are not processed.
	 * - Entries with a missing value behave the same as passing null explicitly.
	 *
	 * @param WC_Data $wc_data The object whose meta data to check.
	 */
	private function assert_incomplete_meta_data_handled_correctly( WC_Data $wc_data ): void {
		$meta_by_key = array();
		foreach ( $wc_data->get_meta_data() as $meta ) {
			$meta_by_key[ $meta->key ] = $meta->value;
		}

		$this->assertEquals( 'complete_value', $meta_by_key['complete_key'] ?? null, 'Complete entry should be saved' );
		$this->assertArrayNotHasKey( '', $meta_by_key, 'Entry without key should not create a meta data row' );
		$this->assertSame(
			$meta_by_key['key_missing_value'] ?? 'NOT_FOUND',
			$meta_by_key['key_explicit_null'] ?? 'NOT_FOUND',
			'Missing value should be equivalent to explicit null'
		);
	}
}
