<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\AI\Connection;
use Automattic\WooCommerce\Blocks\Images\Pexels;
use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\AIContent\PatternsHelper;
use Automattic\WooCommerce\Blocks\AIContent\UpdatePatterns;
use Automattic\WooCommerce\Blocks\AIContent\UpdateProducts;

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
	const SLUG_REGEX                 = '/^[A-z0-9\/_-]+$/';
	const COMMA_SEPARATED_REGEX      = '/[\s,]+/';
	const PATTERNS_AI_DATA_POST_TYPE = 'patterns_ai_data';

	/**
	 * Path to the patterns directory.
	 *
	 * @var string $patterns_path
	 */
	private $patterns_path;

	/**
	 * Constructor for class
	 *
	 * @param Package $package An instance of Package.
	 */
	public function __construct( Package $package ) {
		$this->patterns_path = $package->get_path( 'patterns' );

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
			return update_option( 'woocommerce_blocks_allow_ai_connection', false );
		}

		$token = $ai_connection->get_jwt_token( $site_id );

		if ( is_wp_error( $token ) ) {
			return update_option( 'woocommerce_blocks_allow_ai_connection', false );
		}

		return update_option( 'woocommerce_blocks_allow_ai_connection', true );
	}

	/**
	 * Fetch the WooCommerce patterns from the Patterns Tool Kit (PTK) API and register them.
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

		$patterns     = wp_safe_remote_get( 'https://public-api.wordpress.com/rest/v1/ptk/patterns/en?site=wooblockpatterns.wordpress.com' );
		$body         = wp_remote_retrieve_body( $patterns );
		$decoded_body = json_decode( $body );
		$dictionary   = PatternsHelper::get_patterns_dictionary();

		foreach ( $decoded_body as $data ) {
			if ( empty( $data->name ) ) {
				_doing_it_wrong(
					'register_block_patterns',
					esc_html(
						sprintf(
						/* translators: %s: file name. */
							__( 'Could not register file "%s" as a block pattern ("Slug" field missing)', 'woocommerce' ),
							$data->name
						)
					),
					'6.0.0'
				);
				continue;
			}

			if ( ! preg_match( self::SLUG_REGEX, $data->name ) ) {
				_doing_it_wrong(
					'register_block_patterns',
					esc_html(
						sprintf(
						/* translators: %1s: file name; %2s: slug value found. */
							__( 'Could not register the "%1$s" block pattern (invalid slug "%2$s")', 'woocommerce' ),
							$data->title,
							$data->name
						)
					),
					'6.0.0'
				);
				continue;
			}

			if ( \WP_Block_Patterns_Registry::get_instance()->is_registered( $data->name ) ) {
				continue;
			}

			// Title is a required property.
			if ( ! $data->title ) {
				_doing_it_wrong(
					'register_block_patterns',
					esc_html(
						sprintf(
						/* translators: %1s: file name; %2s: slug value found. */
							__( 'Could not register the "%s" block pattern ("Title" field missing)', 'woocommerce' ),
							$data->name
						)
					),
					'6.0.0'
				);
				continue;
			}

			$pattern_data_from_dictionary = $this->get_pattern_from_dictionary( $dictionary, $data->name );

			/*
				For patterns that can have AI-generated content, we need to get its content from the dictionary and pass
				it to the pattern file through the "$content" and "$images" variables.
				This is to avoid having to access the dictionary for each pattern when it's registered or inserted.
				Before the "$content" and "$images" variables were populated in each pattern. Since the pattern
				registration happens in the init hook, the dictionary was being access one for each pattern and
				for each page load. This way we only do it once on registration.
				For more context: https://github.com/woocommerce/woocommerce-blocks/pull/11733
			*/

			$content = array();
			$images  = array();
			if ( ! is_null( $pattern_data_from_dictionary ) ) {
				$content = $pattern_data_from_dictionary['content'];
				$images  = $pattern_data_from_dictionary['images'] ?? array();
			}

			$pattern_categories = array();
			foreach ( $data->categories as $key => $category ) {
				$pattern_categories[] = $category->slug;

				register_block_pattern_category( $category->slug, array( 'label' => _x( $category->title, 'Block pattern category' ) ) );
			}

			register_block_pattern( $data->name, [
				'title'       => $data->title,
				'slug'        => $data->name,
				'description' => $data->description,
				'content'     => $data->html,
				'categories'  => $pattern_categories,
			] );
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

	/**
	 * Filter the patterns dictionary to get the pattern data corresponding to the pattern slug.
	 *
	 * @param array  $dictionary The patterns dictionary.
	 * @param string $slug The pattern slug.
	 *
	 * @return array|null
	 */
	private function get_pattern_from_dictionary( $dictionary, $slug ) {
		foreach ( $dictionary as $pattern_dictionary ) {
			if ( isset( $pattern_dictionary['slug'] ) && $pattern_dictionary['slug'] === $slug ) {
				return $pattern_dictionary;
			}
		}

		return null;
	}
}
