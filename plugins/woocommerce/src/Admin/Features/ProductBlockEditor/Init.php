<?php
/**
 * WooCommerce Product Block Editor
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplate;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry;

use Automattic\WooCommerce\Internal\Features\ProductBlockEditor\ProductTemplates\SimpleProductTemplate;
use Automattic\WooCommerce\Internal\Features\ProductBlockEditor\ProductTemplates\ProductVariationTemplate;

use WP_Block_Editor_Context;

/**
 * Loads assets related to the product block editor.
 */
class Init {
	/**
	 * The context name used to identify the editor.
	 */
	const EDITOR_CONTEXT_NAME = 'woocommerce/edit-product';

	/**
	 * Supported product types.
	 *
	 * @var array
	 */
	private $supported_product_types = array( 'simple' );

	/**
	 * Registered product templates.
	 *
	 * @var array
	 */
	private $product_templates = array();

	/**
	 * Redirection controller.
	 *
	 * @var RedirectionController
	 */
	private $redirection_controller;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( Features::is_enabled( 'product-variation-management' ) ) {
			array_push( $this->supported_product_types, 'variable' );
		}

		if ( Features::is_enabled( 'product-external-affiliate' ) ) {
			array_push( $this->supported_product_types, 'external' );
		}

		if ( Features::is_enabled( 'product-grouped' ) ) {
			array_push( $this->supported_product_types, 'grouped' );
		}

		$this->redirection_controller = new RedirectionController();

