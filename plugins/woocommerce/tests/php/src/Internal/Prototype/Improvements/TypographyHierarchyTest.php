<?php

namespace Automattic\WooCommerce\Tests\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\Improvements\TypographyHierarchy;
use WP_UnitTestCase;

/**
 * @covers TypographyHierarchy
 */
class TypographyHierarchyTest extends WP_UnitTestCase {

	public function tear_down(): void {
		remove_action( 'admin_head', array( TypographyHierarchy::class, 'output_styles' ) );
		unset( $_COOKIE['wc_prototype_flags'] );
		parent::tear_down();
	}

	public function test_init_does_not_register_hooks_when_flag_is_disabled(): void {
		// No cookie set — flag is off by default.
		TypographyHierarchy::init();

		$this->assertFalse(
			has_action( 'admin_head', array( TypographyHierarchy::class, 'output_styles' ) ),
			'admin_head hook should not be registered when flag is off'
		);
	}

	public function test_init_registers_hooks_when_flag_is_enabled(): void {
		$_COOKIE['wc_prototype_flags'] = wp_json_encode( array( 'typography_hierarchy' => true ) );

		TypographyHierarchy::init();

		$this->assertNotFalse(
			has_action( 'admin_head', array( TypographyHierarchy::class, 'output_styles' ) ),
			'admin_head hook should be registered when flag is on'
		);
	}
}
