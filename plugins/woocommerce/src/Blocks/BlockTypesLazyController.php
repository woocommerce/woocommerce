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

		$is_admin     = is_admin();
		$block_groups = array(
			$this->get_order_confirmation_blocks(),
			$this->get_add_to_cart_with_options_blocks(),
			$this->get_product_display_element_blocks(),
			$this->get_product_grid_blocks(),
			$this->get_product_listing_blocks(),
			$this->get_product_reviews_blocks(),
			$this->get_cart_and_checkout_blocks(),
			$this->get_dedicated_pages_blocks(),
		);
		foreach ( $block_groups as $blocks_group ) {
			foreach ( $blocks_group as $block_name => $block_class ) {
				if ( isset( $whitelisted[ $block_class ] ) ) {
					// Backward compatibility: use original handles for the editor scripts and blocks styles.
					if ( ! $unified_editor_assets && $is_admin ) {
						$this->asset_api->register_script( 'wc-' . $block_name . '-block', $this->asset_api->get_block_asset_build_path( $block_name ), array( 'wc-blocks' ) );
					}
					$asset_api          = $this->asset_api;
					$use_custom_handles = static function ( $metadata ) use ( $block_name, $asset_api ) {
						// Backward compatibility: some blocks didn't use the custom handles before lazy-loading introduction.
						$skip   = array( 'product-price', 'product-stock-indicator' );
						$styles = (array) ( $metadata['style'] ?? array() );
						foreach ( $styles as $index => $specifier ) {
							if ( 'file:../' . $block_name . '.css' === $specifier && ! in_array( $block_name, $skip, true ) ) {
								$custom_handle     = 'wc-blocks-style-' . $block_name;
								$styles[ $index ]  = $custom_handle;
								$metadata['style'] = $styles;
								$asset_api->register_style( $custom_handle, $asset_api->get_block_asset_build_path( $block_name, 'css' ), array(), 'all', true );
								break;
							}
						}
						return $metadata;
					};

					add_filter( 'block_type_metadata', $use_custom_handles );
					$registered_block_type = register_block_type_from_metadata(
						$this->asset_api->generate_block_metadata_path( $block_name ),
						array(
							// Please, don't extend this list further! We expect everyone to lean onto block.json.
							'render_callback' => $this->create_lazy_render_callback( $block_class ),
							'editor_style'    => $unified_editor_assets ? 'wc-block-library-style' : 'wc-blocks-editor-style',
							'editor_script'   => $unified_editor_assets ? 'wc-block-library' : 'wc-' . $block_name . '-block',
						)
					);
					remove_filter( 'block_type_metadata', $use_custom_handles );

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
		$list = array(
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
		if ( ! is_admin() ) {
			// These blocks override enqueue_data() with editor-visible data, hence load eagerly in admin.
			$list['proceed-to-checkout-block'] = \Automattic\WooCommerce\Blocks\BlockTypes\ProceedToCheckoutBlock::class;
		}

		return $list;
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
		$list = array(
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
		if ( ! is_admin() ) {
			// These blocks override enqueue_data() with editor-visible data, hence load eagerly in admin.
			$list['mini-cart-footer-block'] = \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartFooterBlock::class;
		}

		return $list;
	}

	/**
	 * Side effect free and stateless order confirmation blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_order_confirmation_blocks(): array {
		// CreateAccount: excluded — overrides initialize() with hooked_block_types (Block Hooks API).
		$list = array(
			'order-confirmation-status'                 => \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Status::class,
			'order-confirmation-summary'                => \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Summary::class,
			'order-confirmation-totals'                 => \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Totals::class,
			'order-confirmation-totals-wrapper'         => \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\TotalsWrapper::class,
			'order-confirmation-downloads'              => \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Downloads::class,
			'order-confirmation-billing-wrapper'        => \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\BillingWrapper::class,
			'order-confirmation-shipping-wrapper'       => \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\ShippingWrapper::class,
			'order-confirmation-additional-information' => \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\AdditionalInformation::class,
			'order-confirmation-additional-fields'      => \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\AdditionalFields::class,
		);
		if ( ! is_admin() ) {
			// These blocks override enqueue_data() with editor-visible data, hence load eagerly in admin.
			$list['order-confirmation-billing-address']           = \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\BillingAddress::class;
			$list['order-confirmation-shipping-address']          = \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\ShippingAddress::class;
			$list['order-confirmation-downloads-wrapper']         = \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\DownloadsWrapper::class;
			$list['order-confirmation-additional-fields-wrapper'] = \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\AdditionalFieldsWrapper::class;
		}

		return $list;
	}

	/**
	 * Side effect free and stateless add to cart with options blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_add_to_cart_with_options_blocks(): array {
		$list = array(
			'add-to-cart-with-options-quantity-selector'  => \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\QuantitySelector::class,
			'add-to-cart-with-options-variation-description' => \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationDescription::class,
			'add-to-cart-with-options-variation-selector' => \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelector::class,
			'add-to-cart-with-options-variation-selector-attribute-name' => \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttributeName::class,
			'add-to-cart-with-options-grouped-product-selector' => \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\GroupedProductSelector::class,
			'add-to-cart-with-options-grouped-product-item' => \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\GroupedProductItem::class,
			'add-to-cart-with-options-grouped-product-item-selector' => \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\GroupedProductItemSelector::class,
			'add-to-cart-with-options-grouped-product-item-label' => \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\GroupedProductItemLabel::class,
		);
		if ( ! is_admin() ) {
			// These blocks override enqueue_data() with editor-visible data, hence load eagerly in admin.
			$list['add-to-cart-with-options']                              = \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\AddToCartWithOptions::class;
			$list['add-to-cart-with-options-variation-selector-attribute'] = \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttribute::class;
		}

		return $list;
	}

	/**
	 * Side effect free and stateless product display element blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_product_display_element_blocks(): array {
		// ProductGalleryLargeImage: excluded — overrides initialize() with block_type_metadata_settings filter.
		$list = array(
			'product-price'                             => \Automattic\WooCommerce\Blocks\BlockTypes\ProductPrice::class,
			'product-title'                             => \Automattic\WooCommerce\Blocks\BlockTypes\ProductTitle::class,
			'product-sku'                               => \Automattic\WooCommerce\Blocks\BlockTypes\ProductSKU::class,
			'product-sale-badge'                        => \Automattic\WooCommerce\Blocks\BlockTypes\ProductSaleBadge::class,
			'product-summary'                           => \Automattic\WooCommerce\Blocks\BlockTypes\ProductSummary::class,
			'product-average-rating'                    => \Automattic\WooCommerce\Blocks\BlockTypes\ProductAverageRating::class,
			'product-rating'                            => \Automattic\WooCommerce\Blocks\BlockTypes\ProductRating::class,
			'product-rating-counter'                    => \Automattic\WooCommerce\Blocks\BlockTypes\ProductRatingCounter::class,
			'product-rating-stars'                      => \Automattic\WooCommerce\Blocks\BlockTypes\ProductRatingStars::class,
			'product-description'                       => \Automattic\WooCommerce\Blocks\BlockTypes\ProductDescription::class,
			'product-specifications'                    => \Automattic\WooCommerce\Blocks\BlockTypes\ProductSpecifications::class,
			'product-gallery'                           => \Automattic\WooCommerce\Blocks\BlockTypes\ProductGallery::class,
			'product-gallery-thumbnails'                => \Automattic\WooCommerce\Blocks\BlockTypes\ProductGalleryThumbnails::class,
			'product-gallery-large-image-next-previous' => \Automattic\WooCommerce\Blocks\BlockTypes\NextPreviousButtons::class,
		);
		if ( ! is_admin() ) {
			// These blocks have no individual editor scripts or override enqueue_data(), hence load eagerly in admin.
			$list['product-meta']            = \Automattic\WooCommerce\Blocks\BlockTypes\ProductMeta::class;
			$list['product-image-gallery']   = \Automattic\WooCommerce\Blocks\BlockTypes\ProductImageGallery::class;
			$list['product-image']           = \Automattic\WooCommerce\Blocks\BlockTypes\ProductImage::class;
			$list['product-stock-indicator'] = \Automattic\WooCommerce\Blocks\BlockTypes\ProductStockIndicator::class;
		}

		return $list;
	}

	/**
	 * Side effect free and stateless product grid blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_product_grid_blocks(): array {
		if ( ! is_admin() ) {
			// All inherit enqueue_data() from AbstractProductGrid with editor-visible theme support data.
			return array(
				'handpicked-products'   => \Automattic\WooCommerce\Blocks\BlockTypes\HandpickedProducts::class,
				'product-best-sellers'  => \Automattic\WooCommerce\Blocks\BlockTypes\ProductBestSellers::class,
				'product-new'           => \Automattic\WooCommerce\Blocks\BlockTypes\ProductNew::class,
				'product-on-sale'       => \Automattic\WooCommerce\Blocks\BlockTypes\ProductOnSale::class,
				'product-top-rated'     => \Automattic\WooCommerce\Blocks\BlockTypes\ProductTopRated::class,
				'products-by-attribute' => \Automattic\WooCommerce\Blocks\BlockTypes\ProductsByAttribute::class,
				'product-category'      => \Automattic\WooCommerce\Blocks\BlockTypes\ProductCategory::class,
				'product-tag'           => \Automattic\WooCommerce\Blocks\BlockTypes\ProductTag::class,
			);
		}

		return array();
	}

	/**
	 * Side effect free and stateless product listing blocks (shop, category, tag pages).
	 *
	 * @return array<string,string>
	 */
	private function get_product_listing_blocks(): array {
		// ProductCollection\Controller: excluded — query_loop_block_query_vars, pre_render_block, rest_api_init, render_block_data.
		// ProductQuery: excluded — query_vars, pre_render_block, rest_product_query, render_block_core/*.
		// ProductButton: excluded — hooks wp_enqueue_scripts in enqueue_assets() to dequeue legacy add-to-cart scripts.
		// ProductTemplate: excluded — block_type_metadata_settings (before parent — must run during other blocks' registration).
		// ProductResultsCount: excluded — custom get_block_type_style() with non-standard CSS path.
		$list = array(
			'catalog-sorting'               => \Automattic\WooCommerce\Blocks\BlockTypes\CatalogSorting::class,
			'category-title'                => \Automattic\WooCommerce\Blocks\BlockTypes\CategoryTitle::class,
			'category-description'          => \Automattic\WooCommerce\Blocks\BlockTypes\CategoryDescription::class,
			'product-collection-no-results' => \Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\NoResults::class,
		);
		if ( ! is_admin() ) {
			// AllProducts overrides enqueue_data() with editor-visible theme support data, hence loads eagerly in admin.
			$list['all-products'] = \Automattic\WooCommerce\Blocks\BlockTypes\AllProducts::class;
		}

		return $list;
	}

	/**
	 * Side effect free and stateless product review blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_product_reviews_blocks(): array {
		// AllReviews, ReviewsByCategory, ReviewsByProduct: excluded — override get_block_type_script() with a shared frontend handle.
		return array(
			'product-reviews'                     => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviews::class,
			'product-review-rating'               => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewRating::class,
			'product-reviews-title'               => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewsTitle::class,
			'product-review-form'                 => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewForm::class,
			'product-review-date'                 => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewDate::class,
			'product-review-content'              => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewContent::class,
			'product-review-author-name'          => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewAuthorName::class,
			'product-reviews-pagination'          => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewsPagination::class,
			'product-reviews-pagination-next'     => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewsPaginationNext::class,
			'product-reviews-pagination-previous' => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewsPaginationPrevious::class,
			'product-reviews-pagination-numbers'  => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewsPaginationNumbers::class,
			'product-review-template'             => \Automattic\WooCommerce\Blocks\BlockTypes\Reviews\ProductReviewTemplate::class,
		);
	}

	/**
	 * Side effect free and stateless cart and checkout blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_cart_and_checkout_blocks(): array {
		// CheckoutExpressPaymentBlock: excluded — save_post → express payment attr sync.
		// MiniCart: excluded — wp_loaded → patterns, hooked_block_types, render_block_context.
		$list = array(
			'mini-cart-contents' => \Automattic\WooCommerce\Blocks\BlockTypes\MiniCartContents::class,
		);
		if ( ! is_admin() ) {
			// PaymentMethodIcons overrides enqueue_data() with available payment methods, hence loads eagerly in admin.
			$list['payment-method-icons'] = \Automattic\WooCommerce\Blocks\BlockTypes\PaymentMethodIcons::class;
		}

		return $list;
	}

	/**
	 * Side effect free and stateless page-template blocks.
	 *
	 * @return array<string,string>
	 */
	private function get_dedicated_pages_blocks(): array {
		// SingleProduct: excluded — render_block_context, render_block_core/post-excerpt, render_block_core/post-title.
		// Cart: excluded — wp_loaded → patterns, wp → emoji disable.
		// Checkout: excluded — rest_api_init, wp_loaded → patterns, save_post, cart redirect filter.
		// ClassicTemplate: excluded — render_block filter, enqueue_block_assets.
		// ComingSoon: excluded — enqueue_block_assets filter.
		return array(
			'page-content-wrapper' => \Automattic\WooCommerce\Blocks\BlockTypes\PageContentWrapper::class,
		);
	}
}