		if ( \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			if ( ! Features::is_enabled( 'new-product-management-experience' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'dequeue_conflicting_styles' ), 100 );
				add_action( 'get_edit_post_link', array( $this, 'update_edit_product_link' ), 10, 2 );
			}
			add_filter( 'woocommerce_admin_get_user_data_fields', array( $this, 'add_user_data_fields' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'woocommerce_register_post_type_product_variation', array( $this, 'enable_rest_api_for_product_variation' ) );

			add_action( 'current_screen', array( $this, 'set_current_screen_to_block_editor_if_wc_admin' ) );

			add_action( 'rest_api_init', array( $this, 'register_layout_templates' ) );
			add_action( 'rest_api_init', array( $this, 'register_user_metas' ) );

			add_filter( 'register_block_type_args', array( $this, 'register_metadata_attribute' ) );

			// Make sure the block registry is initialized so that core blocks are registered.
			BlockRegistry::get_instance();

			$tracks = new Tracks();
			$tracks->init();

			$this->register_product_templates();
		}
	}

	/**
	 * Enqueue scripts needed for the product form block editor.
	 */
	public function enqueue_scripts() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		$editor_settings = $this->get_product_editor_settings();

		$script_handle = 'wc-admin-edit-product';
		wp_register_script( $script_handle, '', array(), '0.1.0', true );
		wp_enqueue_script( $script_handle );
		wp_add_inline_script(
			$script_handle,
			'var productBlockEditorSettings = productBlockEditorSettings || ' . wp_json_encode( $editor_settings ) . ';',
			'before'
		);
		wp_add_inline_script(
			$script_handle,
			sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( $editor_settings['blockCategories'] ) ),
			'before'
		);
		wp_tinymce_inline_scripts();
		wp_enqueue_media();
	}

	/**
	 * Enqueue styles needed for the rich text editor.
	 */
	public function enqueue_styles() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}
		wp_enqueue_style( 'wp-edit-blocks' );
		wp_enqueue_style( 'wp-format-library' );
		wp_enqueue_editor();
		/**
		 * Enqueue any block editor related assets.
		 *
		 * @since 7.1.0
		*/
		do_action( 'enqueue_block_editor_assets' );
	}

	/**
	 * Dequeue conflicting styles.
	 */
	public function dequeue_conflicting_styles() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}
		// Dequeing this to avoid conflicts, until we remove the 'woocommerce-page' class.
		wp_dequeue_style( 'woocommerce-blocktheme' );
	}

	/**
	 * Update the edit product links when the new experience is enabled.
	 *
	 * @param string $link    The edit link.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function update_edit_product_link( $link, $post_id ) {
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $link;
		}

		if ( $product->get_type() === 'simple' ) {
			return admin_url( 'admin.php?page=wc-admin&path=/product/' . $product->get_id() );
		}

		return $link;
	}

	/**
	 * Enables variation post type in REST API.
	 *
	 * @param array $args Array of post type arguments.
	 * @return array Array of post type arguments.
	 */
	public function enable_rest_api_for_product_variation( $args ) {
		$args['show_in_rest'] = true;

		return $args;
	}

	/**
	 * Adds fields so that we can store user preferences for the variations block.
	 *
	 * @param array $user_data_fields User data fields.
	 * @return array
	 */
	public function add_user_data_fields( $user_data_fields ) {
		return array_merge(
			$user_data_fields,
			array(
				'variable_product_block_tour_shown',
				'local_attributes_notice_dismissed_ids',
				'variable_items_without_price_notice_dismissed',
				'product_advice_card_dismissed',
			)
		);
	}

	/**
	 * Sets the current screen to the block editor if a wc-admin page.
	 */
	public function set_current_screen_to_block_editor_if_wc_admin() {
		$screen = get_current_screen();

		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// (no idea why I need that phpcs:ignore above, but I'm tired trying to re-write this comment to get it to pass)
		// we can't check the 'path' query param because client-side routing is used within wc-admin,
		// so this action handler is only called on the initial page load from the server, which might
		// not be the product edit page (it mostly likely isn't).
		if ( PageController::is_admin_page() ) {
			$screen->is_block_editor( true );

			wp_add_inline_script(
				'wp-blocks',
				'wp.blocks && wp.blocks.unstable__bootstrapServerSideBlockDefinitions && wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
			);
		}
	}

	/**
	 * Get the product editor settings.
	 */
	private function get_product_editor_settings() {
		$editor_settings['productTemplates'] = array_map(
			function ( $product_template ) {
				return $product_template->to_json();
			},
			$this->product_templates
		);

		$block_editor_context = new WP_Block_Editor_Context( array( 'name' => self::EDITOR_CONTEXT_NAME ) );

		return get_block_editor_settings( $editor_settings, $block_editor_context );
	}

	/**
	 * Get default product templates.
	 *
	 * @return array The default templates.
	 */
	private function get_default_product_templates() {
		$templates   = array();
		$templates[] = new ProductTemplate(
			array(
				'id'                 => 'standard-product-template',
				'title'              => __( 'Standard product', 'woocommerce' ),
				'description'        => __( 'A single physical or virtual product, e.g. a t-shirt or an eBook.', 'woocommerce' ),
				'order'              => 10,
				'icon'               => 'shipping',
				'layout_template_id' => 'simple-product',
				'product_data'       => array(
					'type' => 'simple',
				),
			)
		);
		$templates[] = new ProductTemplate(
			array(
				'id'                 => 'grouped-product-template',
				'title'              => __( 'Grouped product', 'woocommerce' ),
				'description'        => __( 'A set of products that go well together, e.g. camera kit.', 'woocommerce' ),
				'order'              => 20,
				'icon'               => 'group',
				'layout_template_id' => 'simple-product',
				'product_data'       => array(
					'type' => 'grouped',
				),
			)
		);
		$templates[] = new ProductTemplate(
			array(
				'id'                 => 'affiliate-product-template',
				'title'              => __( 'Affiliate product', 'woocommerce' ),
				'description'        => __( 'A link to a product sold on a different website, e.g. brand collab.', 'woocommerce' ),
				'order'              => 30,
				'icon'               => 'link',
				'layout_template_id' => 'simple-product',
				'product_data'       => array(
					'type' => 'external',
				),
			)
		);

		return $templates;
	}

	/**
	 * Create default product template by custom product type if it does not have a
	 * template associated yet.
	 *
	 * @param array $templates The registered product templates.
	 * @return array The new templates.
	 */
	private function create_default_product_template_by_custom_product_type( array $templates ) {
		// Getting the product types registered via the classic editor.
		$registered_product_types = wc_get_product_types();

		$custom_product_types = array_filter(
			$registered_product_types,
			function ( $product_type ) {
				return ! in_array( $product_type, $this->supported_product_types, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		$templates_with_product_type = array_filter(
			$templates,
			function ( $template ) {
				$product_data = $template->get_product_data();
				return ! is_null( $product_data ) && array_key_exists( 'type', $product_data );
			}
		);

		$custom_product_types_on_templates = array_map(
			function ( $template ) {
				$product_data = $template->get_product_data();
				return $product_data['type'];
			},
			$templates_with_product_type
		);

		foreach ( $custom_product_types as $product_type => $title ) {
			if ( in_array( $product_type, $custom_product_types_on_templates, true ) ) {
				continue;
			}

			$templates[] = new ProductTemplate(
				array(
					'id'           => $product_type . '-product-template',
					'title'        => $title,
					'product_data' => array(
						'type' => $product_type,
					),
				)
			);
		}

		return $templates;
	}

	/**
	 * Register layout templates.
	 */
	public function register_layout_templates() {
		$layout_template_registry = wc_get_container()->get( LayoutTemplateRegistry::class );

		if ( ! $layout_template_registry->is_registered( 'simple-product' ) ) {
			$layout_template_registry->register(
				'simple-product',
				'product-form',
				SimpleProductTemplate::class
			);
		}

		if ( ! $layout_template_registry->is_registered( 'product-variation' ) ) {
			$layout_template_registry->register(
				'product-variation',
				'product-form',
				ProductVariationTemplate::class
			);
		}
	}

	/**
	 * Register product templates.
	 */
	public function register_product_templates() {
		/**
		 * Allows for new product template registration.
		 *
		 * @since 8.5.0
		 */
		$this->product_templates = apply_filters( 'woocommerce_product_editor_product_templates', $this->get_default_product_templates() );
		$this->product_templates = $this->create_default_product_template_by_custom_product_type( $this->product_templates );

		usort(
			$this->product_templates,
			function ( $a, $b ) {
				return $a->get_order() - $b->get_order();
			}
		);

		$this->redirection_controller->set_product_templates( $this->product_templates );
	}

	/**
	 * Register user metas.
	 */
	public function register_user_metas() {
		register_rest_field(
			'user',
			'metaboxhidden_product',
			array(
				'get_callback'    => function ( $object, $attr ) {
					$hidden = get_user_meta( $object['id'], $attr, true );

					if ( is_array( $hidden ) ) {
						// Ensures to always return a string array.
						return array_values( $hidden );
					}

					return array( 'postcustom' );
				},
				'update_callback' => function ( $value, $object, $attr ) {
					// Update the field/meta value.
					update_user_meta( $object->ID, $attr, $value );
				},
				'schema'          => array(
					'type'        => 'array',
					'description' => __( 'The metaboxhidden_product meta from the user metas.', 'woocommerce' ),
					'items'       => array(
						'type' => 'string',
					),
					'arg_options' => array(
						'sanitize_callback' => 'wp_parse_list',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			)
		);
	}

	/**
	 * Registers the metadata block attribute for all block types.
	 * This is a fallback/temporary solution until
	 * the Gutenberg core version registers the metadata attribute.
	 *
	 * @see https://github.com/WordPress/gutenberg/blob/6aaa3686ae67adc1a6a6b08096d3312859733e1b/lib/compat/wordpress-6.5/blocks.php#L27-L47
	 * To do: Remove this method once the Gutenberg core version registers the metadata attribute.
	 *
	 * @param array $args Array of arguments for registering a block type.
	 * @return array $args
	 */
	public function register_metadata_attribute( $args ) {
		// Setup attributes if needed.
		if ( ! isset( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
			$args['attributes'] = array();
		}

		// Add metadata attribute if it doesn't exist.
		if ( ! array_key_exists( 'metadata', $args['attributes'] ) ) {
			$args['attributes']['metadata'] = array(
				'type' => 'object',
			);
		}

		return $args;
	}
}
