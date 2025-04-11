<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\Utils\Utils;
use Automattic\WooCommerce\Blocks\Utils\MiniCartUtils;
use Automattic\WooCommerce\Blocks\Utils\BlockHooksTrait;

/**
 * Mini-Cart class.
 *
 * @internal
 */
class MiniCart extends AbstractBlock {
	use BlockHooksTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart';

	/**
	 * Chunks build folder.
	 *
	 * @var string
	 */
	protected $chunks_folder = 'mini-cart-contents-block';

	/**
	 * Array of scripts that will be lazy loaded when interacting with the block.
	 *
	 * @var string[]
	 */
	protected $scripts_to_lazy_load = array();

	/**
	 *  Inc Tax label.
	 *
	 * @var string
	 */
	protected $tax_label = '';

	/**
	 *  Visibility of price including tax.
	 *
	 * @var string
	 */
	protected $display_cart_prices_including_tax = false;

	/**
	 * Block Hook API placements.
	 *
	 * @var array
	 */
	protected $hooked_block_placements = array(
		array(
			'position' => 'after',
			'anchor'   => 'core/navigation',
			'area'     => 'header',
			'version'  => '8.4.0',
		),
	);

	/**
	 * Constructor.
	 *
	 * @param AssetApi            $asset_api Instance of the asset API.
	 * @param AssetDataRegistry   $asset_data_registry Instance of the asset data registry.
	 * @param IntegrationRegistry $integration_registry Instance of the integration registry.
	 */
	public function __construct( AssetApi $asset_api, AssetDataRegistry $asset_data_registry, IntegrationRegistry $integration_registry ) {
		parent::__construct( $asset_api, $asset_data_registry, $integration_registry, $this->block_name );
	}

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();
		add_action( 'wp_loaded', array( $this, 'register_empty_cart_message_block_pattern' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_lazy_load_scripts' ), 2 );
		add_filter( 'hooked_block_woocommerce/mini-cart', array( $this, 'modify_hooked_block_attributes' ), 10, 5 );
		add_filter( 'hooked_block_types', array( $this, 'register_hooked_block' ), 9, 4 );
	}

	/**
	 * Callback for the Block Hooks API to modify the attributes of the hooked block.
	 *
	 * @param array|null                      $parsed_hooked_block The parsed block array for the given hooked block type, or null to suppress the block.
	 * @param string                          $hooked_block_type   The hooked block type name.
	 * @param string                          $relative_position   The relative position of the hooked block.
	 * @param array                           $parsed_anchor_block The anchor block, in parsed block array format.
	 * @param WP_Block_Template|WP_Post|array $context             The block template, template part, `wp_navigation` post type,
	 *                                                             or pattern that the anchor block belongs to.
	 * @return array|null
	 */
	public function modify_hooked_block_attributes( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context ) {
		$mini_cart_block_font_size = wp_get_global_styles( array( 'blocks', 'woocommerce/mini-cart', 'typography', 'fontSize' ) );

		if ( ! is_string( $mini_cart_block_font_size ) ) {
			$navigation_block_font_size = wp_get_global_styles( array( 'blocks', 'core/navigation', 'typography', 'fontSize' ) );

			if ( is_string( $navigation_block_font_size ) ) {
				$parsed_hooked_block['attrs']['style']['typography']['fontSize'] = $navigation_block_font_size;
			}
		}

		return $parsed_hooked_block;
	}

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string;
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => [ 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_script( $key = null ) {
		if ( is_cart() || is_checkout() ) {
			return;
		}

		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name . '-frontend' ),
			'dependencies' => [],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]
	 */
	protected function get_block_type_style() {
		return array_merge( parent::get_block_type_style(), [ 'wc-blocks-packages-style' ] );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		if ( is_cart() || is_checkout() ) {
			return;
		}

		parent::enqueue_data( $attributes );

		// Hydrate the following data depending on admin or frontend context.
		if ( ! is_admin() && ! WC()->is_rest_api_request() ) {
			$label_info = $this->get_tax_label();

			$this->tax_label                         = $label_info['tax_label'];
			$this->display_cart_prices_including_tax = $label_info['display_cart_prices_including_tax'];

			$this->asset_data_registry->add(
				'taxLabel',
				$this->tax_label
			);
		}

		$this->asset_data_registry->add(
			'displayCartPricesIncludingTax',
			$this->display_cart_prices_including_tax
		);

		$template_part_edit_uri = '';

		if (
			current_user_can( 'edit_theme_options' ) &&
			( wp_is_block_theme() || current_theme_supports( 'block-template-parts' ) )
		) {
			$theme_slug = BlockTemplateUtils::theme_has_template_part( 'mini-cart' ) ? wp_get_theme()->get_stylesheet() : BlockTemplateUtils::PLUGIN_SLUG;

			if ( version_compare( get_bloginfo( 'version' ), '5.9', '<' ) ) {
				$site_editor_uri = add_query_arg(
					array( 'page' => 'gutenberg-edit-site' ),
					admin_url( 'themes.php' )
				);
			} else {
				$site_editor_uri = add_query_arg(
					array(
						'canvas' => 'edit',
						'path'   => '/template-parts/single',
					),
					admin_url( 'site-editor.php' )
				);
			}

			$template_part_edit_uri = esc_url_raw(
				add_query_arg(
					array(
						'postId'   => sprintf( '%s//%s', $theme_slug, 'mini-cart' ),
						'postType' => 'wp_template_part',
					),
					$site_editor_uri
				)
			);
		}

		$this->asset_data_registry->add(
			'templatePartEditUri',
			$template_part_edit_uri
		);

		/**
		 * Fires after cart block data is registered.
		 *
		 * @since 5.8.0
		 */
		do_action( 'woocommerce_blocks_cart_enqueue_data' );
	}

	/**
	 * Prints the variable containing information about the scripts to lazy load.
	 */
	public function print_lazy_load_scripts() {
		$script_data = $this->asset_api->get_script_data( 'assets/client/blocks/mini-cart-component-frontend.js' );

		$num_dependencies = is_countable( $script_data['dependencies'] ) ? count( $script_data['dependencies'] ) : 0;
		$wp_scripts       = wp_scripts();

		for ( $i = 0; $i < $num_dependencies; $i++ ) {
			$dependency = $script_data['dependencies'][ $i ];

			foreach ( $wp_scripts->registered as $script ) {
				if ( $script->handle === $dependency ) {
					$this->append_script_and_deps_src( $script );
					break;
				}
			}
		}

		$payment_method_registry = Package::container()->get( PaymentMethodRegistry::class );
		$payment_methods         = $payment_method_registry->get_all_active_payment_method_script_dependencies();

		foreach ( $payment_methods as $payment_method ) {
			$payment_method_script = $this->get_script_from_handle( $payment_method );

			if ( ! is_null( $payment_method_script ) ) {
				$this->append_script_and_deps_src( $payment_method_script );
			}
		}

		$this->scripts_to_lazy_load['wc-block-mini-cart-component-frontend'] = array(
			'src'          => $script_data['src'],
			'version'      => $script_data['version'],
			'translations' => $this->get_inner_blocks_translations(),
		);

		$inner_blocks_frontend_scripts = array();
		$cart                          = $this->get_cart_instance();
		if ( $cart ) {
			// Preload inner blocks frontend scripts.
			$inner_blocks_frontend_scripts = $cart->is_empty() ? array(
				'empty-cart-frontend',
				'filled-cart-frontend',
				'shopping-button-frontend',
			) : array(
				'empty-cart-frontend',
				'filled-cart-frontend',
				'title-frontend',
				'items-frontend',
				'footer-frontend',
				'products-table-frontend',
				'cart-button-frontend',
				'checkout-button-frontend',
				'title-label-frontend',
				'title-items-counter-frontend',
			);
		}
		foreach ( $inner_blocks_frontend_scripts as $inner_block_frontend_script ) {
			$script_data = $this->asset_api->get_script_data( 'assets/client/blocks/mini-cart-contents-block/' . $inner_block_frontend_script . '.js' );
			$this->scripts_to_lazy_load[ 'wc-block-' . $inner_block_frontend_script ] = array(
				'src'     => $script_data['src'],
				'version' => $script_data['version'],
			);
		}

		$data                          = rawurlencode( wp_json_encode( $this->scripts_to_lazy_load ) );
		$mini_cart_dependencies_script = "var wcBlocksMiniCartFrontendDependencies = JSON.parse( decodeURIComponent( '" . esc_js( $data ) . "' ) );";

		wp_add_inline_script(
			'wc-mini-cart-block-frontend',
			$mini_cart_dependencies_script,
			'before'
		);
	}

	/**
	 * Returns the script data given its handle.
	 *
	 * @param string $handle Handle of the script.
	 *
	 * @return \_WP_Dependency|null Object containing the script data if found, or null.
	 */
	protected function get_script_from_handle( $handle ) {
		$wp_scripts = wp_scripts();
		foreach ( $wp_scripts->registered as $script ) {
			if ( $script->handle === $handle ) {
				return $script;
			}
		}
		return null;
	}

	/**
	 * Recursively appends a scripts and its dependencies into the scripts_to_lazy_load array.
	 *
	 * @param \_WP_Dependency $script Object containing script data.
	 */
	protected function append_script_and_deps_src( $script ) {
		$wp_scripts = wp_scripts();

		// This script and its dependencies have already been appended.
		if ( ! $script || array_key_exists( $script->handle, $this->scripts_to_lazy_load ) || wp_script_is( $script->handle, 'enqueued' ) ) {
			return;
		}

		if ( is_countable( $script->deps ) && count( $script->deps ) ) {
			foreach ( $script->deps as $dep ) {
				if ( ! array_key_exists( $dep, $this->scripts_to_lazy_load ) ) {
					$dep_script = $this->get_script_from_handle( $dep );

					if ( ! is_null( $dep_script ) ) {
						$this->append_script_and_deps_src( $dep_script );
					}
				}
			}
		}
		if ( ! $script->src ) {
			return;
		}

		$site_url = site_url() ?? wp_guess_url();

		if ( Utils::wp_version_compare( '6.3', '>=' ) ) {
			$script_before = $wp_scripts->get_inline_script_data( $script->handle, 'before' );
			$script_after  = $wp_scripts->get_inline_script_data( $script->handle, 'after' );
		} else {
			$script_before = $wp_scripts->print_inline_script( $script->handle, 'before', false );
			$script_after  = $wp_scripts->print_inline_script( $script->handle, 'after', false );
		}

		$this->scripts_to_lazy_load[ $script->handle ] = array(
			'src'          => preg_match( '|^(https?:)?//|', $script->src ) ? $script->src : $site_url . $script->src,
			'version'      => $script->ver,
			'before'       => $script_before,
			'after'        => $script_after,
			'translations' => $wp_scripts->print_translations( $script->handle, false ),
		);
	}

	/**
	 * Returns the markup for the cart price.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	protected function get_cart_price_markup( $attributes ) {
		if ( isset( $attributes['hasHiddenPrice'] ) && false !== $attributes['hasHiddenPrice'] ) {
			return;
		}
		$price_color = isset( $attributes['priceColor']['color'] ) ? $attributes['priceColor']['color'] : '';

		return '<span class="wc-block-mini-cart__amount" style="color:' . esc_attr( $price_color ) . '"></span>' . $this->get_include_tax_label_markup( $attributes );
	}

	/**
	 * Returns the markup for render the tax label.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	protected function get_include_tax_label_markup( $attributes ) {
		if ( empty( $this->tax_label ) ) {
			return '';
		}
		$price_color = isset( $attributes['priceColor']['color'] ) ? $attributes['priceColor']['color'] : '';

		return '<small class="wc-block-mini-cart__tax-label" style="color:' . esc_attr( $price_color ) . ' " hidden>' . esc_html( $this->tax_label ) . '</small>';
	}

	/**
	 * Append frontend scripts when rendering the Mini-Cart block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		return $content . $this->get_markup( MiniCartUtils::migrate_attributes_to_color_panel( $attributes ) );
	}

	/**
	 * Render the markup for the Mini-Cart block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string The HTML markup.
	 */
	protected function get_markup( $attributes ) {
		if ( is_admin() || WC()->is_rest_api_request() ) {
			// In the editor we will display the placeholder, so no need to load
			// real cart data and to print the markup.
			return '';
		}

		$classes_styles  = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array( 'text_color', 'background_color', 'font_size', 'font_weight', 'font_family', 'extra_classes' ) );
		$wrapper_classes = sprintf( 'wc-block-mini-cart wp-block-woocommerce-mini-cart %s', $classes_styles['classes'] );
		$wrapper_styles  = $classes_styles['styles'];

		$icon_color          = isset( $attributes['iconColor']['color'] ) ? esc_attr( $attributes['iconColor']['color'] ) : 'currentColor';
		$product_count_color = isset( $attributes['productCountColor']['color'] ) ? esc_attr( $attributes['productCountColor']['color'] ) : '';
		$styles              = $product_count_color ? 'background:' . $product_count_color : '';
		$icon                = MiniCartUtils::get_svg_icon( $attributes['miniCartIcon'] ?? '', $icon_color );

		$product_count_visibility = isset( $attributes['productCountVisibility'] ) ? $attributes['productCountVisibility'] : 'greater_than_zero';

		$button_html = '<span class="wc-block-mini-cart__quantity-badge">
			' . $icon . '
			' . ( 'never' !== $product_count_visibility ? '<span class="wc-block-mini-cart__badge" style="' . esc_attr( $styles ) . '"></span>' : '' ) . '
		</span>
		' . $this->get_cart_price_markup( $attributes );

		if ( is_cart() || is_checkout() ) {
			if ( $this->should_not_render_mini_cart( $attributes ) ) {
				return '';
			}

			// It is not necessary to load the Mini-Cart Block on Cart and Checkout page.
			return '<div class="' . esc_attr( $wrapper_classes ) . '" style="visibility:hidden" aria-hidden="true">
				<button class="wc-block-mini-cart__button" disabled aria-label="' . __( 'Cart', 'woocommerce' ) . '">' . $button_html . '</button>
			</div>';
		}

		$template_part_contents = '';

		// Determine if we need to load the template part from the DB, the theme or WooCommerce in that order.
		$templates_from_db = BlockTemplateUtils::get_block_templates_from_db( array( 'mini-cart' ), 'wp_template_part' );
		if ( is_countable( $templates_from_db ) && count( $templates_from_db ) > 0 ) {
			$template_slug_to_load = $templates_from_db[0]->theme;
		} else {
			$theme_has_mini_cart   = BlockTemplateUtils::theme_has_template_part( 'mini-cart' );
			$template_slug_to_load = $theme_has_mini_cart ? get_stylesheet() : BlockTemplateUtils::PLUGIN_SLUG;
		}
		$template_part = get_block_template( $template_slug_to_load . '//mini-cart', 'wp_template_part' );

		if ( $template_part && ! empty( $template_part->content ) ) {
			$template_part_contents = do_blocks( $template_part->content );
		}

		if ( '' === $template_part_contents ) {
			$template_part_contents = do_blocks(
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				file_get_contents( Package::get_path() . 'templates/' . BlockTemplateUtils::DIRECTORY_NAMES['TEMPLATE_PARTS'] . '/mini-cart.html' )
			);
		}

		return '<div class="' . esc_attr( $wrapper_classes ) . '" style="' . esc_attr( $wrapper_styles ) . '">
			<button class="wc-block-mini-cart__button" aria-label="' . __( 'Cart', 'woocommerce' ) . '">' . $button_html . '</button>
			<div class="is-loading wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--is-hidden" aria-hidden="true">
				<div class="wc-block-mini-cart__drawer wc-block-components-drawer">
					<div class="wc-block-components-drawer__content">
						<div class="wc-block-mini-cart__template-part">'
						. wp_kses_post( $template_part_contents ) .
						'</div>
					</div>
				</div>
			</div>
		</div>';
	}

