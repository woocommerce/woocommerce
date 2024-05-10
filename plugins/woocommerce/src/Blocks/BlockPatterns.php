<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\Images\Pexels;
use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;
use Automattic\WooCommerce\Blocks\AIContent\UpdatePatterns;
use Automattic\WooCommerce\Blocks\AIContent\UpdateProducts;
use Automattic\WooCommerce\Blocks\Patterns\PatternRegistry;
use Automattic\WooCommerce\Blocks\Patterns\PTKClient;

/**
 * Registers patterns under the `./patterns/` directory and updates their content.
 * Each pattern is defined as a PHP file and defines its metadata using plugin-style headers.
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
	const PATTERNS_AI_DATA_POST_TYPE = 'patterns_ai_data';

	/**
	 * Path to the patterns' directory.
	 *
	 * @var string $patterns_path
	 */
	private $patterns_path;

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
	public function __construct( Package $package, PatternRegistry $pattern_registry ) {
		$this->patterns_path    = $package->get_path( 'patterns' );
		$this->pattern_registry = $pattern_registry;

		add_action( 'init', array( $this, 'register_block_patterns' ) );
		add_action( 'update_option_woo_ai_describe_store_description', array( $this, 'schedule_on_option_update' ), 10, 2 );
		add_action( 'update_option_woo_ai_describe_store_description', array( $this, 'update_ai_connection_allowed_option' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'schedule_on_plugin_update' ), 10, 2 );
		add_action( 'woocommerce_update_patterns_content', array( $this, 'update_patterns_content' ) );
	}

	/**
	 * Make sure the 'woocommerce_blocks_allow_ai_connection' option is set to true if the site is connected to AI.
	 *
	 * @param string $option The option name.
	 * @param string $value The option value.
	 *
	 * @return bool
	 */
	public function update_ai_connection_allowed_option( $option, $value ): bool {
		$ai_connection = new Connection();

		$site_id = $ai_connection->get_site_id();

		if ( is_wp_error( $site_id ) ) {
			return update_option( 'woocommerce_blocks_allow_ai_connection', false, true );
		}

		$token = $ai_connection->get_jwt_token( $site_id );

		if ( is_wp_error( $token ) ) {
			return update_option( 'woocommerce_blocks_allow_ai_connection', false, true );
		}

		return update_option( 'woocommerce_blocks_allow_ai_connection', true, true );
	}

	/**
	 * Register block patterns from core and PTK.
	 *
	 * @return void
	 */
	public function register_block_patterns() {
		if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
			return;
		}

		register_post_type(
			self::PATTERNS_AI_DATA_POST_TYPE,
			array(
				'labels'           => array(
					'name'          => __( 'Patterns AI Data', 'woocommerce' ),
					'singular_name' => __( 'Patterns AI Data', 'woocommerce' ),
				),
				'public'           => false,
				'hierarchical'     => false,
				'rewrite'          => false,
				'query_var'        => false,
				'delete_with_user' => false,
				'can_export'       => true,
			)
		);

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

		$dictionary = PatternsHelper::get_patterns_dictionary();

		$this->register_block_patterns_from_files( $default_headers, $dictionary );
	}

	/**
	 * Registers the block patterns and categories under `./patterns/`.
	 *
	 * @param array $default_headers The default headers a pattern can have.
	 * @param array $dictionary The patterns' dictionary.
	 *
	 * @return void
	 */
	private function register_block_patterns_from_files( array $default_headers, array $dictionary ) {
		if ( ! file_exists( $this->patterns_path ) ) {
			return;
		}

		$files = glob( $this->patterns_path . '/*.php' );
		if ( ! $files ) {
			return;
		}

		foreach ( $files as $file ) {
			$pattern_data = get_file_data( $file, $default_headers );

			$this->pattern_registry->register_block_pattern( $file, $pattern_data, $dictionary );
		}
	}



	/**
	 * Update the patterns content when the store description is changed.
	 *
	 * @param string $option The option name.
	 * @param string $value The option value.
	 */
	public function schedule_on_option_update( $option, $value ) {
		$last_business_description = get_option( 'last_business_description_with_ai_content_generated' );

		if ( $last_business_description === $value ) {
			return;
		}

		$this->schedule_patterns_content_update( $value );
	}

	/**
	 * Update the patterns content when the WooCommerce Blocks plugin is updated.
	 *
	 * @param \WP_Upgrader $upgrader_object  WP_Upgrader instance.
	 * @param array        $options  Array of bulk item update data.
	 */
	public function schedule_on_plugin_update( $upgrader_object, $options ) {
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( str_contains( $plugin, 'woocommerce.php' ) ) {
					$business_description = get_option( 'woo_ai_describe_store_description' );

					if ( $business_description ) {
						$this->schedule_patterns_content_update( $business_description );
					}
				}
			}
		}
	}

	/**
	 * Update the patterns content when the store description is changed.
	 *
	 * @param string $business_description The business description.
	 */
	public function schedule_patterns_content_update( $business_description ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$action_scheduler = WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php';

		if ( ! file_exists( $action_scheduler ) ) {
			return;
		}

		require_once $action_scheduler;

		as_schedule_single_action( time(), 'woocommerce_update_patterns_content', array( $business_description ) );
	}

	/**
	 * Update the patterns content.
	 *
	 * @param string $value The new value saved for the add_option_woo_ai_describe_store_description option.
	 *
	 * @return bool|string|\WP_Error
	 */
	public function update_patterns_content( $value ) {
		$allow_ai_connection = get_option( 'woocommerce_blocks_allow_ai_connection' );

		if ( ! $allow_ai_connection ) {
			return new \WP_Error(
				'ai_connection_not_allowed',
				__( 'AI content generation is not allowed on this store. Update your store settings if you wish to enable this feature.', 'woocommerce' )
			);
		}

		$ai_connection = new Connection();

		$site_id = $ai_connection->get_site_id();

		if ( is_wp_error( $site_id ) ) {
			return $site_id->get_error_message();
		}

		$token = $ai_connection->get_jwt_token( $site_id );

		if ( is_wp_error( $token ) ) {
			return $token->get_error_message();
		}

		$business_description = get_option( 'woo_ai_describe_store_description' );

		$images = ( new Pexels() )->get_images( $ai_connection, $token, $business_description );

		if ( is_wp_error( $images ) ) {
			return $images->get_error_message();
		}

		$populate_patterns = ( new UpdatePatterns() )->generate_content( $ai_connection, $token, $images, $business_description );

		if ( is_wp_error( $populate_patterns ) ) {
			return $populate_patterns->get_error_message();
		}

		$populate_products = ( new UpdateProducts() )->generate_content( $ai_connection, $token, $images, $business_description );

		if ( is_wp_error( $populate_products ) ) {
			return $populate_products->get_error_message();
		}

		return true;
	}
}
