<?php

namespace Automattic\WooCommerce\Tests\Blocks\Patterns;

use Automattic\WooCommerce\Blocks\Patterns\PatternRegistry;

/**
 * Unit tests for the PatternRegistry class.
 */
class PatternRegistryTest extends \WP_UnitTestCase {
	/**
	 * The registry instance.
	 *
	 * @var PatternRegistry $client
	 */
	private PatternRegistry $pattern_registry;

	/**
	 * Initialize the registry instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->pattern_registry = new PatternRegistry();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		unregister_block_pattern( 'my-pattern' );
		parent::tearDown();
	}

	/**
	 * Test that a pattern should not be registered without a slug.
	 */
	public function test_should_not_register_a_pattern_without_slug() {
		$pattern = [
			'title' => 'My Pattern',
		];

		$this->setExpectedIncorrectUsage( 'register_block_patterns' );
		$this->pattern_registry->register_block_pattern( 'source', $pattern, [] );
	}

	/**
	 * Test that a pattern should not be registered with a wrong slug.
	 */
	public function test_should_not_register_a_pattern_with_a_wrong_slug() {
		$pattern = [
			'title' => 'My Pattern',
			'slug'  => 'my-pattern!',
		];

		$this->setExpectedIncorrectUsage( 'register_block_patterns' );
		$this->pattern_registry->register_block_pattern( 'source', $pattern, [] );
	}

	/**
	 * Test that a pattern should not be registered without a title.
	 */
	public function test_should_not_register_a_pattern_without_a_title() {
		$pattern = [
			'slug' => 'my-pattern',
		];

		$this->setExpectedIncorrectUsage( 'register_block_patterns' );
		$this->pattern_registry->register_block_pattern( 'source', $pattern, [] );
	}

	/**
	 * Test comma separated categories are registered.
	 */
	public function test_comma_separated_categories_are_registered() {
		$pattern = [
			'title'      => 'My Pattern',
			'slug'       => 'my-pattern',
			'content'    => 'My pattern content',
			'categories' => 'cat1,cat2',
		];

		$this->pattern_registry->register_block_pattern( 'source', $pattern, [] );

		$cat1 = \WP_Block_Pattern_Categories_Registry::get_instance()->get_registered( 'cat-1' );
		$this->assertEquals(
			array(
				'label' => 'Cat 1',
				'name'  => 'cat-1',
			),
			$cat1,
		);

		$cat2 = \WP_Block_Pattern_Categories_Registry::get_instance()->get_registered( 'cat-2' );
		$this->assertEquals(
			array(
				'label' => 'Cat 2',
				'name'  => 'cat-2',
			),
			$cat2,
		);
	}

	/**
	 * Test array categories are registered.
	 */
	public function test_array_categories_are_registered() {
		$pattern = [
			'title'      => 'My Pattern',
			'slug'       => 'my-pattern',
			'content'    => 'My pattern content',
			'categories' => array(
				'cat1' => array( 'title' => 'cat1' ),
				'cat2' => array( 'title' => 'cat2' ),
			),
		];

		$this->pattern_registry->register_block_pattern( 'source', $pattern, [] );

		$cat1 = \WP_Block_Pattern_Categories_Registry::get_instance()->get_registered( 'cat-1' );

		$this->assertEquals(
			array(
				'label' => 'Cat 1',
				'name'  => 'cat-1',
			),
			$cat1,
		);

		$cat2 = \WP_Block_Pattern_Categories_Registry::get_instance()->get_registered( 'cat-2' );
		$this->assertEquals(
			array(
				'label' => 'Cat 2',
				'name'  => 'cat-2',
			),
			$cat2,
		);
	}

	/**
	 * Test HTML entities are decoded in pattern titles.
	 */
	public function test_html_entities_are_decoded_in_pattern_title() {
		$pattern = [
			'title'   => 'Featured Products: Fresh &amp; Tasty',
			'slug'    => 'my-pattern',
			'content' => '<!-- wp:group {"metadata":{"name":"Sweet Organic Lemons"}} -->
<div class="wp-block-group"></div>
<!-- /wp:group -->',
		];

		$this->pattern_registry->register_block_pattern( 'source', $pattern, [] );

		$registered_pattern = \WP_Block_Patterns_Registry::get_instance()->get_registered( $pattern['slug'] );

		$this->assertSame( 'Featured Products: Fresh & Tasty', $registered_pattern['title'] );
	}

	/**
	 * Enable the feature flag.
	 *
	 * @param array $features The features.
	 * @return array|string[]
	 */
	public function enable_feature_flag( $features ) {
		return array_merge( $features, array( 'enabled-feature-flag' ) );
	}
}