	/**
	 * Return the main instance of WC_Cart class.
	 *
	 * @return \WC_Cart CartController class instance.
	 */
	protected function get_cart_instance() {
		$cart = WC()->cart;

		if ( $cart && $cart instanceof \WC_Cart ) {
			return $cart;
		}

		return null;
	}

	/**
	 * Get array with data for handle the tax label.
	 * the entire logic of this function is was taken from:
	 * https://github.com/woocommerce/woocommerce/blob/e730f7463c25b50258e97bf56e31e9d7d3bc7ae7/includes/class-wc-cart.php#L1582
	 *
	 * @return array;
	 */
	protected function get_tax_label() {
		$cart = $this->get_cart_instance();

		if ( $cart && $cart->display_prices_including_tax() ) {
			if ( ! wc_prices_include_tax() ) {
				$tax_label                         = WC()->countries->inc_tax_or_vat();
				$display_cart_prices_including_tax = true;
				return array(
					'tax_label'                         => $tax_label,
					'display_cart_prices_including_tax' => $display_cart_prices_including_tax,
				);
			}
			return array(
				'tax_label'                         => '',
				'display_cart_prices_including_tax' => true,
			);
		}

		if ( wc_prices_include_tax() ) {
			$tax_label = WC()->countries->ex_tax_or_vat();
			return array(
				'tax_label'                         => $tax_label,
				'display_cart_prices_including_tax' => false,
			);
		}

		return array(
			'tax_label'                         => '',
			'display_cart_prices_including_tax' => false,
		);
	}

