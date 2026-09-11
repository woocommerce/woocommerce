<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\SharedStores\ProductsStore;
use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplateCompatibility;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * SingleProductTemplate class.
 *
 * @internal
 */
class SingleProductTemplate extends AbstractTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'single-product';

	/**
	 * Initialization method.
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'render_block_template' ) );
		add_filter( 'get_block_templates', array( $this, 'update_single_product_content' ), 11, 1 );
	}

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Single Product', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Displays a single product.', 'woocommerce' );
	}

	/**
	 * Run template-specific logic when the query matches this template.
	 */
	public function render_block_template() {
		if ( ! is_embed() && is_singular( 'product' ) ) {
			global $post;

			$compatibility_layer = new SingleProductTemplateCompatibility();
			$compatibility_layer->init();

			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$consent = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

				// Load the product data into the products store so derived
				// state closures can resolve it during server-side rendering.
				ProductsStore::load_product( $consent, $product->get_id() );

				// Set the current product context. The derived state
				// closures (mainProductInContext, productVariationInContext, productInContext)
				// are registered by ProductsStore::register_state().
				wp_interactivity_state(
					'woocommerce/products',
					array(
						'productId'   => $product->get_id(),
						'variationId' => null,
					)
				);
			}
		}
	}

	/**
	 * Add the block template objects to be used.
	 *
	 * @param array $query_result Array of template objects.
	 * @return array
	 */
	public function update_single_product_content( $query_result ) {
		$query_result = array_map(
			function ( $template ) {
				if ( str_contains( $template->slug, self::SLUG ) ) {
					// We don't want to add the compatibility layer on the Editor Side.
					// The second condition is necessary to not apply the compatibility layer on the REST API. Gutenberg uses the REST API to clone the template.
					// More details: https://github.com/woocommerce/woocommerce-blocks/issues/9662.
					if ( ( ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) && ! BlockTemplateUtils::template_has_legacy_template_block( $template ) ) {
						// Add the product class to the body. We should move this to a more appropriate place.
						add_filter(
							'body_class',
							function ( $classes ) {
								return array_merge( $classes, wc_get_product_class() );
							}
						);

						global $product;

						if ( ! $product instanceof \WC_Product ) {
							$product_id = get_the_ID();
							if ( $product_id ) {
								wc_setup_product_data( $product_id );
							}
						}

						if ( post_password_required() ) {
							$template->content = $this->add_password_form( $template->content );
						} else {
							$template->content = SingleProductTemplateCompatibility::add_compatibility_layer( $template->content );
						}
					}
				}
				return $template;
			},
			$query_result
		);

		return $query_result;
	}

	/**
	 * Replace the first single product template block with the password form. Remove all other single product template blocks.
	 *
	 * @param array $parsed_blocks Array of parsed block objects.
	 * @return array Parsed blocks.
	 */
	private static function replace_first_single_product_template_block_with_password_form( $parsed_blocks ) {
		$blocks              = array();
		$is_already_replaced = false;

		foreach ( $parsed_blocks as $block ) {
			$processed           = self::process_block_for_password_form( $block, $is_already_replaced );
			$is_already_replaced = $processed['is_already_replaced'];
			$blocks              = array_merge( $blocks, $processed['blocks'] );
		}

		return $blocks;
	}

	/**
	 * Replace or remove a parsed block when rendering a password-protected product.
	 *
	 * @param array $block               Parsed block.
	 * @param bool  $is_already_replaced If the password form has already been added.
	 * @return array{blocks: array, is_already_replaced: bool} Replacement blocks (empty when removed).
	 */
	private static function process_block_for_password_form( $block, $is_already_replaced ) {
		if ( self::is_password_protected_replacement_block( $block ) ) {
			if ( $is_already_replaced ) {
				return array(
					'blocks'              => array(),
					'is_already_replaced' => true,
				);
			}

			return array(
				'blocks'              => array( parse_blocks( '<!-- wp:html -->' . get_the_password_form() . '<!-- /wp:html -->' )[0] ),
				'is_already_replaced' => true,
			);
		}

		if ( empty( $block['innerBlocks'] ) || ! isset( $block['innerContent'] ) || ! is_array( $block['innerContent'] ) ) {
			return array(
				'blocks'              => array( $block ),
				'is_already_replaced' => $is_already_replaced,
			);
		}

		$new_inner_blocks  = array();
		$new_inner_content = array();
		$inner_block_index = 0;

		foreach ( $block['innerContent'] as $chunk ) {
			if ( is_string( $chunk ) ) {
				$new_inner_content[] = $chunk;
				continue;
			}

			if ( ! isset( $block['innerBlocks'][ $inner_block_index ] ) ) {
				continue;
			}

			$processed           = self::process_block_for_password_form( $block['innerBlocks'][ $inner_block_index ], $is_already_replaced );
			$is_already_replaced = $processed['is_already_replaced'];
			++$inner_block_index;

			foreach ( $processed['blocks'] as $processed_block ) {
				$new_inner_blocks[]  = $processed_block;
				$new_inner_content[] = null;
			}
		}

		if ( count( $new_inner_blocks ) === 0 ) {
			return array(
				'blocks'              => array(),
				'is_already_replaced' => $is_already_replaced,
			);
		}

		$block['innerBlocks']  = $new_inner_blocks;
		$block['innerContent'] = $new_inner_content;

		return array(
			'blocks'              => array( $block ),
			'is_already_replaced' => $is_already_replaced,
		);
	}

	/**
	 * Whether this block should be replaced with the password form (or removed after the first replacement).
	 *
	 * This list is not every block that can appear on a single product template. Blocks such as breadcrumbs
	 * stay visible on password-protected products.
	 *
	 * @param array $block Parsed block.
	 * @return bool
	 */
	private static function is_password_protected_replacement_block( $block ) {
		$block_name = $block['blockName'] ?? '';

		$single_product_template_blocks = array(
			'woocommerce/product-image-gallery',
			'woocommerce/product-details',
			'woocommerce/add-to-cart-form',
			'woocommerce/product-meta',
			'woocommerce/product-rating',
			'woocommerce/product-price',
			'woocommerce/product-summary',
			'woocommerce/related-products',
			'woocommerce/add-to-cart-with-options',
			'woocommerce/product-gallery',
			'woocommerce/product-collection',
			'core/post-title',
			'core/post-excerpt',
		);

		if ( in_array( $block_name, $single_product_template_blocks, true ) ) {
			return true;
		}

		if (
			'core/pattern' !== $block_name ||
			! isset( $block['attrs']['slug'] )
		) {
			return false;
		}

		$pattern = \WP_Block_Patterns_Registry::get_instance()->get_registered( $block['attrs']['slug'] );

		if ( empty( $pattern['content'] ) ) {
			return false;
		}

		return BlockTemplateUtils::has_block_including_patterns(
			$single_product_template_blocks,
			parse_blocks( $pattern['content'] )
		);
	}

	/**
	 * Add password form to the Single Product Template.
	 *
	 * @param string $content The content of the template.
	 * @return string
	 */
	public static function add_password_form( $content ) {
		$parsed_blocks     = parse_blocks( $content );
		$serialized_blocks = serialize_blocks( self::replace_first_single_product_template_block_with_password_form( $parsed_blocks ) );

		return $serialized_blocks;
	}
}
