<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates_Registry;

/**
 * Test cases for Templates::register_post_types_to_api().
 */
class Templates_Test extends Email_Editor_Unit_Test {

	/**
	 * The System Under Test.
	 *
	 * @var Templates
	 */
	private Templates $sut;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wc_ee_rest_field_registered'] = false;
		$GLOBALS['wc_ee_test_schema']           = array( 'properties' => array() );
		$this->sut                              = new Templates( new Templates_Registry() );
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wc_ee_rest_field_registered'], $GLOBALS['wc_ee_test_schema'] );
		parent::tearDown();
	}

	/**
	 * Test that the field is registered when post_types is absent.
	 *
	 * @testdox Should register the field when post_types is absent from the schema (WP ≤ 6.9).
	 */
	public function test_registers_field_when_post_types_absent_from_schema(): void {
		$GLOBALS['wc_ee_test_schema'] = array( 'properties' => array() );

		$this->sut->register_post_types_to_api();

		$this->assertTrue( $GLOBALS['wc_ee_rest_field_registered'], 'Field should be registered when post_types is absent from the schema.' );
	}

	/**
	 * Test that registration is skipped when view context is present.
	 *
	 * @testdox Should skip registration when post_types is present with view context (future WP).
	 */
	public function test_skips_registration_when_view_context_present(): void {
		$GLOBALS['wc_ee_test_schema'] = array(
			'properties' => array(
				'post_types' => array( 'context' => array( 'view', 'edit', 'embed' ) ),
			),
		);

		$this->sut->register_post_types_to_api();

		$this->assertFalse( $GLOBALS['wc_ee_rest_field_registered'], 'Field should not be registered when post_types is natively available in the view context.' );
	}

	/**
	 * Test that the field is registered when view context is missing (WP 7.0 scenario).
	 *
	 * @testdox Should register the field when post_types is present but view context is missing (WP 7.0).
	 */
	public function test_registers_field_when_post_types_present_but_view_context_missing(): void {
		$GLOBALS['wc_ee_test_schema'] = array(
			'properties' => array(
				'post_types' => array( 'context' => array( 'edit' ) ),
			),
		);

		$this->sut->register_post_types_to_api();

		$this->assertTrue( $GLOBALS['wc_ee_rest_field_registered'], 'Field should be registered when post_types is present but only in the edit context.' );
	}

	/**
	 * Test that the field is registered when post_types has no context key.
	 *
	 * @testdox Should register the field when post_types is present but has no context key.
	 */
	public function test_registers_field_when_post_types_present_but_no_context_key(): void {
		$GLOBALS['wc_ee_test_schema'] = array(
			'properties' => array(
				'post_types' => array( 'type' => 'array' ),
			),
		);

		$this->sut->register_post_types_to_api();

		$this->assertTrue( $GLOBALS['wc_ee_rest_field_registered'], 'Field should be registered when post_types has no context key.' );
	}
}
