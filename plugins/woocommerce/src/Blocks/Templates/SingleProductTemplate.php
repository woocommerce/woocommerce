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

			$valid_slugs         = array( self::SLUG );
			$single_product_slug = 'product' === $post->post_type && $post->post_name ? 'single-product-' . $post->post_name : '';
			if ( $single_product_slug ) {
				$valid_slugs[] = 'single-product-' . $post->post_name;
			}
			$templates = get_block_templates( array( 'slug__in' => $valid_slugs ) );

			if ( count( $templates ) === 0 ) {
				return;
			}

			// Use the first template by default.
			$template = reset( $templates );

			// Check if there is a template matching the slug `single-product-{post_name}`.
			if ( count( $valid_slugs ) > 1 && count( $templates ) > 1 ) {
				foreach ( $templates as $t ) {
					if ( $single_product_slug === $t->slug ) {
						$template = $t;
						break;
					}
				}
			}

			if ( isset( $template ) && BlockTemplateUtils::template_has_legacy_template_block( $template ) ) {
				add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
			}

			$product = wc_get_product( $post->ID );
			if ( $product ) {
				$consent = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

				// Load the product data into the products store so derived
				// state closures can resolve it during server-side rendering.
				ProductsStore::load_product( $consent, $product->get_id() );

				// Keep the existing product-data store hydrated so current
				// consumers (add-to-cart-with-options, variation-selector)
				// continue to work. This will be removed when consumers are
				// migrated to product-context.
				wp_interactivity_state(
					'woocommerce/product-data',
					array(
						'templateState' => array(
							'productId'   => $product->get_id(),
							'variationId' => null,
						),
					)
				);

				// Register the product-context store state. The derived state
				// closures (product, selectedVariation) mirror the JS getters
				// so that directives referencing state.product resolve during
				// SSR. If more call sites need to register these closures,
				// consider extracting them into a shared helper.
				wp_interactivity_state(
					'woocommerce/product-context',
					array(
						'productId'         => $product->get_id(),
						'variationId'       => null,
						'product'           => function () {
							$context    = wp_interactivity_get_context();
							$state      = wp_interactivity_state( 'woocommerce/product-context' );
							$product_id = ! empty( $context ) ? $context['productId'] : ( $state['productId'] ?? null );

							if ( ! $product_id ) {
								return null;
							}

							$products_state = wp_interactivity_state( 'woocommerce/products' );
							return $products_state['products'][ $product_id ] ?? null;
						},
						'selectedVariation' => function () {
							$context      = wp_interactivity_get_context();
							$state        = wp_interactivity_state( 'woocommerce/product-context' );
							$variation_id = ! empty( $context ) ? $context['variationId'] : ( $state['variationId'] ?? null );

							if ( ! $variation_id ) {
								return null;
							}

							$products_state = wp_interactivity_state( 'woocommerce/products' );
							return $products_state['productVariations'][ $variation_id ] ?? null;
						},
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
	 * @param array   $parsed_blocks Array of parsed block objects.
	 * @param boolean $is_already_replaced If the password form has already been added.
	 * @return array Parsed blocks
	 */
	private static function replace_first_single_product_template_block_with_password_form( $parsed_blocks, $is_already_replaced ) {
		// We want to replace the first single product template block with the password form. We also want to remove all other single product template blocks.
		// This array doesn't contains all the blocks. For example, it missing the breadcrumbs blocks: it doesn't make sense replace the breadcrumbs with the password form.
		$single_product_template_blocks = array(
			'woocommerce/product-image-gallery',
			'woocommerce/product-details',
			'woocommerce/add-to-cart-form',
			'woocommerce/product-meta',
			'woocommerce/product-rating',
			'woocommerce/product-price',
			'woocommerce/related-products',
			'woocommerce/add-to-cart-with-options',
			'woocommerce/product-gallery',
			'woocommerce/product-collection',
			'core/post-title',
			'core/post-excerpt',
		);

		return array_reduce(
			$parsed_blocks,
			function ( $carry, $block ) use ( $single_product_template_blocks ) {
				if ( in_array( $block['blockName'], $single_product_template_blocks, true ) || ( 'core/pattern' === $block['blockName'] && isset( $block['attrs']['slug'] ) && 'woocommerce-blocks/related-products' === $block['attrs']['slug'] ) ) {
					if ( $carry['is_already_replaced'] ) {
						return array(
							'blocks'              => $carry['blocks'],
							'html_block'          => null,
							'removed'             => true,
							'is_already_replaced' => true,

						);
					}

					return array(
						'blocks'              => $carry['blocks'],
						'html_block'          => parse_blocks( '<!-- wp:html -->' . get_the_password_form() . '<!-- /wp:html -->' )[0],
						'removed'             => false,
						'is_already_replaced' => $carry['is_already_replaced'],
					);

				}

				if ( isset( $block['innerBlocks'] ) && count( $block['innerBlocks'] ) > 0 ) {
					$index              = 0;
					$new_inner_blocks   = array();
					$new_inner_contents = $block['innerContent'];
					foreach ( $block['innerContent'] as $inner_content ) {
						// Don't process the closing tag of the block.
						if ( count( $block['innerBlocks'] ) === $index ) {
							break;
						}

						$blocks                       = self::replace_first_single_product_template_block_with_password_form( array( $block['innerBlocks'][ $index ] ), $carry['is_already_replaced'] );
						$new_blocks                   = $blocks['blocks'];
						$html_block                   = $blocks['html_block'];
						$is_removed                   = $blocks['removed'];
						$carry['is_already_replaced'] = $blocks['is_already_replaced'];

						if ( isset( $html_block ) ) {
							$new_inner_blocks             = array_merge( $new_inner_blocks, $new_blocks, array( $html_block ) );
							$carry['is_already_replaced'] = true;
						} else {
							$new_inner_blocks = array_merge( $new_inner_blocks, $new_blocks );
						}

						if ( $is_removed ) {
							unset( $new_inner_contents[ $index ] );
							// The last element of the inner contents contains the closing tag of the block. We don't want to remove it.
							if ( $index + 1 < count( $new_inner_contents ) ) {
								unset( $new_inner_contents[ $index + 1 ] );
							}
							$new_inner_contents = array_values( $new_inner_contents );
						}

						$index++;
					}

					$block['innerBlocks']  = $new_inner_blocks;
					$block['innerContent'] = $new_inner_contents;

					if ( count( $new_inner_blocks ) === 0 ) {
						return array(
							'blocks'              => $carry['blocks'],
							'html_block'          => null,
							'removed'             => true,
							'is_already_replaced' => $carry['is_already_replaced'],
						);
					}

					return array(
						'blocks'              => array_merge( $carry['blocks'], array( $block ) ),
						'html_block'          => null,
						'removed'             => false,
						'is_already_replaced' => $carry['is_already_replaced'],
					);
				}

				return array(
					'blocks'              => array_merge( $carry['blocks'], array( $block ) ),
					'html_block'          => null,
					'removed'             => false,
					'is_already_replaced' => $carry['is_already_replaced'],
				);
			},
			array(
				'blocks'              => array(),
				'html_block'          => null,
				'removed'             => false,
				'is_already_replaced' => $is_already_replaced,
			)
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
		$blocks            = self::replace_first_single_product_template_block_with_password_form( $parsed_blocks, false );
		$serialized_blocks = serialize_blocks( $blocks['blocks'] );

		return $serialized_blocks;
	}
}
