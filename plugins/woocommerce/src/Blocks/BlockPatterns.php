<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Patterns\PatternRegistry;

/**
 * Registers patterns under the `./patterns/` directory and updates their content.
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
	 * Constructor for class
	 *
	 * @param Package         $package An instance of Package.
	 * @param PatternRegistry $pattern_registry An instance of PatternRegistry.
	 */
	public function __construct(
		Package $package,
		PatternRegistry $pattern_registry
	) {
		$this->patterns_path    = $package->get_path( 'patterns' );
		$this->pattern_registry = $pattern_registry;

		add_action( 'init', array( $this, 'register_block_patterns' ) );
	}

	/**
	 * Register block patterns from core.
	 *
	 * The pattern content is not loaded here. Instead, each pattern is registered
	 * with the absolute path to its source file (via the `filePath` property), so
	 * that core's `WP_Block_Patterns_Registry` loads (and caches) the content
	 * lazily, only when a pattern is actually requested. This avoids the cost of
	 * `include`-ing all pattern files on every request (e.g. front-end, REST,
	 * cron), where patterns are never consumed.
	 *
	 * @return void
	 */
	public function register_block_patterns() {
		if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
			return;
		}

		$patterns = $this->get_block_patterns();
		foreach ( $patterns as $pattern ) {
			// The pattern list can come from a stale or malformed site transient, so make sure the source is a
			// usable string before dereferencing it, to avoid a PHP warning when building the path.
			if ( empty( $pattern['source'] ) || ! is_string( $pattern['source'] ) ) {
				continue;
			}

			$pattern_path = $this->patterns_path . '/' . $pattern['source'];

			// The pattern list can come from a stale cache, so confirm the file
			// still exists before registering it with a `filePath`. Without
			// inline content, core would otherwise emit an undefined-content
			// warning when it tries to load a missing file on demand. This
			// mirrors core's own `_register_theme_block_patterns()` guard.
			if ( ! file_exists( $pattern_path ) ) {
				continue;
			}

			$pattern['source']   = $pattern_path;
			$pattern['filePath'] = $pattern_path;

			$this->pattern_registry->register_block_pattern( $pattern_path, $pattern );
		}
	}

	/**
	 * Gets block pattern data from the cache if available
	 *
	 * @return array Block pattern data.
	 */
	private function get_block_patterns() {
		$pattern_data = $this->get_pattern_cache();

		if ( is_array( $pattern_data ) ) {
			return $pattern_data;
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
			'templateTypes' => 'Template Types',
		);

		if ( ! file_exists( $this->patterns_path ) ) {
			return array();
		}

		$files = glob( $this->patterns_path . '/*.php' );
		if ( ! $files ) {
			return array();
		}

		$patterns = array();

		foreach ( $files as $file ) {
			$data = get_file_data( $file, $default_headers );
			// We want to store the relative path in the cache, so we can use it later to register the pattern.
			$data['source'] = str_replace( $this->patterns_path . '/', '', $file );
			$patterns[]     = $data;
		}

		$this->set_pattern_cache( $patterns );
		return $patterns;
	}

	/**
	 * Gets block pattern cache.
	 *
	 * @return array|false Returns an array of patterns if cache is found, otherwise false.
	 */
	private function get_pattern_cache() {
		$pattern_data = get_site_transient( 'woocommerce_blocks_patterns' );

		if ( is_array( $pattern_data ) && WOOCOMMERCE_VERSION === $pattern_data['version'] ) {
			return $pattern_data['patterns'];
		}

		return false;
	}

	/**
	 * Sets block pattern cache.
	 *
	 * @param array $patterns Block patterns data to set in cache.
	 */
	private function set_pattern_cache( array $patterns ) {
		$pattern_data = array(
			'version'  => WOOCOMMERCE_VERSION,
			'patterns' => $patterns,
		);

		set_site_transient( 'woocommerce_blocks_patterns', $pattern_data, MONTH_IN_SECONDS );
	}
}
