<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesRegistry;
use WC_Unit_Test_Case;

/**
 * Tests for the AbilitiesRegistry class.
 */
class AbilitiesRegistryTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should remove vendor rest_api_init hook when WP core provides abilities REST controllers.
	 */
	public function test_removes_vendor_rest_init_when_core_controllers_exist(): void {
		if ( ! class_exists( 'WP_REST_Abilities_V1_Run_Controller' ) ) {
			$this->markTestSkipped( 'Requires WordPress 6.9+ with Abilities REST controllers in core.' );
		}

		add_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ), 11 );

		new AbilitiesRegistry();

		$this->assertFalse(
			has_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ) ),
			'Vendor rest_api_init hook should be removed when WP core provides abilities REST controllers'
		);
	}

	/**
	 * @testdox Should keep vendor rest_api_init hook when WP core does not provide abilities REST controllers.
	 */
	public function test_keeps_vendor_rest_init_when_core_controllers_missing(): void {
		if ( class_exists( 'WP_REST_Abilities_V1_Run_Controller' ) ) {
			$this->markTestSkipped( 'Requires WordPress < 6.9 without Abilities REST controllers in core.' );
		}

		add_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ), 11 );

		new AbilitiesRegistry();

		$this->assertSame(
			11,
			has_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ) ),
			'Vendor rest_api_init hook should remain when WP core does not provide abilities REST controllers'
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ), 11 );
		remove_all_actions( 'abilities_api_categories_init' );
		remove_all_actions( 'wp_abilities_api_categories_init' );
		remove_all_actions( 'abilities_api_init' );
		remove_all_actions( 'wp_abilities_api_init' );
		parent::tearDown();
	}
}
