<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Internal\Features\BlockEditorUnifiedAssets;

/**
 * Registers side-effect-free blocks deferring class instantiation to render time.
 *
 * @internal
 */
final class BlockTypesLazyController {
	/**
	 * Instance of the asset API.
	 *
	 * @var AssetApi
	 */
	private AssetApi $asset_api;

	/**
	 * Instance of the asset data registry.
	 *
	 * @var AssetDataRegistry
	 */
	private AssetDataRegistry $asset_data_registry;

	/**
	 * Processed block classes registry (class FQN → full block name).
	 *
	 * @var array<string,string>
	 */
	private array $registered_blocks = array();

	/**
	 * Constructor.
	 *
	 * @param AssetApi          $asset_api           Instance of the asset API.
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetApi $asset_api, AssetDataRegistry $asset_data_registry ) {
		$this->asset_api           = $asset_api;
		$this->asset_data_registry = $asset_data_registry;
	}

	/**
	 * Register side-effect-free blocks (class instantiation deferred to render time).
	 *
	 * @param string[] $whitelist Block class FQNs intended for initialization.
	 * @return string[]
	 */
	public function register_blocks( array $whitelist ): array {
		if ( ! empty( $this->registered_blocks ) ) {
			return array_keys( array_filter( $this->registered_blocks, array( \WP_Block_Type_Registry::get_instance(), 'is_registered' ) ) );
		}

		$whitelisted           = array_flip( $whitelist );
		$unified_editor_assets = BlockEditorUnifiedAssets::is_enabled();

		$inner_block_groups = array(
			$this->get_cart_inner_blocks(),
			$this->get_mini_cart_inner_blocks(),
			$this->get_checkout_inner_blocks(),
		);

		foreach ( $inner_block_groups as $blocks_group ) {
			foreach ( $blocks_group as $block_name => $block_class ) {
				if ( isset( $whitelisted[ $block_class ] ) ) {
					$registered_block_type = register_block_type_from_metadata(
						$this->asset_api->generate_block_metadata_path( $block_name, 'inner-blocks/' ),
						array(
							// Please, don't extend this list further! We expect everyone to lean onto block.json.
							'render_callback' => $this->create_lazy_render_callback( $block_class ),
							'editor_style'    => $unified_editor_assets ? 'wc-block-library-style' : 'wc-blocks-editor-style',
						)
					);
					if ( $registered_block_type instanceof \WP_Block_Type ) {
						$this->registered_blocks[ $block_class ] = $registered_block_type->name;
					}
				}
			}
		}

		return array_keys( $this->registered_blocks );
	}

	/**
	 * Create a render callback that lazily instantiates the block class on it's first render.
	 *
	 * @param string $block_type_class Fully-qualified class name.
	 * @return callable
	 */
	private function create_lazy_render_callback( string $block_type_class ): callable {
		$asset_api           = $this->asset_api;
		$asset_data_registry = $this->asset_data_registry;

		return static function ( $attributes = array(), $content = '', $block = null ) use ( $block_type_class, $asset_api, $asset_data_registry ) {
			static $instance = null;
			$instance        = $instance ?? new $block_type_class( $asset_api, $asset_data_registry, new IntegrationRegistry() );
			return $instance->render_callback( $attributes, $content, $block );
		};
	}

