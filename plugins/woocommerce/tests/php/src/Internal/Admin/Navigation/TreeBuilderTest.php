<?php

declare( strict_types = 1 );


namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Tree_Builder;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Tree_Builder
 */
class TreeBuilderTest extends \WC_Unit_Test_Case {

	/**
	 * Given the default tree and no extra $menu/$submenu entries, the builder
	 * returns the default tree unchanged (minus any slugs whose underlying
	 * registration is absent — none here, so all retained).
	 */
	public function test_default_tree_passes_through_unchanged() {
		$default = array(
			'woocommerce' => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'wc-admin'    => array(
				'parent'   => 'woocommerce',
				'title'    => 'Home',
				'position' => 10,
			),
		);

		// Simulate WP having registered both pages.
		$raw_menu    = array(
			array( 'WooCommerce', 'read', 'woocommerce', '', '' ),
		);
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertArrayHasKey( 'wc-admin', $tree );
		$this->assertSame( 'woocommerce', $tree['wc-admin']['parent'] );
	}

	/**
	 * Slugs declared in the default tree but not registered by any plugin are
	 * silently skipped (not errors).
	 */
	public function test_unregistered_slugs_are_skipped() {
		$default = array(
			'woocommerce'          => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'woocommerce-payments' => array(
				'parent'   => 'woocommerce',
				'title'    => 'WooPayments',
				'position' => 20,
			),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array();

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertArrayNotHasKey( 'woocommerce-payments', $tree );
	}

	/**
	 * Submenu items registered under 'woocommerce' that aren't in the default tree
	 * auto-attach to the woocommerce root with source = 'auto', preserving registration order.
	 */
	public function test_auto_attach_woocommerce_submenu_items() {
		$default = array(
			'woocommerce' => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Third-party Tool', 'manage_woocommerce', 'my-plugin-page' ),
				array( 'Another Tool', 'manage_woocommerce', 'my-plugin-other' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'my-plugin-page', $tree );
		$this->assertSame( 'woocommerce', $tree['my-plugin-page']['parent'] );
		$this->assertSame( 'auto', $tree['my-plugin-page']['source'] );
		$this->assertSame( 'Third-party Tool', $tree['my-plugin-page']['title'] );

		$this->assertArrayHasKey( 'my-plugin-other', $tree );
		$this->assertSame( 'auto', $tree['my-plugin-other']['source'] );

		// Registration order is preserved via position values.
		$this->assertLessThan(
			$tree['my-plugin-other']['position'],
			$tree['my-plugin-page']['position'],
			'First-registered auto item should have a lower position than the second'
		);
	}

	/**
	 * When the Extensions node is present in the tree, third-party submenu
	 * items registered under `woocommerce` nest under Extensions instead of
	 * the Woo root — keeps the top level of the cascade curated.
	 */
	public function test_auto_attach_nests_under_extensions_when_present() {
		$default = array(
			'woocommerce'               => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'wc-admin&path=/extensions' => array(
				'parent'   => 'woocommerce',
				'title'    => 'Extensions',
				'position' => 95,
			),
		);

		$raw_menu    = array(
			array( 'WooCommerce', 'read', 'woocommerce', '', '' ),
			array( 'Extensions', 'read', 'wc-admin&path=/extensions', '', '' ),
		);
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Third-party Tool', 'manage_woocommerce', 'my-plugin-page' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'my-plugin-page', $tree );
		$this->assertSame( 'wc-admin&path=/extensions', $tree['my-plugin-page']['parent'] );
		$this->assertSame( 'auto', $tree['my-plugin-page']['source'] );
	}

	/**
	 * If the Extensions node isn't in the tree (e.g. a filter removed it),
	 * auto-attached items fall back to the Woo root rather than getting
	 * dropped by the unknown-parent pass.
	 */
	public function test_auto_attach_falls_back_to_root_when_extensions_absent() {
		$default = array(
			'woocommerce' => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Third-party Tool', 'manage_woocommerce', 'my-plugin-page' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'my-plugin-page', $tree );
		$this->assertSame( 'woocommerce', $tree['my-plugin-page']['parent'] );
	}

	/**
	 * A parent-chain cycle (A -> B -> A) is broken by demoting the lowest-position
	 * node to the Woo root. Deterministic — same input produces same output.
	 */
	public function test_cycle_detection_breaks_lowest_position_to_root() {
		// Simulated cycle introduced via the filter.
		$default = array(
			'woocommerce' => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'node-a'      => array(
				'parent'   => 'node-b',
				'title'    => 'A',
				'position' => 30,
			),
			'node-b'      => array(
				'parent'   => 'node-a',
				'title'    => 'B',
				'position' => 40,
			),
		);

		// Register both so they aren't dropped for being unregistered.
		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'A', 'read', 'node-a' ),
				array( 'B', 'read', 'node-b' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		// node-a has position 30 (lowest), so it gets demoted to the Woo root.
		$this->assertSame( 'woocommerce', $tree['node-a']['parent'] );
		// node-b's chain is now valid: node-b -> node-a -> woocommerce.
		$this->assertSame( 'node-a', $tree['node-b']['parent'] );
	}

	/**
	 * The capability from the original registration is preserved on auto-attached items.
	 */
	public function test_auto_attached_items_preserve_capability() {
		$default = array(
			'woocommerce' => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Secret Tool', 'manage_options', 'secret-page' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertSame( 'manage_options', $tree['secret-page']['capability'] );
	}

	/**
	 * A node whose parent is unknown (not in the tree) is dropped from the result
	 * and, when WP_DEBUG is on, a debug message is logged.
	 */
	public function test_unknown_parent_drops_node() {
		$default = array(
			'woocommerce' => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'orphan'      => array(
				'parent'   => 'does-not-exist-yet',
				'title'    => 'Orphan',
				'position' => 30,
			),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Orphan', 'read', 'orphan' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayNotHasKey( 'orphan', $tree );
	}

	/**
	 * A node the user lacks capability for is marked `hidden = true` unless it
	 * has a visible descendant, in which case it's marked `breadcrumb = true`
	 * (rendered as non-clickable label).
	 */
	public function test_capability_filtering_with_breadcrumb_passthrough() {
		$default = array(
			'woocommerce' => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'parent-cap'  => array(
				'parent'     => 'woocommerce',
				'title'      => 'Parent',
				'position'   => 30,
				'capability' => 'manage_options',
			),
			'child-cap'   => array(
				'parent'     => 'parent-cap',
				'title'      => 'Child',
				'position'   => 10,
				'capability' => 'read',
			),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Parent', 'manage_options', 'parent-cap' ),
				array( 'Child', 'read', 'child-cap' ),
			),
		);

		// Simulate a user with 'read' but not 'manage_options'.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$tree = $builder->apply_capability_filter( $tree );

		// Parent survives as a breadcrumb because child is visible.
		$this->assertArrayHasKey( 'parent-cap', $tree );
		$this->assertTrue( $tree['parent-cap']['breadcrumb'] ?? false );
		// Child is fully visible.
		$this->assertArrayHasKey( 'child-cap', $tree );
		$this->assertFalse( $tree['child-cap']['hidden'] ?? false );
	}

	/**
	 * When a parent is capability-hidden AND has no visible descendants,
	 * the parent is removed entirely.
	 */
	public function test_capability_hidden_without_descendants_removes_parent() {
		$default = array(
			'woocommerce' => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'parent-cap'  => array(
				'parent'     => 'woocommerce',
				'title'      => 'Parent',
				'position'   => 30,
				'capability' => 'manage_options',
			),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array( array( 'Parent', 'manage_options', 'parent-cap' ) ),
		);

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );
		$tree    = $builder->apply_capability_filter( $tree );

		$this->assertArrayNotHasKey( 'parent-cap', $tree );
	}

	/**
	 * WP's CPT submenu auto-generates a self-reference entry as the first
	 * item (`$submenu['edit.php?post_type=product'][5][2] === 'edit.php?post_type=product'`).
	 * Hoist it under a synthetic `<slug>--all` key with `url` pointing back
	 * at the rail root, so populate_root_submenus emits a working "All Products"
	 * row first in the rail flyout.
	 */
	public function test_cpt_self_reference_is_hoisted_under_synthetic_key() {
		$default = array(
			'woocommerce'                => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'edit.php?post_type=product' => array(
				'parent'   => 'woocommerce',
				'title'    => 'Products',
				'position' => 30,
			),
		);

		$raw_menu    = array(
			array( 'WooCommerce', 'read', 'woocommerce', '', '' ),
			array( 'Products', 'edit_products', 'edit.php?post_type=product', '', '' ),
		);
		$raw_submenu = array(
			'edit.php?post_type=product' => array(
				array( 'All Products', 'edit_products', 'edit.php?post_type=product' ),
				array( 'Reviews', 'edit_products', 'product-reviews' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'edit.php?post_type=product--all', $tree );
		$this->assertSame( 'edit.php?post_type=product', $tree['edit.php?post_type=product--all']['parent'] );
		$this->assertSame( 'edit.php?post_type=product', $tree['edit.php?post_type=product--all']['url'] );
		$this->assertSame( 'All Products', $tree['edit.php?post_type=product--all']['title'] );
		$this->assertSame( 'rehomed-self', $tree['edit.php?post_type=product--all']['source'] );
		// Sorted first via position=1.
		$this->assertSame( 1, $tree['edit.php?post_type=product--all']['position'] );
		// Other rehomed children still hoist normally with auto positions.
		$this->assertArrayHasKey( 'product-reviews', $tree );
		$this->assertGreaterThan(
			$tree['edit.php?post_type=product--all']['position'],
			$tree['product-reviews']['position'],
			'Self-reference must sort before other rehomed children'
		);
	}

	/**
	 * Slugs in `Tree_Builder::AUTO_ATTACH_EXCLUDE` (e.g. `post-new.php?post_type=product`)
	 * are filtered out when hoisting submenu children — they're either
	 * legacy redirects or duplicates of more idiomatic actions surfaced
	 * elsewhere in the UI.
	 */
	public function test_auto_attach_exclude_filters_rehomed_children() {
		$default = array(
			'woocommerce'                => array(
				'parent'   => null,
				'title'    => 'WooCommerce',
				'position' => 2,
			),
			'edit.php?post_type=product' => array(
				'parent'   => 'woocommerce',
				'title'    => 'Products',
				'position' => 30,
			),
		);

		$raw_menu    = array(
			array( 'WooCommerce', 'read', 'woocommerce', '', '' ),
			array( 'Products', 'edit_products', 'edit.php?post_type=product', '', '' ),
		);
		$raw_submenu = array(
			'edit.php?post_type=product' => array(
				array( 'Add New Product', 'edit_products', 'post-new.php?post_type=product' ),
				array( 'Reviews', 'edit_products', 'product-reviews' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayNotHasKey( 'post-new.php?post_type=product', $tree );
		$this->assertArrayHasKey( 'product-reviews', $tree );
	}
}
