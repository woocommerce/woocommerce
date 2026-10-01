<?php
/**
 * WCDataSubjectTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\WCDataSubject;

/**
 * The snapshot of a WC_Data object an in-memory write changes.
 */
class WCDataSubjectTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox Should show in-memory changes to existing and new meta in the snapshot.
	 */
	public function test_snapshot_shows_unsaved_meta_changes(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_test_code', 'saved' );
		$product->save();
		$product = wc_get_product( $product->get_id() );

		$product->update_meta_data( '_test_code', 'changed' );
		$product->update_meta_data( '_test_new', 'added' );
		$meta = array_column( ( new WCDataSubject( $product ) )->snapshot()['meta_data'], 'value', 'key' );

		$this->assertSame( 'changed', $meta['_test_code'] );
		$this->assertSame( 'added', $meta['_test_new'] );
	}
}
