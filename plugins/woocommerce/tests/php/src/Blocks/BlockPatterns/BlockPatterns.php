<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockPatterns;

use Automattic\WooCommerce\Blocks\Patterns\PatternRegistry;
use Automattic\WooCommerce\Blocks\BlockPatterns as TestedBlockPatterns;
use Automattic\WooCommerce\Blocks\Patterns\PTKPatternsStore;
use Automattic\WooCommerce\Blocks\Domain\Package;

/**
 * Unit tests for the BlockPatterns class.
 */
class BlockPatterns extends \WP_UnitTestCase {

	/**
	 * Holds the BlockPatterns under test.
	 *
	 * @var TestedBlockPatterns The BlockPatterns under test.
	 */
	private $block_patterns;

	/**
	 * Holds the mock PatternRegistry instance.
	 *
	 * @var PatternRegistry The mock PatternRegistry.
	 */
	private $pattern_registry;


	/**
	 * Holds the mock PTKPatternsStore instance.
	 *
	 * @var PTKPatternsStore The mock PTKPatternsStore.
	 */
	private $ptk_patterns_store;

	/**
	 * Sets up a new TestedBlockPatterns so it can be tested.
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_site_transient( 'woocommerce_blocks_patterns' );

		$package                  = new Package( '0.1.0', __DIR__ );
		$this->pattern_registry   = $this->createMock( PatternRegistry::class );
		$this->ptk_patterns_store = $this->createMock( PTKPatternsStore::class );

		$this->block_patterns = new TestedBlockPatterns(
			$package,
			$this->pattern_registry,
			$this->ptk_patterns_store
		);
	}

	/**
	 * Tests if patterns are registered with the correct pattern data.
	 */
	public function test_block_patterns_registration() {

		ob_start();
		include __DIR__ . '/patterns/mock-header.php';
		$mock_header_content = ob_get_clean();

		ob_start();
		include __DIR__ . '/patterns/mock-footer.php';
		$mock_footer_content = ob_get_clean();

		$this->pattern_registry
			->expects( $this->exactly( 2 ) )
			->method( 'register_block_pattern' )
			->withConsecutive(
				array(
					__DIR__ . '/patterns/mock-footer.php',
					array(
						'title'         => 'Mock Footer',
						'slug'          => 'woocommerce-blocks/mock-footer',
						'description'   => '',
						'viewportWidth' => '',
						'categories'    => 'WooCommerce',
						'keywords'      => '',
						'blockTypes'    => 'core/template-part/footer',
						'inserter'      => '',
						'featureFlag'   => '',
						'templateTypes' => '',
						'source'        => __DIR__ . '/patterns/mock-footer.php',
						'content'       => $mock_footer_content,
					),
				),
				array(
					__DIR__ . '/patterns/mock-header.php',
					array(
						'title'         => 'Mock Header',
						'slug'          => 'woocommerce-blocks/mock-header',
						'description'   => '',
						'viewportWidth' => '',
						'categories'    => 'WooCommerce',
						'keywords'      => '',
						'blockTypes'    => 'core/template-part/header',
						'inserter'      => '',
						'featureFlag'   => '',
						'templateTypes' => '',
						'source'        => __DIR__ . '/patterns/mock-header.php',
						'content'       => $mock_header_content,
					),
				),
			);

		$this->block_patterns->register_block_patterns();
	}

	/**
	 * Tests if patterns are registered with the cached data.
	 */
	public function test_cached_block_patterns_registration() {
		$mock_patterns = array(
			array(
				'title'   => 'Mock Cached',
				'source'  => 'mock-cached.php',
				'content' => '',
			),
		);
		$pattern_data  = array(
			'version'  => WOOCOMMERCE_VERSION,
			'patterns' => $mock_patterns,
		);

		set_site_transient( 'woocommerce_blocks_patterns', $pattern_data );

		$expected_pattern            = $mock_patterns[0];
		$expected_pattern['source']  = __DIR__ . '/patterns/mock-cached.php';
		$expected_pattern['content'] = '';

		$this->pattern_registry
			->expects( $this->exactly( 1 ) )
			->method( 'register_block_pattern' )
			->with(
				__DIR__ . '/patterns/mock-cached.php',
				$expected_pattern,
			);

		$this->block_patterns->register_block_patterns();
	}

	/**
	 * Tests if patterns are registered with the cached data.
	 */
	public function test_invalid_cached_block_patterns_registration() {
		$mock_patterns = array(
			array(
				'title'  => 'Mock Cached',
				'source' => __DIR__ . '/patterns/mock-cached.php',
			),
		);
		$pattern_data  = array(
			'version'  => '1.0.0-old',
			'patterns' => $mock_patterns,
		);

		set_site_transient( 'woocommerce_blocks_patterns', $pattern_data );

		ob_start();
		include __DIR__ . '/patterns/mock-header.php';
		$mock_header_content = ob_get_clean();

		ob_start();
		include __DIR__ . '/patterns/mock-footer.php';
		$mock_footer_content = ob_get_clean();

		$this->pattern_registry
			->expects( $this->exactly( 2 ) )
			->method( 'register_block_pattern' )
			->withConsecutive(
				array(
					__DIR__ . '/patterns/mock-footer.php',
					array(
						'title'         => 'Mock Footer',
						'slug'          => 'woocommerce-blocks/mock-footer',
						'description'   => '',
						'viewportWidth' => '',
						'categories'    => 'WooCommerce',
						'keywords'      => '',
						'blockTypes'    => 'core/template-part/footer',
						'inserter'      => '',
						'featureFlag'   => '',
						'templateTypes' => '',
						'source'        => __DIR__ . '/patterns/mock-footer.php',
						'content'       => $mock_footer_content,
					),
				),
				array(
					__DIR__ . '/patterns/mock-header.php',
					array(
						'title'         => 'Mock Header',
						'slug'          => 'woocommerce-blocks/mock-header',
						'description'   => '',
						'viewportWidth' => '',
						'categories'    => 'WooCommerce',
						'keywords'      => '',
						'blockTypes'    => 'core/template-part/header',
						'inserter'      => '',
						'featureFlag'   => '',
						'templateTypes' => '',
						'source'        => __DIR__ . '/patterns/mock-header.php',
						'content'       => $mock_header_content,
					),
				),
			);

		$this->block_patterns->register_block_patterns();
	}
}