	/**
	 * Side effect free and stateless Cart inner blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_cart_inner_blocks(): array {
		// ProceedToCheckoutBlock: excluded — overrides enqueue_data() with editor-visible checkout page URL.
		return array(
			'cart-accepted-payment-methods-block'  => \Automattic\WooCommerce\Blocks\BlockTypes\CartAcceptedPaymentMethodsBlock::class,
			'cart-cross-sells-block'               => \Automattic\WooCommerce\Blocks\BlockTypes\CartCrossSellsBlock::class,
			'cart-cross-sells-products-block'      => \Automattic\WooCommerce\Blocks\BlockTypes\CartCrossSellsProductsBlock::class,
			'cart-express-payment-block'           => \Automattic\WooCommerce\Blocks\BlockTypes\CartExpressPaymentBlock::class,
			'cart-items-block'                     => \Automattic\WooCommerce\Blocks\BlockTypes\CartItemsBlock::class,
			'cart-line-items-block'                => \Automattic\WooCommerce\Blocks\BlockTypes\CartLineItemsBlock::class,
			'cart-order-summary-block'             => \Automattic\WooCommerce\Blocks\BlockTypes\CartOrderSummaryBlock::class,
			'cart-order-summary-coupon-form-block' => \Automattic\WooCommerce\Blocks\BlockTypes\CartOrderSummaryCouponFormBlock::class,
			'cart-order-summary-discount-block'    => \Automattic\WooCommerce\Blocks\BlockTypes\CartOrderSummaryDiscountBlock::class,
			'cart-order-summary-fee-block'         => \Automattic\WooCommerce\Blocks\BlockTypes\CartOrderSummaryFeeBlock::class,
			'cart-order-summary-heading-block'     => \Automattic\WooCommerce\Blocks\BlockTypes\CartOrderSummaryHeadingBlock::class,
			'cart-order-summary-shipping-block'    => \Automattic\WooCommerce\Blocks\BlockTypes\CartOrderSummaryShippingBlock::class,
			'cart-order-summary-subtotal-block'    => \Automattic\WooCommerce\Blocks\BlockTypes\CartOrderSummarySubtotalBlock::class,
			'cart-order-summary-taxes-block'       => \Automattic\WooCommerce\Blocks\BlockTypes\CartOrderSummaryTaxesBlock::class,
			'cart-order-summary-totals-block'      => \Automattic\WooCommerce\Blocks\BlockTypes\CartOrderSummaryTotalsBlock::class,
			'cart-totals-block'                    => \Automattic\WooCommerce\Blocks\BlockTypes\CartTotalsBlock::class,
			'empty-cart-block'                     => \Automattic\WooCommerce\Blocks\BlockTypes\EmptyCartBlock::class,
			'filled-cart-block'                    => \Automattic\WooCommerce\Blocks\BlockTypes\FilledCartBlock::class,
		);
	}

	/**
	 * Side effect free and stateless Checkout inner blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_checkout_inner_blocks(): array {
		// CheckoutExpressPaymentBlock: excluded — overrides initialize() with save_post hook for express payment attribute sync.
		return array(
			'checkout-actions-block'                   => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutActionsBlock::class,
			'checkout-additional-information-block'    => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutAdditionalInformationBlock::class,
			'checkout-billing-address-block'           => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutBillingAddressBlock::class,
			'checkout-contact-information-block'       => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutContactInformationBlock::class,
			'checkout-fields-block'                    => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutFieldsBlock::class,
			'checkout-order-note-block'                => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderNoteBlock::class,
			'checkout-order-summary-block'             => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderSummaryBlock::class,
			'checkout-order-summary-cart-items-block'  => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderSummaryCartItemsBlock::class,
			'checkout-order-summary-coupon-form-block' => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderSummaryCouponFormBlock::class,
			'checkout-order-summary-discount-block'    => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderSummaryDiscountBlock::class,
			'checkout-order-summary-fee-block'         => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderSummaryFeeBlock::class,
			'checkout-order-summary-shipping-block'    => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderSummaryShippingBlock::class,
			'checkout-order-summary-subtotal-block'    => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderSummarySubtotalBlock::class,
			'checkout-order-summary-taxes-block'       => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderSummaryTaxesBlock::class,
			'checkout-order-summary-totals-block'      => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutOrderSummaryTotalsBlock::class,
			'checkout-payment-block'                   => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutPaymentBlock::class,
			'checkout-pickup-options-block'            => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutPickupOptionsBlock::class,
			'checkout-shipping-address-block'          => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutShippingAddressBlock::class,
			'checkout-shipping-method-block'           => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutShippingMethodBlock::class,
			'checkout-shipping-methods-block'          => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutShippingMethodsBlock::class,
			'checkout-terms-block'                     => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutTermsBlock::class,
			'checkout-totals-block'                    => \Automattic\WooCommerce\Blocks\BlockTypes\CheckoutTotalsBlock::class,
		);
	}

	/**
	 * Side effect free and stateless MiniCart inner blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_mini_cart_inner_blocks(): array {
		// MiniCartFooterBlock: excluded — overrides enqueue_data() with editor-visible footer description.
		return array(
			'empty-mini-cart-contents-block'      => \Automattic\WooCommerce\Blocks\BlockTypes\EmptyMiniCartContentsBlock::class,
			'filled-mini-cart-contents-block'     => \Automattic\WooCommerce\Blocks\BlockTypes\FilledMiniCartContentsBlock::class,
			'mini-cart-cart-button-block'         => \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartCartButtonBlock::class,
			'mini-cart-checkout-button-block'     => \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartCheckoutButtonBlock::class,
			'mini-cart-items-block'               => \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartItemsBlock::class,
			'mini-cart-products-table-block'      => \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartProductsTableBlock::class,
			'mini-cart-shopping-button-block'     => \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartShoppingButtonBlock::class,
			'mini-cart-title-block'               => \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartTitleBlock::class,
			'mini-cart-title-items-counter-block' => \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartTitleItemsCounterBlock::class,
			'mini-cart-title-label-block'         => \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartTitleLabelBlock::class,
		);
	}
}
