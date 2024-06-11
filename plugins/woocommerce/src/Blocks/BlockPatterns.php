<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;
use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Patterns\PatternRegistry;
use Automattic\WooCommerce\Blocks\Patterns\PTKPatternsStore;
use WP_Error;

/**
 * Registers patterns under the `./patterns/` directory and from the PTK API and updates their content.
 * Each pattern from core is defined as a PHP file and defines its metadata using plugin-style headers.
 * The minimum required definition is:
 *
 *     /**
 *      * Title: My Pattern
 *      * Slug: my-theme/my-pattern
 *      *
 *
 * The output of the PHP source corresponds to the content of the pattern, e.g.:
 *
 *     <main><p><?php echo "Hello"; ?></p></main>
 *
 * Other settable fields include:
 *
 *   - Description
 *   - Viewport Width
 *   - Categories       (comma-separated values)
 *   - Keywords         (comma-separated values)
 *   - Block Types      (comma-separated values)
 *   - Inserter         (yes/no)
 *
 * @internal
 */
class BlockPatterns {
	/**
	 * Path to the patterns' directory.
	 *
	 * @var string $patterns_path
	 */
	private string $patterns_path;

	/**
	 * PatternRegistry instance.
	 *
	 * @var PatternRegistry $pattern_registry
	 */
	private PatternRegistry $pattern_registry;

	/**
	 * Patterns dictionary
	 *
	 * @var array|WP_Error
	 */
	private $dictionary;

	/**
	 * PTKPatternsStore instance.
	 *
	 * @var PTKPatternsStore $ptk_patterns_store
	 */
	private PTKPatternsStore $ptk_patterns_store;

	/**
	 * Constructor for class
	 *
	 * @param Package          $package An instance of Package.
	 * @param PatternRegistry  $pattern_registry An instance of PatternRegistry.
	 * @param PTKPatternsStore $ptk_patterns_store An instance of PTKPatternsStore.
	 */
	public function __construct(
		Package $package,
		PatternRegistry $pattern_registry,
		PTKPatternsStore $ptk_patterns_store
	) {
		$this->patterns_path      = $package->get_path( 'patterns' );
		$this->pattern_registry   = $pattern_registry;
		$this->ptk_patterns_store = $ptk_patterns_store;

		$this->dictionary = PatternsHelper::get_patterns_dictionary();

		add_action( 'init', array( $this, 'register_block_patterns' ) );

		if ( Features::is_enabled( 'pattern-toolkit-full-composability' ) ) {
			add_action( 'init', array( $this, 'register_ptk_patterns' ) );
		}
	}

	/**
	 * Register block patterns from core.
	 *
	 * @return void
	 */
	public function register_block_patterns() {
		if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
			return;
		}

		$default_headers = array(
			'title'         => 'Title',
			'slug'          => 'Slug',
			'description'   => 'Description',
			'viewportWidth' => 'Viewport Width',
			'categories'    => 'Categories',
			'keywords'      => 'Keywords',
			'blockTypes'    => 'Block Types',
			'inserter'      => 'Inserter',
			'featureFlag'   => 'Feature Flag',
		);

		if ( ! file_exists( $this->patterns_path ) ) {
			return;
		}

		$files = glob( $this->patterns_path . '/*.php' );
		if ( ! $files ) {
			return;
		}

		foreach ( $files as $file ) {
			$pattern_data = get_file_data( $file, $default_headers );

			$this->pattern_registry->register_block_pattern( $file, $pattern_data, $this->dictionary );
		}
	}

	/**
	 * Register patterns from the Patterns Toolkit.
	 *
	 * @return void
	 */
	public function register_ptk_patterns() {
		// Only if the user has allowed tracking, we register the patterns from the PTK.
		$allow_tracking = 'yes' === get_option( 'woocommerce_allow_tracking' );
		if ( ! $allow_tracking ) {
			return;
		}

		$patterns = $this->ptk_patterns_store->get_patterns();
		if ( empty( $patterns ) ) {
			wc_get_logger()->warning(
				__( 'Empty patterns received from the PTK Pattern Store', 'woocommerce' ),
			);
			return;
		}

		foreach ( $patterns as $pattern ) {
			$pattern['slug']    = $pattern['name'];
			$pattern['content'] = $pattern['html'];

			$this->pattern_registry->register_block_pattern( $pattern['ID'], $pattern, $this->dictionary );
		}
	}
}
