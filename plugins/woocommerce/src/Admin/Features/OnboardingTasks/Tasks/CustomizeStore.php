<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use WP_Post;

/**
 * Customize Your Store Task
 *
 * @internal
 */
class CustomizeStore extends Task {

	/**
	 * Array of sample products for the Customize Your Store task.
	 *
	 * This array contains sample product data used to populate the patterns
	 * with example products when no real products exist. Each product
	 * includes a title, image filename, description, and price.
	 *
	 * @var array
	 */
	private $sample_products = array(
		array(
			'title'       => 'Vintage Typewriter',
			'image'       => 'writing-typing-keyboard-technology-white-vintage.jpg',
			'description' => 'A hit spy novel or a love letter? Anything you type using this vintage typewriter from the 20s is bound to make a mark.',
			'price'       => 90,
		),
		array(
			'title'       => 'Leather-Clad Leisure Chair',
			'image'       => 'table-wood-house-chair-floor-window.jpg',
			'description' => 'Sit back and relax in this comfy designer chair. High-grain leather and steel frame add luxury to your your leisure.',
			'price'       => 249,
		),
		array(
			'title'       => 'Black and White',
			'image'       => 'white-black-black-and-white-photograph-monochrome-photography.jpg',
			'description' => 'This 24" x 30" high-quality print just exudes summer. Hang it on the wall and forget about the world outside.',
			'price'       => 115,
		),
		array(
			'title'       => '3-Speed Bike',
			'image'       => 'road-sport-vintage-wheel-retro-old.jpg',
			'description' => 'Zoom through the streets on this premium 3-speed bike. Manufactured and assembled in Germany in the 80s.',
			'price'       => 115,
		),
		array(
			'title'       => 'Hi-Fi Headphones',
			'image'       => 'man-person-music-black-and-white-white-photography.jpg',
			'description' => 'Experience your favorite songs in a new way with these premium hi-fi headphones.',
			'price'       => 125,
		),
		array(
			'title'       => 'Retro Glass Jug (330 ml)',
			'image'       => 'drinkware-liquid-tableware-dishware-bottle-fluid.jpg',
			'description' => 'Thick glass and a classic silhouette make this jug a must-have for any retro-inspired kitchen.',
			'price'       => 115,
		),
	);

	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );

		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_site_editor_scripts' ) );

		add_action( 'show_admin_bar', array( $this, 'possibly_hide_wp_admin_bar' ) );

		// Hook to remove unwanted UI elements when users are viewing with ?cys-hide-admin-bar=true.
		add_action( 'wp_head', array( $this, 'possibly_remove_unwanted_ui_elements' ) );

		add_action( 'save_post_wp_global_styles', array( $this, 'mark_task_as_complete_block_theme' ), 10, 3 );
		add_action( 'save_post_wp_template', array( $this, 'mark_task_as_complete_block_theme' ), 10, 3 );
		add_action( 'save_post_wp_template_part', array( $this, 'mark_task_as_complete_block_theme' ), 10, 3 );
		add_action( 'customize_save_after', array( $this, 'mark_task_as_complete_classic_theme' ) );

		if ( WC()->is_rest_api_request() ) {
			$referring_url = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			$parsed_url    = wp_parse_url( $referring_url );

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$is_assembler_hub = (
				isset( $parsed_url['query'] ) &&
				str_starts_with( $parsed_url['query'], 'page=wc-admin' ) &&
				str_contains( $parsed_url['query'], 'path=%2Fcustomize-store%2Fassembler-hub' ) &&
				isset( $parsed_url['path'] ) &&
				str_contains( $parsed_url['path'], '/wp-admin/admin.php' )
			);

			if ( $is_assembler_hub ) {
				add_filter( 'rest_prepare_product', array( $this, 'add_product_rendered_title' ), 10, 3 );
				add_filter( 'the_posts', array( $this, 'add_sample_products_to_query' ), 10, 2 );
				add_filter( 'rest_pre_dispatch', array( $this, 'add_sample_product_data_to_query' ), 10, 3 );
			}
		}
	}

	/**
	 * Mark the CYS task as complete whenever the user updates their global styles.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post being updated.
	 *
	 * @return void
	 */
	public function mark_task_as_complete_block_theme( $post_id, $post, $update ) {
		if ( $post instanceof WP_Post ) {
			$is_cys_complete = $this->has_custom_global_styles( $post ) || $this->has_custom_template( $post );

			if ( $is_cys_complete ) {
				update_option( 'woocommerce_admin_customize_store_completed', 'yes' );
			}
		}
	}

	/**
	 * Mark the CYS task as complete whenever the user saves the customizer changes.
	 *
	 * @return void
	 */
	public function mark_task_as_complete_classic_theme() {
		update_option( 'woocommerce_admin_customize_store_completed', 'yes' );
	}

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'customize-store';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Customize your store ', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return '';
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return '';
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return get_option( 'woocommerce_admin_customize_store_completed' ) === 'yes';
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return true;
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'admin.php?page=wc-admin&path=%2Fcustomize-store' );
	}

	/**
	 * Generate a sample product post object.
	 *
	 * This method creates a WP_Post object representing a sample product
	 * using data from the sample products array.
	 *
	 * @param int $index The index of the sample product to use.
	 * @return WP_Post A WP_Post object representing a sample product.
	 */
	public function generate_sample_product_post_object( $index ) {
		$sample_product_data = $this->sample_products[ $index ];

		return new WP_Post(
			(object) array(
				'ID'                    => time() * 1000 + $index,
				'post_author'           => 1,
				'post_date'             => '',
				'post_date_gmt'         => '',
				'post_content'          => $sample_product_data['description'],
				'post_title'            => $sample_product_data['title'],
				'post_excerpt'          => $sample_product_data['description'],
				'post_status'           => 'publish',
				'comment_status'        => 'open',
				'ping_status'           => 'closed',
				'post_password'         => '',
				'post_name'             => sanitize_title( $sample_product_data['title'] ),
				'to_ping'               => '',
				'pinged'                => '',
				'post_modified'         => '',
				'post_modified_gmt'     => '',
				'post_content_filtered' => '',
				'post_parent'           => 0,
				'guid'                  => '',
				'menu_order'            => 0,
				'post_type'             => 'product',
				'post_mime_type'        => '',
				'comment_count'         => 0,
				'filter'                => 'raw',
			)
		);
	}

	/**
	 * Generate a sample product object.
	 *
	 * This method creates an array representing a sample product
	 * using data from the sample products array.
	 *
	 * @param int $product_id The ID of the product to generate. The last digit
	 *                        of the product ID is used to determine which sample product to use.
	 * @return array An array representing a sample product.
	 */
	public function generate_sample_product_object( $product_id ) {
		$last_digit          = substr( $product_id, -1 );
		$sample_product_data = $this->sample_products[ $last_digit ];
		$image_src           = plugins_url( '/assets/images/pattern-placeholders/' . $sample_product_data['image'], dirname( __DIR__, 4 ) );
		$currency_decimals   = wc_get_price_decimals();
		$prices              = ( new CurrencyFormatter() )->format(
			array(
				'price'         => $sample_product_data['price'] * 10 ** $currency_decimals,
				'regular_price' => $sample_product_data['price'] * 10 ** $currency_decimals,
				'sale_price'    => null,
				'price_range'   => null,
			)
		);

		return array(
			'id'                  => $product_id,
			'name'                => sanitize_text_field( $sample_product_data['title'] ),
			'slug'                => sanitize_title( $sample_product_data['title'] ),
			'parent'              => 0,
			'type'                => 'simple',
			'variation'           => '',
			'permalink'           => '',
			'sku'                 => '',
			'short_description'   => '',
			'description'         => $sample_product_data['description'],
			'on_sale'             => false,
			'prices'              => $prices,
			'price_html'          => '',
			'average_rating'      => '0',
			'review_count'        => 0,
			'images'              => array(
				(object) array(
					'src'       => $image_src,
					'thumbnail' => $image_src,
					'alt'       => '',
				),
			),
			'categories'          => array(),
			'tags'                => array(),
			'attributes'          => array(),
			'variations'          => array(),
			'has_options'         => false,
			'is_purchasable'      => true,
			'is_in_stock'         => true,
			'is_on_backorder'     => false,
			'low_stock_remaining' => null,
			'sold_individually'   => false,
			'extensions'          => array(),
		);
	}

	/**
	 * Adds the rendered title to the product response.
	 *
	 * This method sets the 'rendered' title of a product to be the same as its 'raw' title
	 * in the REST API response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     The original post object.
	 * @param WP_REST_Request  $request  The request object.
	 * @return WP_REST_Response The modified response object.
	 */
	public function add_product_rendered_title( $response, $post, $request ) {
		if ( ! empty( $response->data['title']['rendered'] ) || 'product' !== $post->post_type ) {
			return $response;
		}

		$response->data['title']['rendered'] = $response->data['title']['raw'];

		return $response;
	}

	/**
	 * Adds sample products to the query result if no products are found.
	 *
	 * This function is used to populate the product list with sample data when
	 * the store has no real products and a REST API request is made.
	 *
	 * @param array    $posts An array of post objects.
	 * @param WP_Query $query The WP_Query instance.
	 * @return array An array of post objects, potentially including sample products.
	 */
	public function add_sample_products_to_query( $posts, $query ) {
		if ( 'product' !== $query->query['post_type'] || ! WC()->is_rest_api_request() || ! empty( $posts ) ) {
			return $posts;
		}

		$sample_products = array();

		foreach ( $this->sample_products as $index => $sample_product ) {
			$sample_products[] = $this->generate_sample_product_post_object( $index );
		}

		return $sample_products;
	}

	/**
	 * Handles requests for nonexistent products by generating sample product data.
	 *
	 * This method intercepts REST API requests for product details. If the requested
	 * product doesn't exist and the product ID suggests it's a sample product
	 * (ID representing a timestamp within the last 24 hours), it returns a
	 * sample product object.
	 *
	 * @param mixed           $result  The current result, usually null for not found.
	 * @param WP_REST_Server  $server  The REST server instance.
	 * @param WP_REST_Request $request The current REST request.
	 *
	 * @return WP_REST_Response|mixed The sample product response if applicable, otherwise the original result.
	 */
	public function add_sample_product_data_to_query( $result, $server, $request ) {
		if ( false !== strpos( $request->get_route(), '/wc/store/v1/products/' ) ) {
			$product_id = (int) str_replace( '/wc/store/v1/products/', '', $request->get_route() );
			$product    = wc_get_product( $product_id );

			// Sample products use `time()` as the ID. This way we can detect that
			// it was a sample product returned 24 hours or less ago.
			if ( ! $product && $product_id + 86400000 > time() * 1000 ) {
				$sample_product = $this->generate_sample_product_object( $product_id );

				return rest_ensure_response( $sample_product );
			}
		}

		return $result;
	}

	/**
	 * Possibly add site editor scripts.
	 */
	public function possibly_add_site_editor_scripts() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_wc_admin_page = (
			isset( $_GET['page'] ) &&
			'wc-admin' === $_GET['page'] &&
			isset( $_GET['path'] )
		);

		$is_assembler_hub     = $is_wc_admin_page && str_starts_with( wc_clean( wp_unslash( $_GET['path'] ) ), '/customize-store/assembler-hub' );
		$is_transitional_page = $is_wc_admin_page && str_starts_with( wc_clean( wp_unslash( $_GET['path'] ) ), '/customize-store/transitional' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! ( $is_assembler_hub || $is_transitional_page ) ) {
			return;
		}

		// See: https://github.com/WordPress/WordPress/blob/master/wp-admin/site-editor.php.
		if ( ! wp_is_block_theme() ) {
			wp_die( esc_html__( 'The theme you are currently using is not compatible.', 'woocommerce' ) );
		}
		global $editor_styles;

		// Flag that we're loading the block editor.
		$current_screen = get_current_screen();
		$current_screen->is_block_editor( true );

		// Default to is-fullscreen-mode to avoid jumps in the UI.
		add_filter(
			'admin_body_class',
			static function ( $classes ) {
				return "$classes is-fullscreen-mode";
			}
		);

		$block_editor_context   = new \WP_Block_Editor_Context( array( 'name' => 'core/edit-site' ) );
		$indexed_template_types = array();
		foreach ( get_default_block_template_types() as $slug => $template_type ) {
			$template_type['slug']    = (string) $slug;
			$indexed_template_types[] = $template_type;
		}

		$custom_settings = array(
			'siteUrl'                   => site_url(),
			'postsPerPage'              => get_option( 'posts_per_page' ),
			'styles'                    => get_block_editor_theme_styles(),
			'defaultTemplateTypes'      => $indexed_template_types,
			'defaultTemplatePartAreas'  => get_allowed_block_template_part_areas(),
			'supportsLayout'            => wp_theme_has_theme_json(),
			'supportsTemplatePartsMode' => ! wp_is_block_theme() && current_theme_supports( 'block-template-parts' ),
		);

		// Add additional back-compat patterns registered by `current_screen` et al.
		$custom_settings['__experimentalAdditionalBlockPatterns']          = \WP_Block_Patterns_Registry::get_instance()->get_all_registered( true );
		$custom_settings['__experimentalAdditionalBlockPatternCategories'] = \WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered( true );

		$editor_settings         = get_block_editor_settings( $custom_settings, $block_editor_context );
		$active_global_styles_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
		$active_theme            = get_stylesheet();
		$preload_paths           = array(
			array( '/wp/v2/media', 'OPTIONS' ),
			'/wp/v2/types?context=view',
			'/wp/v2/types/wp_template?context=edit',
			'/wp/v2/types/wp_template-part?context=edit',
			'/wp/v2/templates?context=edit&per_page=-1',
			'/wp/v2/template-parts?context=edit&per_page=-1',
			'/wp/v2/themes?context=edit&status=active',
			'/wp/v2/global-styles/' . $active_global_styles_id . '?context=edit',
			'/wp/v2/global-styles/' . $active_global_styles_id,
			'/wp/v2/global-styles/themes/' . $active_theme,
		);

		block_editor_rest_api_preload( $preload_paths, $block_editor_context );

		wp_add_inline_script(
			'wp-blocks',
			sprintf(
				'window.wcBlockSettings = %s;',
				wp_json_encode( $editor_settings )
			)
		);

		// Preload server-registered block schemas.
		wp_add_inline_script(
			'wp-blocks',
			'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
		);

		wp_add_inline_script(
			'wp-blocks',
			sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( isset( $editor_settings['blockCategories'] ) ? $editor_settings['blockCategories'] : array() ) ),
			'after'
		);

		wp_enqueue_script( 'wp-editor' );
		wp_enqueue_script( 'wp-format-library' ); // Not sure if this is needed.
		wp_enqueue_script( 'wp-router' );
		wp_enqueue_style( 'wp-editor' );
		wp_enqueue_style( 'wp-edit-site' );
		wp_enqueue_style( 'wp-format-library' );
		wp_enqueue_media();

		if (
				current_theme_supports( 'wp-block-styles' ) &&
				( ! is_array( $editor_styles ) || count( $editor_styles ) === 0 )
			) {
			wp_enqueue_style( 'wp-block-library-theme' );
		}
		/** This action is documented in wp-admin/edit-form-blocks.php
		 *
		 * @since 8.0.3
		*/
		do_action( 'enqueue_block_editor_assets' );
	}

	/**
	 * Appends a small style to hide admin bar
	 *
	 * @param bool $show Whether to show the admin bar.
	 */
	public function possibly_hide_wp_admin_bar( $show ) {
		if ( isset( $_GET['cys-hide-admin-bar'] ) ) { // @phpcs:ignore
			return false;
		}
		return $show;
	}

	/**
	 * Runs script and add styles to remove unwanted elements and hide scrollbar
	 * when users are viewing with ?cys-hide-admin-bar=true.
	 *
	 * @return void
	 */
	public function possibly_remove_unwanted_ui_elements() {
		if ( isset( $_GET['cys-hide-admin-bar'] ) ) { // @phpcs:ignore
			echo '
			<style type="text/css">
				body { overflow: hidden; }
			</style>';
		}
	}

	/**
	 * Checks if the post has custom global styles stored (if it is different from the default global styles).
	 *
	 * @param WP_Post $post The post object.
	 * @return bool
	 */
	private function has_custom_global_styles( WP_Post $post ) {
		$required_keys = array( 'version', 'isGlobalStylesUserThemeJSON' );

		$json_post_content = json_decode( $post->post_content, true );
		if ( is_null( $json_post_content ) ) {
			return false;
		}

		$post_content_keys = array_keys( $json_post_content );

		return ! empty( array_diff( $post_content_keys, $required_keys ) ) || ! empty( array_diff( $required_keys, $post_content_keys ) );
	}

	/**
	 * Checks if the post is a template or a template part.
	 *
	 * @param WP_Post $post The post object.
	 * @return bool Whether the post is a template or a template part.
	 */
	private function has_custom_template( WP_Post $post ) {
		return in_array( $post->post_type, array( 'wp_template', 'wp_template_part' ), true );
	}
}
