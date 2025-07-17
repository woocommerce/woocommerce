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
use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\Block_Delimiter;

/**
 * Mini-Cart class.
 *
 * @internal
 */
class MiniCart extends AbstractBlock {
	use BlockHooksTrait;
	use BlocksSharedState;

	// TODO: Check if we can use the `BlockJsonAssetsTrait`.

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart';

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
	 * WooCommerce mini-cart template blocks.
	 *
	 * @var array
	 */
	const MINI_CART_TEMPLATE_BLOCKS = array(
		'woocommerce/mini-cart-contents',
		'woocommerce/filled-mini-cart-contents-block',
		'woocommerce/mini-cart-title-block',
		'woocommerce/mini-cart-title-label-block',
		'woocommerce/mini-cart-title-items-counter-block',
		'woocommerce/mini-cart-items-block',
		'woocommerce/mini-cart-products-table-block',
		'woocommerce/mini-cart-footer-block',
		'woocommerce/mini-cart-cart-button-block',
		'woocommerce/mini-cart-checkout-button-block',
		'woocommerce/empty-mini-cart-contents-block',
		'woocommerce/mini-cart-shopping-button-block',
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
		$script = array(
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => array( 'wc-blocks' ),
		);
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
		// The frontend script module is enqueued in the render method.
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]
	 */
	protected function get_block_type_style() {
		return array_merge( parent::get_block_type_style(), array( 'wc-blocks-packages-style' ) );
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
	 * Render the Mini-Cart block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$cart = $this->get_cart_instance();

		/**
		 *  In the editor we will display the placeholder, so no need to load
		 *  real cart data and to print the markup. In the REST API or any other
		 *  non-frontend context where the cart is not available, we don't need
		 *  to render anything.
		 */
		if ( is_admin() || WC()->is_rest_api_request() || null === $cart ) {
			return '';
		}

		// TODO: Check if we can replace this with `get_block_wrapper_attributes`.
		$classes_styles  = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array( 'text_color', 'background_color', 'font_size', 'font_weight', 'font_family', 'extra_classes' ) );
		$wrapper_classes = sprintf( 'wc-block-mini-cart wp-block-woocommerce-mini-cart %s', $classes_styles['classes'] );

		$icon_color               = isset( $attributes['iconColor']['color'] ) ? esc_attr( $attributes['iconColor']['color'] ) : 'currentColor';
		$product_count_color      = isset( $attributes['productCountColor']['color'] ) ? esc_attr( $attributes['productCountColor']['color'] ) : '';
		$styles                   = $product_count_color ? 'background:' . $product_count_color : '';
		$icon                     = MiniCartUtils::get_svg_icon( $attributes['miniCartIcon'] ?? '', $icon_color );
		$product_count_visibility = isset( $attributes['productCountVisibility'] ) ? $attributes['productCountVisibility'] : 'greater_than_zero';

		/**
		 * In the cart and checkout pages, the block is either rendered hidden
		 * or removed.
		 */
		if ( is_cart() || is_checkout() ) {
			if (
				isset( $attributes['cartAndCheckoutRenderStyle'] )
				&& 'hidden' !== $attributes['cartAndCheckoutRenderStyle']
			) {
				// When the block is removed on the cart and checkout pages.
				return '';
			}

			$button_html = '<span class="wc-block-mini-cart__quantity-badge">
				' . $icon . '
				' . ( 'never' !== $product_count_visibility ? '<span class="wc-block-mini-cart__badge" style="' . esc_attr( $styles ) . '"></span>' : '' ) . '
			</span>
			' . $this->get_cart_price_markup( $attributes );

			// When the block is hidden on the cart and checkout pages.
			return '<div class="' . esc_attr( $wrapper_classes ) . '" style="visibility:hidden" aria-hidden="true">
				<button class="wc-block-mini-cart__button" disabled aria-label="' . __( 'Cart', 'woocommerce' ) . '">' . $button_html . '</button>
			</div>';
		}

		wp_enqueue_script_module( $this->get_full_block_name() );

