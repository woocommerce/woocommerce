<?php

declare( strict_types = 1 );


namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Rehomed_Slugs;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Rehomed_Slugs
 */
class Default_Tree_Test extends \WC_Unit_Test_Case {

	/**
	 * The default tree must be well-formed: every non-null parent must reference
	 * an existing slug in the tree.
	 */
	public function test_default_tree_is_well_formed() {
		$tree = require dirname( WC_PLUGIN_FILE ) . '/src/Internal/Admin/Navigation/default-tree.php';

		$this->assertIsArray( $tree );
		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertNull( $tree['woocommerce']['parent'], 'WooCommerce root must have null parent' );

		foreach ( $tree as $slug => $node ) {
			$this->assertArrayHasKey( 'parent', $node, "Node '$slug' missing parent key" );
			$this->assertArrayHasKey( 'title', $node, "Node '$slug' missing title key" );
			$this->assertArrayHasKey( 'position', $node, "Node '$slug' missing position key" );

			if ( null !== $node['parent'] ) {
				$this->assertArrayHasKey(
					$node['parent'],
					$tree,
					"Node '$slug' references unknown parent '{$node['parent']}'"
				);
			}
		}
	}

	/**
	 * The rehomed-slugs list must match the spec.
	 */
	public function test_rehomed_slugs_constant() {
		// `woocommerce` itself is intentionally absent — the feature keeps
		// Woo's own top-level registration as the single consolidated rail
		// item, and only rehomes the sibling Woo-related top-levels.
		$expected = array(
			'edit.php?post_type=product',
			'wc-admin&path=/analytics/overview',
			'woocommerce-marketing',
			'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM',
			'wc-admin&path=/payments/connect',
			'wc-admin&path=/payments/overview',
			'woocommerce-payments',
			'klaviyo_settings',
		);

		$this->assertSame( $expected, Rehomed_Slugs::ALL );
	}
}
