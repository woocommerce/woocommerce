<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Patterns\PatternUpdater;
use Automattic\WooCommerce\Blocks\Verticals\Client;
use Automattic\WooCommerce\Blocks\Verticals\VerticalsSelector;

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
	const SLUG_REGEX            = '/^[A-z0-9\/_-]+$/';
	const COMMA_SEPARATED_REGEX = '/[\s,]+/';

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
		add_action( 'update_option_woo_ai_describe_store_description', array( $this, 'schedule_patterns_content_update' ), 10, 2 );
		add_action( 'woocommerce_update_patterns_content', array( $this, 'update_patterns_content' ) );
	}

	/**
	 * Registers the block patterns and categories under `./patterns/`.
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

			if ( empty( $pattern_data['slug'] ) ) {
				_doing_it_wrong(
					'register_block_patterns',
					esc_html(
						sprintf(
						/* translators: %s: file name. */
							__( 'Could not register file "%s" as a block pattern ("Slug" field missing)', 'woo-gutenberg-products-block' ),
							$file
						)
					),
					'6.0.0'
				);
				continue;
			}

			if ( ! preg_match( self::SLUG_REGEX, $pattern_data['slug'] ) ) {
				_doing_it_wrong(
					'register_block_patterns',
					esc_html(
						sprintf(
						/* translators: %1s: file name; %2s: slug value found. */
							__( 'Could not register file "%1$s" as a block pattern (invalid slug "%2$s")', 'woo-gutenberg-products-block' ),
							$file,
							$pattern_data['slug']
						)
					),
					'6.0.0'
				);
				continue;
			}

			if ( \WP_Block_Patterns_Registry::get_instance()->is_registered( $pattern_data['slug'] ) ) {
				continue;
			}

			// Title is a required property.
			if ( ! $pattern_data['title'] ) {
				_doing_it_wrong(
					'register_block_patterns',
					esc_html(
						sprintf(
						/* translators: %1s: file name; %2s: slug value found. */
							__( 'Could not register file "%s" as a block pattern ("Title" field missing)', 'woo-gutenberg-products-block' ),
							$file
						)
					),
					'6.0.0'
				);
				continue;
			}

			// For properties of type array, parse data as comma-separated.
			foreach ( array( 'categories', 'keywords', 'blockTypes' ) as $property ) {
				if ( ! empty( $pattern_data[ $property ] ) ) {
					$pattern_data[ $property ] = array_filter(
						preg_split(
							self::COMMA_SEPARATED_REGEX,
							(string) $pattern_data[ $property ]
						)
					);
				} else {
					unset( $pattern_data[ $property ] );
				}
			}

			// Parse properties of type int.
			foreach ( array( 'viewportWidth' ) as $property ) {
				if ( ! empty( $pattern_data[ $property ] ) ) {
					$pattern_data[ $property ] = (int) $pattern_data[ $property ];
				} else {
					unset( $pattern_data[ $property ] );
				}
			}

			// Parse properties of type bool.
			foreach ( array( 'inserter' ) as $property ) {
				if ( ! empty( $pattern_data[ $property ] ) ) {
					$pattern_data[ $property ] = in_array(
						strtolower( $pattern_data[ $property ] ),
						array( 'yes', 'true' ),
						true
					);
				} else {
					unset( $pattern_data[ $property ] );
				}
			}

			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.LowLevelTranslationFunction
			$pattern_data['title'] = translate_with_gettext_context( $pattern_data['title'], 'Pattern title', 'woo-gutenberg-products-block' );
			if ( ! empty( $pattern_data['description'] ) ) {
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.LowLevelTranslationFunction
				$pattern_data['description'] = translate_with_gettext_context( $pattern_data['description'], 'Pattern description', 'woo-gutenberg-products-block' );
			}

			// The actual pattern content is the output of the file.
			ob_start();
			include $file;
			$pattern_data['content'] = ob_get_clean();
			if ( ! $pattern_data['content'] ) {
				continue;
			}

			foreach ( $pattern_data['categories'] as $key => $category ) {
				$category_slug = _wp_to_kebab_case( $category );

				$pattern_data['categories'][ $key ] = $category_slug;

				register_block_pattern_category(
					$category_slug,
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
					array( 'label' => __( $category, 'woo-gutenberg-products-block' ) )
				);
			}

			register_block_pattern( $pattern_data['slug'], $pattern_data );
		}
	}

	/**
	 * Update the patterns content when the store description is changed.
	 *
	 * @param string $option The option name.
	 * @param string $value The option value.
	 */
	public function schedule_patterns_content_update( $option, $value ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$action_scheduler = WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php';

		if ( ! file_exists( $action_scheduler ) ) {
			return;
		}

		require_once $action_scheduler;

		as_schedule_single_action( time(), 'woocommerce_update_patterns_content', array( $value ) );
	}

	/**
	 * Update the patterns content.
	 *
	 * @param string $value The new value saved for the add_option_woo_ai_describe_store_description option.
	 *
	 * @return bool|int|string|\WP_Error
	 */
	public function update_patterns_content( $value ) {
		$allow_ai_connection = get_option( 'woocommerce_blocks_allow_ai_connection' );

		if ( ! $allow_ai_connection ) {
			return new \WP_Error(
				'ai_connection_not_allowed',
				__( 'AI content generation is not allowed on this store. Update your store settings if you wish to enable this feature.', 'woo-gutenberg-products-block' )
			);
		}

		$vertical_id = ( new VerticalsSelector() )->get_vertical_id( $value );

		if ( is_wp_error( $vertical_id ) ) {
			return $vertical_id;
		}

		return ( new PatternUpdater() )->create_patterns_content( $vertical_id, new Client() );
	}
}