		$this->register_cart_interactivity( 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce' );
		$this->initialize_shared_config( 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce' );

		$wrapper_styles                   = $classes_styles['styles'];
		$template_part_contents           = do_blocks( $this->process_template_contents( $this->get_template_part_contents() ) );
		$cart_item_count                  = $cart->get_cart_contents_count() || 0;
		$display_cart_price_including_tax = get_option( 'woocommerce_tax_display_cart' ) === 'incl';
		$cart_item_count                  = $cart->get_cart_contents_count() || 0;
		$badge_is_visible                 = ( 'always' === $product_count_visibility ) || ( 'never' !== $product_count_visibility && $cart_item_count > 0 );
		$on_cart_click_behaviour          = isset( $attributes['onCartClickBehaviour'] ) ? $attributes['onCartClickBehaviour'] : 'open_drawer';
		$cart_always_shows_price          = isset( $attributes['hasHiddenPrice'] ) && false === $attributes['hasHiddenPrice'];
		$price_color                      = isset( $attributes['priceColor']['color'] ) ? $attributes['priceColor']['color'] : '';
		$button_role                      = 'navigate_to_checkout' === $on_cart_click_behaviour ? 'role="link"' : '';

		$formatted_subtotal = '';
		$p                  = new \WP_HTML_Tag_Processor( wc_price( $cart->get_displayed_subtotal() ) );
		if ( $p->next_tag( 'bdi' ) ) {
			while ( $p->next_token() ) {
				if ( '#text' === $p->get_token_name() ) {
					$formatted_subtotal .= $p->get_modifiable_text();
				}
			}
		}

			// The following translation is a temporary workaround. It will be
			// reverted to the previous form (`%1$d item in cart`) as soon as the
			// `@wordpress/i18n` package is available as a script module.
			$button_aria_label_template = isset( $attributes['hasHiddenPrice'] ) && false !== $attributes['hasHiddenPrice']
				/* translators: %d is the number of products in the cart. */
				? __( 'Number of items in the cart: %d', 'woocommerce' )
				/* translators: %1$d is the number of products in the cart. %2$s is the cart total */
				: __( 'Number of items in the cart: %1$d. Total price of %2$s', 'woocommerce' );

			wp_interactivity_state(
				$this->get_full_block_name(),
				array(
					'totalItemsInCart'   => $cart_item_count,
					'badgeIsVisible'     => $badge_is_visible,
					'formattedSubtotal'  => $formatted_subtotal,
					'buttonAriaLabel'    => function () use ( $button_aria_label_template ) {
						$state = wp_interactivity_state();
						return isset( $attributes['hasHiddenPrice'] ) && false !== $attributes['hasHiddenPrice']
							? sprintf( $button_aria_label_template, $state['totalItemsInCart'] )
							: sprintf( $button_aria_label_template, $state['totalItemsInCart'], $state['formattedSubtotal'] );
					},
					'drawerOverlayClass' => 'wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--with-slide-out wc-block-components-drawer__screen-overlay--is-hidden',
				)
			);

		$context = array(
			'isOpen'                       => false,
			'productCountVisibility'       => $product_count_visibility,
			'displayCartPriceIncludingTax' => $display_cart_price_including_tax,
		);

			wp_interactivity_config(
				$this->get_full_block_name(),
				array(
					'addToCartBehaviour'      => $attributes['addToCartBehaviour'],
					'onCartClickBehaviour'    => $on_cart_click_behaviour,
					'checkoutUrl'             => wc_get_checkout_url(),
					'buttonAriaLabelTemplate' => $button_aria_label_template,
				)
			);

			$cart_always_shows_price = isset( $attributes['hasHiddenPrice'] ) && false === $attributes['hasHiddenPrice'];
			$price_color             = isset( $attributes['priceColor']['color'] ) ? $attributes['priceColor']['color'] : '';

			$button_role = 'navigate_to_checkout' === $on_cart_click_behaviour
				? 'role="link"'
				: '';

			ob_start();
		?>
		<div
			data-wp-interactive="woocommerce/mini-cart"
			data-wp-init="callbacks.setupOpenDrawerListener"
			data-wp-watch="callbacks.disableScrollingOnBody"
			<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			class="<?php echo esc_attr( $wrapper_classes ); ?>"
			style="<?php echo esc_attr( $wrapper_styles ); ?>"
		>
			<button 
				data-wp-on--click="callbacks.openDrawer"
				data-wp-bind--aria-label="state.buttonAriaLabel"
				class="wc-block-mini-cart__button"
				<?php echo $button_role; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				aria-label="<?php echo esc_attr( __( 'Cart', 'woocommerce' ) ); ?>"
			>
				<span class="wc-block-mini-cart__quantity-badge">
					<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( 'never' !== $product_count_visibility ) : ?>
						<span data-wp-bind--hidden="!state.badgeIsVisible" data-wp-text="state.totalItemsInCart" class="wc-block-mini-cart__badge" style="<?php echo esc_attr( $styles ); ?>">
						</span>
					<?php endif; ?>
				</span>
				<?php if ( $cart_always_shows_price ) : ?>
					<span data-wp-text="state.formattedSubtotal" class="wc-block-mini-cart__amount" style="<?php echo 'color:' . esc_attr( $price_color ); ?>">
					</span>
					<?php echo $this->get_include_tax_label_markup( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</button>
			<div data-wp-on--click="callbacks.overlayCloseDrawer" data-wp-bind--class="state.drawerOverlayClass">
				<div 
					data-wp-bind--role="state.drawerRole"
					data-wp-bind--aria-modal="context.isOpen"
					data-wp-bind--aria-hidden="!context.isOpen"
					data-wp-bind--tabindex="state.drawerTabIndex"
					class="wc-block-mini-cart__drawer wc-block-components-drawer is-mobile"
				>
					<div class="wc-block-components-drawer__content">
						<div class="wc-block-mini-cart__template-part">
							<?php echo $template_part_contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}


	/**
	 * Process template contents to remove unwanted div wrappers.
	 *
	 * The old Mini Cart template had extra divs nested within the block tags
	 * that are no longer necessary since we don't render the Mini Cart with
	 * React anymore. To maintain compatibility with user saved templates that
	 * have these wrapper divs, we must remove them.
	 *
	 * @param string $template_contents The template contents to process.
	 * @return string The processed template contents.
	 */
	protected function process_template_contents( $template_contents ) {
		$p               = new \WP_HTML_Tag_Processor( $template_contents );
		$is_old_template = $p->next_tag(
			array(
				'tag_name'   => 'div',
				'class_name' => 'wp-block-woocommerce-mini-cart-contents',
			)
		);

		if ( ! $is_old_template ) {
			return $template_contents;
		}

		$output                   = '';
		$was_at                   = 0;
		$is_mini_cart_block_stack = array( false );

		foreach ( Block_Delimiter::scan_delimiters( $template_contents ) as $where => $delimiter ) {
			list( $at, $length ) = $where;
			$block_type          = $delimiter->allocate_and_return_block_type();
			$delimiter_type      = $delimiter->get_delimiter_type();

			if ( ! $is_mini_cart_block_stack[ array_key_last( $is_mini_cart_block_stack ) ] ) {
				// Copy content up to and including this block delimiter.
				$output .= substr( $template_contents, $was_at, $at + $length - $was_at );
			} else {
				// Just copy the block delimiter, skipping the wrapper div that existed before.
				$output .= substr( $template_contents, $at, $length );
			}

			// Update the position to the end of the block delimiter.
			$was_at = $at + $length;

			if ( Block_Delimiter::OPENER === $delimiter_type ) {
				// Add the Mini Cart block info to a stack.
				$is_mini_cart_block_stack[] = in_array( $block_type, self::MINI_CART_TEMPLATE_BLOCKS, true );
			} elseif ( Block_Delimiter::CLOSER === $delimiter_type ) {
				// Pop the last Mini Cart block info from the stack.
				array_pop( $is_mini_cart_block_stack );
			}
		}

		// Add any remaining content.
		$output .= substr( $template_contents, $was_at );

		return $output;
	}

	/**
	 * Get the mini cart template part contents to render inside the drawer.
	 *
	 * @return string The contents of the template part.
	 */
	protected function get_template_part_contents() {
		$template_name          = 'mini-cart';
		$template_part_contents = '';

		// Determine if we need to load the template part from the DB, the theme or WooCommerce in that order.
		$templates_from_db = BlockTemplateUtils::get_block_templates_from_db( array( $template_name ), 'wp_template_part' );
		if ( is_countable( $templates_from_db ) && count( $templates_from_db ) > 0 ) {
			$template_slug_to_load = $templates_from_db[0]->theme;
		} else {
			$theme_has_mini_cart   = BlockTemplateUtils::theme_has_template_part( $template_name );
			$template_slug_to_load = $theme_has_mini_cart ? get_stylesheet() : BlockTemplateUtils::PLUGIN_SLUG;
		}
		$template_part = get_block_template( $template_slug_to_load . '//' . $template_name, 'wp_template_part' );

		if ( $template_part && ! empty( $template_part->content ) ) {
			$template_part_contents = $template_part->content;
		}

		if ( '' === $template_part_contents ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$file_contents          = file_get_contents( Package::get_path() . 'templates/' . BlockTemplateUtils::DIRECTORY_NAMES['TEMPLATE_PARTS'] . '/' . $template_name . '.html' );
			$template_part_contents = $file_contents;
		}

		return $template_part_contents;
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
}
