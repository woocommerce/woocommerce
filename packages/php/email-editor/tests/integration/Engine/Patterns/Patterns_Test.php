<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Patterns;

/**
 * Integration test for Patterns class
 */
class Patterns_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Patterns instance
	 *
	 * @var Patterns
	 */
	private $patterns;
	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->patterns = $this->di_container->get( Patterns::class );
		$this->cleanup_patterns();
	}

	/**
	 * Test that the pattern categories are registered in WP_Block_Patterns_Registry
	 */
	public function testItRegistersPatternCategories() {
		$this->patterns->initialize();
		$categories = \WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered();
		/** @var array{name: string, label: string, description: string} $category */ // phpcs:ignore
		$category = array_pop( $categories );
		$this->assertEquals( 'email-contents', $category['name'] );
		$this->assertEquals( 'Email Contents', $category['label'] );
		$this->assertEquals( 'A collection of email content layouts.', $category['description'] );
	}

	/**
	 * Clean registered patterns and categories
	 */
	private function cleanup_patterns() {
		$categories_registry = \WP_Block_Pattern_Categories_Registry::get_instance();
		$categories          = $categories_registry->get_all_registered();
		foreach ( $categories as $category ) {
			$categories_registry->unregister( $category['name'] );
		}
	}
}
