<?php
declare(strict_types=1);

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
	const CATEGORIES_PREFIXES = [ '_woo_', '_dotcom_imported_' ];

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

		add_action( 'init', array( $this, 'register_block_patterns' ) );

		if ( Features::is_enabled( 'pattern-toolkit-full-composability' ) ) {
			add_action( 'init', array( $this, 'register_ptk_patterns' ) );
		}
	}

	/**
	 * Returns the Patterns dictionary.
	 *
	 * @return array|WP_Error
	 */
	private function get_patterns_dictionary() {
		if ( null === $this->dictionary ) {
			$this->dictionary = PatternsHelper::get_patterns_dictionary();
		}

		return $this->dictionary;
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

		$patterns = $this->get_block_patterns();
		foreach ( $patterns as $pattern ) {
			$this->pattern_registry->register_block_pattern( $pattern['source'], $pattern, $this->get_patterns_dictionary() );
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
			'featureFlag'   => 'Feature Flag',
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
			$data           = get_file_data( $file, $default_headers );
			$data['source'] = $file;
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

		// The most efficient way to check for an existing action is to use `as_has_scheduled_action`, but in unusual
		// cases where another plugin has loaded a very old version of Action Scheduler, it may not be available to us.
		$has_scheduled_action = function_exists( 'as_has_scheduled_action' ) ? 'as_has_scheduled_action' : 'as_next_scheduled_action';

		$patterns = $this->ptk_patterns_store->get_patterns();
		if ( empty( $patterns ) ) {
			// Only log once per day by using a transient.
			$transient_key = 'wc_ptk_pattern_store_warning';
			// By only logging when patterns are empty and no fetch is scheduled,
			// we ensure that warnings are only generated in genuinely problematic situations,
			// such as when the pattern fetching mechanism has failed entirely.
			if ( ! get_transient( $transient_key ) && ! call_user_func( $has_scheduled_action, 'fetch_patterns' ) ) {
				wc_get_logger()->warning(
					__( 'Empty patterns received from the PTK Pattern Store', 'woocommerce' ),
				);
				// Set the transient to true to indicate that the warning has been logged in the current day.
				set_transient( $transient_key, true, DAY_IN_SECONDS );
			}
			return;
		}

		$patterns = $this->parse_categories( $patterns );

		foreach ( $patterns as $pattern ) {
			$pattern['slug']    = $pattern['name'];
			$pattern['content'] = $pattern['html'];

			$this->pattern_registry->register_block_pattern( $pattern['ID'], $pattern, $this->get_patterns_dictionary() );
		}
	}

	/**
	 * Parse prefixed categories from the PTK patterns into the actual WooCommerce categories.
	 *
	 * @param array $patterns The patterns to parse.
	 * @return array The parsed patterns.
	 */
	private function parse_categories( array $patterns ) {
		return array_map(
			function ( $pattern ) {
				$pattern['categories'] = array_map(
					function ( $category ) {
						foreach ( self::CATEGORIES_PREFIXES as $prefix ) {
							if ( strpos( $category['title'], $prefix ) !== false ) {
								$parsed_category   = str_replace( $prefix, '', $category['title'] );
								$parsed_category   = str_replace( '_', ' ', $parsed_category );
								$category['title'] = ucfirst( $parsed_category );
							}
						}

						return $category;
					},
					$pattern['categories']
				);
				return $pattern;
			},
			$patterns
		);
	}
}
