<?php
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

/**
 * Mini-Cart class.
 *
 * @internal
 */
class MiniCart extends AbstractBlock {
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
		add_action( 'hooked_block_types', array( $this, 'register_auto_insert' ), 10, 4 );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_lazy_load_scripts' ), 2 );
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
				$this->tax_label,
				''
			);
		}

		$this->asset_data_registry->add(
			'displayCartPricesIncludingTax',
			$this->display_cart_prices_including_tax,
			true
		);

		$template_part_edit_uri = '';

		if (
			current_user_can( 'edit_theme_options' ) &&
			( wc_current_theme_is_fse_theme() || current_theme_supports( 'block-template-parts' ) )
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
			$template_part_edit_uri,
			''
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
		$script_data = $this->asset_api->get_script_data( 'build/mini-cart-component-frontend.js' );

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
			$script_data = $this->asset_api->get_script_data( 'build/mini-cart-contents-block/' . $inner_block_frontend_script . '.js' );
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
		$price_color = array_key_exists( 'priceColor', $attributes ) ? $attributes['priceColor']['color'] : '';

		return '<span class="wc-block-mini-cart__amount" style="color:' . esc_attr( $price_color ) . ' "></span>' . $this->get_include_tax_label_markup( $attributes );
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
		$price_color = array_key_exists( 'priceColor', $attributes ) ? $attributes['priceColor']['color'] : '';

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

		$classes_styles  = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array( 'text_color', 'background_color', 'font_size', 'font_weight', 'font_family' ) );
		$wrapper_classes = sprintf( 'wc-block-mini-cart wp-block-woocommerce-mini-cart %s', $classes_styles['classes'] );
		if ( ! empty( $attributes['className'] ) ) {
			$wrapper_classes .= ' ' . $attributes['className'];
		}
		$wrapper_styles = $classes_styles['styles'];

		$icon_color          = array_key_exists( 'iconColor', $attributes ) ? esc_attr( $attributes['iconColor']['color'] ) : 'currentColor';
		$product_count_color = array_key_exists( 'productCountColor', $attributes ) ? esc_attr( $attributes['productCountColor']['color'] ) : '';

		// Default "Cart" icon.
		$icon = '<svg class="wc-block-mini-cart__icon" width="32" height="32" viewBox="0 0 32 32" fill="' . $icon_color . '" xmlns="http://www.w3.org/2000/svg">
					<circle cx="12.6667" cy="24.6667" r="2" fill="' . $icon_color . '"/>
					<circle cx="23.3333" cy="24.6667" r="2" fill="' . $icon_color . '"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M9.28491 10.0356C9.47481 9.80216 9.75971 9.66667 10.0606 9.66667H25.3333C25.6232 9.66667 25.8989 9.79247 26.0888 10.0115C26.2787 10.2305 26.3643 10.5211 26.3233 10.8081L24.99 20.1414C24.9196 20.6341 24.4977 21 24 21H12C11.5261 21 11.1173 20.6674 11.0209 20.2034L9.08153 10.8701C9.02031 10.5755 9.09501 10.269 9.28491 10.0356ZM11.2898 11.6667L12.8136 19H23.1327L24.1803 11.6667H11.2898Z" fill="' . $icon_color . '"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M5.66669 6.66667C5.66669 6.11438 6.1144 5.66667 6.66669 5.66667H9.33335C9.81664 5.66667 10.2308 6.01229 10.3172 6.48778L11.0445 10.4878C11.1433 11.0312 10.7829 11.5517 10.2395 11.6505C9.69614 11.7493 9.17555 11.3889 9.07676 10.8456L8.49878 7.66667H6.66669C6.1144 7.66667 5.66669 7.21895 5.66669 6.66667Z" fill="' . $icon_color . '"/>
				</svg>';

		if ( isset( $attributes['miniCartIcon'] ) ) {
			if ( 'bag' === $attributes['miniCartIcon'] ) {
				$icon = '<svg class="wc-block-mini-cart__icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M12.4444 14.2222C12.9354 14.2222 13.3333 14.6202 13.3333 15.1111C13.3333 15.8183 13.6143 16.4966 14.1144 16.9967C14.6145 17.4968 15.2927 17.7778 16 17.7778C16.7072 17.7778 17.3855 17.4968 17.8856 16.9967C18.3857 16.4966 18.6667 15.8183 18.6667 15.1111C18.6667 14.6202 19.0646 14.2222 19.5555 14.2222C20.0465 14.2222 20.4444 14.6202 20.4444 15.1111C20.4444 16.2898 19.9762 17.4203 19.1427 18.2538C18.3092 19.0873 17.1787 19.5555 16 19.5555C14.8212 19.5555 13.6908 19.0873 12.8573 18.2538C12.0238 17.4203 11.5555 16.2898 11.5555 15.1111C11.5555 14.6202 11.9535 14.2222 12.4444 14.2222Z" fill="' . $icon_color . '""/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M11.2408 6.68254C11.4307 6.46089 11.7081 6.33333 12 6.33333H20C20.2919 6.33333 20.5693 6.46089 20.7593 6.68254L24.7593 11.3492C25.0134 11.6457 25.0717 12.0631 24.9085 12.4179C24.7453 12.7727 24.3905 13 24 13H8.00001C7.60948 13 7.25469 12.7727 7.0915 12.4179C6.92832 12.0631 6.9866 11.6457 7.24076 11.3492L11.2408 6.68254ZM12.4599 8.33333L10.1742 11H21.8258L19.5401 8.33333H12.4599Z" fill="' . $icon_color . '"/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M7 12C7 11.4477 7.44772 11 8 11H24C24.5523 11 25 11.4477 25 12V25.3333C25 25.8856 24.5523 26.3333 24 26.3333H8C7.44772 26.3333 7 25.8856 7 25.3333V12ZM9 13V24.3333H23V13H9Z" fill="' . $icon_color . '"/>
						</svg>';
			} elseif ( 'bag-alt' === $attributes['miniCartIcon'] ) {
				$icon = '<svg class="wc-block-mini-cart__icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M19.5556 12.3333C19.0646 12.3333 18.6667 11.9354 18.6667 11.4444C18.6667 10.7372 18.3857 8.05893 17.8856 7.55883C17.3855 7.05873 16.7073 6.77778 16 6.77778C15.2928 6.77778 14.6145 7.05873 14.1144 7.55883C13.6143 8.05893 13.3333 10.7372 13.3333 11.4444C13.3333 11.9354 12.9354 12.3333 12.4445 12.3333C11.9535 12.3333 11.5556 11.9354 11.5556 11.4444C11.5556 10.2657 12.0238 7.13524 12.8573 6.30175C13.6908 5.46825 14.8213 5 16 5C17.1788 5 18.3092 5.46825 19.1427 6.30175C19.9762 7.13524 20.4445 10.2657 20.4445 11.4444C20.4445 11.9354 20.0465 12.3333 19.5556 12.3333Z" fill="' . $icon_color . '"/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 12C7.5 11.4477 7.94772 11 8.5 11H23.5C24.0523 11 24.5 11.4477 24.5 12V25.3333C24.5 25.8856 24.0523 26.3333 23.5 26.3333H8.5C7.94772 26.3333 7.5 25.8856 7.5 25.3333V12ZM9.5 13V24.3333H22.5V13H9.5Z" fill="' . $icon_color . '" />
						</svg>';
			}
		}

		$button_html = $this->get_cart_price_markup( $attributes ) . '
		<span class="wc-block-mini-cart__quantity-badge">
			' . $icon . '
			<span class="wc-block-mini-cart__badge" style="background:' . $product_count_color . '"></span>
		</span>';

		if ( is_cart() || is_checkout() ) {
			if ( $this->should_not_render_mini_cart( $attributes ) ) {
				return '';
			}

			// It is not necessary to load the Mini-Cart Block on Cart and Checkout page.
			return '<div class="' . esc_attr( $wrapper_classes ) . '" style="visibility:hidden" aria-hidden="true">
				<button class="wc-block-mini-cart__button" disabled>' . $button_html . '</button>
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
		$template_part = BlockTemplateUtils::get_block_template( $template_slug_to_load . '//mini-cart', 'wp_template_part' );

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
			<button class="wc-block-mini-cart__button">' . $button_html . '</button>
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
		};

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
				'title'    => __( 'Empty Mini-Cart Message', 'woo-gutenberg-products-block' ),
				'inserter' => false,
				'content'  => '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>' . __( 'Your cart is currently empty!', 'woo-gutenberg-products-block' ) . '</strong></p><!-- /wp:paragraph -->',
			)
		);
	}

	/**
	 * Callback for `hooked_block_types` to auto-inject the mini-cart block into headers after navigation.
	 *
	 * @param array                    $hooked_blocks An array of block slugs hooked into a given context.
	 * @param string                   $position      Position of the block insertion point.
	 * @param string                   $anchor_block  The block acting as the anchor for the inserted block.
	 * @param \WP_Block_Template|array $context       Where the block is embedded.
	 * @since $VID:$
	 * @return array An array of block slugs hooked into a given context.
	 */
	public function register_auto_insert( $hooked_blocks, $position, $anchor_block, $context ) {
		// Cache for active theme.
		static $active_theme_name = null;
		if ( is_null( $active_theme_name ) ) {
			$active_theme_name = wp_get_theme()->get( 'Name' );
		}
		/**
		 * A list of pattern slugs to exclude from auto-insert (useful when
		 * there are patterns that have a very specific location for the block)
		 *
		 * @since $VID:$
		 */
		$pattern_exclude_list = apply_filters( 'woocommerce_blocks_mini_cart_auto_insert_pattern_exclude_list', [] );

		/**
		 * A list of theme slugs to execute this with. This is a temporary
		 * measure until improvements to the Block Hooks API allow for exposing
		 * to all block themes.
		 *
		 * @since $VID:$
		 */
		$theme_include_list = apply_filters( 'woocommerce_blocks_mini_cart_auto_insert_theme_include_list', [ 'Twenty Twenty-Four' ] );

		if ( $context && in_array( $active_theme_name, $theme_include_list, true ) ) {
			if (
				'after' === $position &&
				'core/navigation' === $anchor_block &&
				$this->is_header_part_or_pattern( $context ) &&
				! $this->pattern_is_excluded( $context, $pattern_exclude_list ) &&
				! $this->has_mini_cart_block( $context )
			) {
				$hooked_blocks[] = 'woocommerce/' . $this->block_name;
			}
		}

		return $hooked_blocks;
	}

	/**
	 * Returns whether the pattern is excluded or not
	 *
	 * @param array|\WP_Block_Template $context Where the block is embedded.
	 * @param array                    $pattern_exclude_list List of pattern slugs to exclude.
	 * @since $VID:$
	 * @return boolean
	 */
	private function pattern_is_excluded( $context, $pattern_exclude_list ) {
		$pattern_slug = is_array( $context ) && isset( $context['slug'] ) ? $context['slug'] : '';
		return in_array( $pattern_slug, $pattern_exclude_list, true );
	}

	/**
	 * Checks if the provided context contains a mini-cart block.
	 *
	 * @param array|\WP_Block_Template $context Where the block is embedded.
	 * @since $VID:$
	 * @return boolean
	 */
	private function has_mini_cart_block( $context ) {
		/**
		 * Note: this won't work for parsing WP_Block_Template instance until it's fixed in core
		 * because $context->content is set as the result of `traverse_and_serialize_blocks` so
		 * the filter callback doesn't get the original content.
		 *
		 * @see https://core.trac.wordpress.org/ticket/59882
		 */
		$content = is_array( $context ) && isset( $context['content'] ) ? $context['content'] : '';
		$content = '' === $content && $context instanceof \WP_Block_Template ? $context->content : $content;
		return strpos( $content, 'wp:woocommerce/mini-cart' ) !== false;
	}

	/**
	 * Given a provided context, returns whether the context refers to header content.
	 *
	 * @param array|\WP_Block_Template $context Where the block is embedded.
	 * @since $VID:$
	 * @return boolean
	 */
	private function is_header_part_or_pattern( $context ) {
		$is_header_pattern = is_array( $context ) &&
		(
			( isset( $context['blockTypes'] ) && in_array( 'core/template-part/header', $context['blockTypes'], true ) ) ||
			( isset( $context['categories'] ) && in_array( 'header', $context['categories'], true ) )
		);
		$is_header_part    = $context instanceof \WP_Block_Template && 'header' === $context->area;
		return ( $is_header_pattern || $is_header_part );
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
