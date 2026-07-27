<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Blocks\Utils\Utils;
use Automattic\WooCommerce\Blocks\Utils\MiniCartUtils;
use Automattic\WooCommerce\Blocks\Utils\BlockHooksTrait;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Enums\TaxDisplayMode;
use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use Automattic\Block_Delimiter;

/**
 * Mini-Cart class.
 *
 * @internal
 */
class MiniCart extends AbstractBlock {
	use BlockHooksTrait;
	use EnableBlockJsonAssetsTrait;

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
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = array() ) {
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
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		/**
		 * Do not render for logged-out users if the Coming Soon mode is enabled for store pages only.
		 */
		$coming_soon_helper = wc_get_container()->get( ComingSoonHelper::class );
		if ( ! is_user_logged_in() && ! WC()->is_rest_api_request() && $coming_soon_helper->is_store_coming_soon() ) {
			return '';
		}

		/**
		 * In the cart and checkout pages, the block is either rendered hidden or removed.
		 * It is not interactive, so it can fall back to the existing implementation.
		 */
		if ( is_cart() || is_checkout() ) {
			return $content . $this->get_markup( MiniCartUtils::migrate_attributes_to_color_panel( $attributes ) );
		}

		wp_enqueue_script_module( $this->get_full_block_name() );

		// Enqueue all integration scripts registered for this block.
		$integration_script_handles = $this->integration_registry->get_all_registered_script_handles();
		foreach ( $integration_script_handles as $handle ) {
			wp_enqueue_script( $handle );
		}

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_cart_state( $consent );
		BlocksSharedState::load_store_config( $consent );
		BlocksSharedState::load_placeholder_image( $consent );

		$cart = $this->get_cart_instance();

		if ( $cart ) {
			$classes_styles           = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
			$icon_color               = isset( $attributes['iconColor']['color'] ) ? esc_attr( $attributes['iconColor']['color'] ) : 'currentColor';
			$product_count_color      = isset( $attributes['productCountColor']['color'] ) ? $attributes['productCountColor']['color'] : '';
			$styles                   = $product_count_color ? 'background:' . esc_attr( $product_count_color ) : '';
			$icon                     = MiniCartUtils::get_svg_icon( $attributes['miniCartIcon'] ?? '', $icon_color );
			$product_count_visibility = isset( $attributes['productCountVisibility'] ) ? $attributes['productCountVisibility'] : 'greater_than_zero';
			$wrapper_classes          = sprintf( 'wc-block-mini-cart wp-block-woocommerce-mini-cart %s', $classes_styles['classes'] );
			$wrapper_styles           = $classes_styles['styles'];
			// Pre-render the template part so nested blocks enqueue their assets before the overlay is printed in wp_footer.
			$template_part_contents           = $this->get_template_part_contents( false );
			$template_part_contents           = do_blocks( $this->process_template_contents( $template_part_contents ) );
			$cart_item_count                  = $cart ? $cart->get_cart_contents_count() : 0;
			$display_cart_price_including_tax = get_option( 'woocommerce_tax_display_cart' ) === TaxDisplayMode::INCLUSIVE;
			$cart_item_count                  = $cart ? $cart->get_cart_contents_count() : 0;
			$badge_is_visible                 = ( 'always' === $product_count_visibility ) || ( 'never' !== $product_count_visibility && $cart_item_count > 0 );
			$formatted_subtotal               = '';
			$html                             = new \WP_HTML_Tag_Processor( wc_price( $cart->get_displayed_subtotal() ) );
			$on_cart_click_behaviour          = isset( $attributes['onCartClickBehaviour'] ) ? $attributes['onCartClickBehaviour'] : 'open_drawer';

			if ( $html->next_tag( 'bdi' ) ) {
				while ( $html->next_token() ) {
					if ( '#text' === $html->get_token_name() ) {
						$formatted_subtotal .= $html->get_modifiable_text();
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
					'isOpen'             => false,
					'totalItemsInCart'   => $cart_item_count,
					'shouldShowTaxLabel' => $cart->get_cart_contents_tax() > 0,
					'badgeIsVisible'     => $badge_is_visible,
					'formattedSubtotal'  => $formatted_subtotal,
					'drawerOverlayClass' => 'wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--with-slide-out wc-block-components-drawer__screen-overlay--is-hidden',
					'buttonAriaLabel'    => function () use ( $button_aria_label_template ) {
						$state = wp_interactivity_state();
						return isset( $attributes['hasHiddenPrice'] ) && false !== $attributes['hasHiddenPrice']
							? sprintf( $button_aria_label_template, $state['totalItemsInCart'] )
							: sprintf( $button_aria_label_template, $state['totalItemsInCart'], $state['formattedSubtotal'] );
					},
					'productCountColor'  => $product_count_color,
				)
			);

			$context = array(
				'productCountVisibility' => $product_count_visibility,
			);

			wp_interactivity_config(
				$this->get_full_block_name(),
				array(
					'displayCartPriceIncludingTax' => $display_cart_price_including_tax,
					'onCartClickBehaviour'         => $on_cart_click_behaviour,
					'checkoutUrl'                  => wc_get_checkout_url(),
					'buttonAriaLabelTemplate'      => $button_aria_label_template,
				)
			);

			$cart_always_shows_price = isset( $attributes['hasHiddenPrice'] ) && false === $attributes['hasHiddenPrice'];
			$price_color             = isset( $attributes['priceColor']['color'] ) ? $attributes['priceColor']['color'] : '';

			$button_role = 'navigate_to_checkout' === $on_cart_click_behaviour
				? 'role="link"'
				: '';

			// Render the minicart overlay in the body, outside of the block itself.
			if ( ! has_action( 'wp_footer', array( $this, 'render_mini_cart_overlay' ) ) ) {
				add_action( 'wp_footer', array( $this, 'render_mini_cart_overlay' ) );
			}
			ob_start();
			?>

			<div
				data-wp-interactive="woocommerce/mini-cart"
				data-wp-init="callbacks.setupJQueryEventBridge"
				data-wp-on-document--wc-blocks_added_to_cart="woocommerce::actions.refreshCartItems"
				data-wp-on-document--wc-blocks_removed_from_cart="woocommerce::actions.refreshCartItems"
				<?php if ( 'open_drawer' === $attributes['addToCartBehaviour'] ) : ?>
				data-wp-on-document--wc-blocks_added_to_cart---open-drawer="actions.openDrawer"
				<?php endif; ?>
				data-wp-watch="callbacks.disableScrollingOnBody"
				data-wp-init--mark-as-hydrated="callbacks.markAsHydrated"
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo wp_interactivity_data_wp_context( $context ); ?>
				class="<?php echo esc_attr( $wrapper_classes ); ?>"
				style="<?php echo esc_attr( $wrapper_styles ); ?>"
			>
				<button
					data-wp-on--click="actions.openDrawer"
					data-wp-bind--aria-label="state.buttonAriaLabel"
					class="wc-block-mini-cart__button"
					<?php echo $button_role; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					<span class="wc-block-mini-cart__quantity-badge">
						<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $icon;
						?>
							<?php if ( 'never' !== $product_count_visibility ) : ?>
								<span
									data-wp-style--background-color="state.badgeBackgroundColor"
									data-wp-style--color="state.badgeTextColor"
									data-wp-bind--hidden="!state.badgeIsVisible"
									data-wp-text="state.totalItemsInCart"
									class="wc-block-mini-cart__badge"
									style="<?php echo esc_attr( $styles ); ?>"
								>
							</span>
						<?php endif; ?>
					</span>
					<?php if ( $cart_always_shows_price ) : ?>
						<span data-wp-text="state.formattedSubtotal" class="wc-block-mini-cart__amount" translate="no" style="<?php echo 'color:' . esc_attr( $price_color ); ?>">
						</span>
						<?php if ( ! empty( $this->tax_label ) ) : ?>
							<small
								data-wp-bind--hidden="!state.shouldShowTaxLabel"
								class="wc-block-mini-cart__tax-label"
								style="color:<?php echo esc_attr( $price_color ); ?>"
							>
								<?php echo esc_html( $this->tax_label ); ?>
							</small>
						<?php endif; ?>
					<?php endif; ?>
				</button>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		return '';
	}

	/**
	 * Echoes the Interactivity API Mini Cart overlay markup.
	 *
	 * @since 11.1.0
	 * @return void
	 */
	public function render_mini_cart_overlay() {
		$template_part_contents = $this->get_template_part_contents( false );
		$template_part_contents = do_blocks( $this->process_template_contents( $template_part_contents ) );
		ob_start();
		?>
		<div
			data-wp-interactive="woocommerce/mini-cart"
			data-wp-router-region='{ "id": "woocommerce/mini-cart-overlay", "attachTo": "body" }'
			data-wp-key="wc-mini-cart-overlay"
			data-wp-on--click="actions.overlayCloseDrawer"
			data-wp-on--keydown="actions.handleOverlayKeydown"
			data-wp-watch="callbacks.focusFirstElement"
			data-wp-bind--class="state.drawerOverlayClass"
		>
			<div
				data-wp-bind--role="state.drawerRole"
				data-wp-bind--aria-modal="state.isOpen"
				data-wp-bind--aria-hidden="!state.isOpen"
				data-wp-bind--tabindex="state.drawerTabIndex"
				class="wc-block-mini-cart__drawer wc-block-components-drawer is-mobile"
			>
				<div class="wc-block-components-drawer__content">
					<div class="wc-block-mini-cart__template-part">
						<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $template_part_contents;
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_interactivity_process_directives( ob_get_clean() );
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
	 * @param bool $do_blocks Whether to apply do_blocks() to the template part contents.
	 * @return string The contents of the template part.
	 */
	protected function get_template_part_contents( $do_blocks = true ) {
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
			if ( $do_blocks ) {
				$template_part_contents = do_blocks( $template_part->content );
			} else {
				$template_part_contents = $template_part->content;
			}
		}

		if ( '' === $template_part_contents ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$file_contents = file_get_contents( Package::get_path() . 'templates/' . BlockTemplateUtils::DIRECTORY_NAMES['TEMPLATE_PARTS'] . '/' . $template_name . '.html' );
			if ( $do_blocks ) {
				$template_part_contents = do_blocks(
					$file_contents
				);
			} else {
				$template_part_contents = $file_contents;
			}
		}

		return $template_part_contents;
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

		$classes_styles  = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		$wrapper_classes = sprintf( 'wc-block-mini-cart wp-block-woocommerce-mini-cart %s', $classes_styles['classes'] );
		$wrapper_styles  = $classes_styles['styles'];

		$icon_color          = isset( $attributes['iconColor']['color'] ) ? esc_attr( $attributes['iconColor']['color'] ) : 'currentColor';
		$product_count_color = isset( $attributes['productCountColor']['color'] ) ? $attributes['productCountColor']['color'] : '';
		$styles              = $product_count_color ? 'background:' . esc_attr( $product_count_color ) : '';
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

		$template_part_contents = $this->get_template_part_contents();

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