	/**
	 * Prepare translations for inner blocks and dependencies.
	 */
	protected function get_inner_blocks_translations() {
		$wp_scripts   = wp_scripts();
		$translations = array();

		$chunks        = $this->get_chunks_paths( $this->chunks_folder );
		$vendor_chunks = $this->get_chunks_paths( 'vendors--mini-cart-contents-block' );
		$shared_chunks = [ 'cart-blocks/cart-line-items--mini-cart-contents-block/products-table-frontend' ];

		foreach ( array_merge( $chunks, $vendor_chunks, $shared_chunks ) as $chunk ) {
			$handle = 'wc-blocks-' . $chunk . '-chunk';
			$this->asset_api->register_script( $handle, $this->asset_api->get_block_asset_build_path( $chunk ), [], true );
			$translations[] = $wp_scripts->print_translations( $handle, false );
			wp_deregister_script( $handle );
		}

		$translations = array_filter( $translations );

		return implode( '', $translations );
	}

	/**
	 * Register block pattern for Empty Cart Message to make it translatable.
	 */
	public function register_empty_cart_message_block_pattern() {
		register_block_pattern(
			'woocommerce/mini-cart-empty-cart-message',
			array(
				'title'    => __( 'Empty Mini-Cart Message', 'woocommerce' ),
				'inserter' => false,
				'content'  => '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>' . __( 'Your cart is currently empty!', 'woocommerce' ) . '</strong></p><!-- /wp:paragraph -->',
			)
		);
	}

	/**
	 * Returns whether the Mini-Cart should be rendered or not.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return bool
	 */
	public function should_not_render_mini_cart( array $attributes ) {
		return isset( $attributes['cartAndCheckoutRenderStyle'] ) && 'hidden' !== $attributes['cartAndCheckoutRenderStyle'];
	}
}
