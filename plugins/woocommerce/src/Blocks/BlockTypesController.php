<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\Cart;
use Automattic\WooCommerce\Blocks\BlockTypes\Checkout;
use Automattic\WooCommerce\Blocks\BlockTypes\MiniCartContents;

/**
 * BlockTypesController class.
 *
 * @since 5.0.0
 * @internal
 */
final class BlockTypesController {

	/**
	 * Instance of the asset API.
	 *
	 * @var AssetApi
	 */
	protected $asset_api;

	/**
	 * Instance of the asset data registry.
	 *
	 * @var AssetDataRegistry
	 */
	protected $asset_data_registry;

	/**
	 * Holds the registered blocks that have WooCommerce blocks as their parents.
	 *
	 * @var array List of registered blocks.
	 */
	private $registered_blocks_with_woocommerce_parents;

	/**
	 * Constructor.
	 *
	 * @param AssetApi          $asset_api Instance of the asset API.
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetApi $asset_api, AssetDataRegistry $asset_data_registry ) {
		$this->asset_api           = $asset_api;
		$this->asset_data_registry = $asset_data_registry;
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() { // phpcs:ignore WooCommerce.Functions.InternalInjectionMethod.MissingPublic
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'wp_loaded', array( $this, 'register_block_patterns' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_categories' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'add_data_attributes' ), 10, 2 );
		add_action( 'woocommerce_login_form_end', array( $this, 'redirect_to_field' ) );
		add_filter( 'widget_types_to_hide_from_legacy_widget_block', array( $this, 'hide_legacy_widgets_with_block_equivalent' ) );
		add_action( 'woocommerce_delete_product_transients', array( $this, 'delete_product_transients' ) );
		add_filter( 'register_block_type_args', array( $this, 'enqueue_block_style_for_classic_themes' ), 10, 2 );
	}

	/**
	 * Get registered blocks that have WooCommerce blocks as their parents. Adds the value to the
	 * `registered_blocks_with_woocommerce_parents` cache if `init` has been fired.
	 *
	 * @return array Registered blocks with WooCommerce blocks as parents.
	 */
	public function get_registered_blocks_with_woocommerce_parent() {
		// If init has run and the cache is already set, return it.
		if ( did_action( 'init' ) && ! empty( $this->registered_blocks_with_woocommerce_parents ) ) {
			return $this->registered_blocks_with_woocommerce_parents;
		}

		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

		if ( ! is_array( $registered_blocks ) ) {
			return array();
		}

		$this->registered_blocks_with_woocommerce_parents = array_filter(
			$registered_blocks,
			function ( $block ) {
				if ( empty( $block->parent ) ) {
					return false;
				}
				if ( ! is_array( $block->parent ) ) {
					$block->parent = array( $block->parent );
				}
				$woocommerce_blocks = array_filter(
					$block->parent,
					function ( $parent_block_name ) {
						return 'woocommerce' === strtok( $parent_block_name, '/' );
					}
				);
				return ! empty( $woocommerce_blocks );
			}
		);
		return $this->registered_blocks_with_woocommerce_parents;
	}

	/**
	 * Register blocks, hooking up assets and render functions as needed.
	 */
	public function register_blocks() {
		$this->register_block_metadata();
		$block_types = $this->get_block_types();

		foreach ( $block_types as $block_type ) {
			$block_type_class = __NAMESPACE__ . '\\BlockTypes\\' . $block_type;

			new $block_type_class( $this->asset_api, $this->asset_data_registry, new IntegrationRegistry() );
		}
	}

	/**
	 * Register block metadata collections for WooCommerce blocks.
	 *
	 * This method handles the registration of block metadata by using WordPress's block metadata
	 * collection registration system. It includes a temporary workaround for WordPress 6.7's
	 * strict path validation that might fail for sites using symlinked plugins.
	 *
	 * If the registration fails due to path validation, blocks will fall back to regular
	 * registration without affecting functionality.
	 */
	public function register_block_metadata() {
		$meta_file_path = WC_ABSPATH . 'assets/client/blocks/blocks-json.php';
		if ( function_exists( 'wp_register_block_metadata_collection' ) && file_exists( $meta_file_path ) ) {
			add_filter( 'doing_it_wrong_trigger_error', array( __CLASS__, 'bypass_block_metadata_doing_it_wrong' ), 10, 4 );
			wp_register_block_metadata_collection(
				WC_ABSPATH . 'assets/client/blocks/',
				$meta_file_path
			);
			remove_filter( 'doing_it_wrong_trigger_error', array( __CLASS__, 'bypass_block_metadata_doing_it_wrong' ), 10 );
		}
	}

	/**
	 * Temporarily bypasses _doing_it_wrong() notices for block metadata collection registration.
	 *
	 * WordPress 6.7 introduced block metadata collections (with strict path validation).
	 * Any sites using symlinks for plugins will fail the validation which causes the metadata
	 * collection to not be registered. However, the blocks will still fall back to the regular
	 * registration and no functionality is affected.
	 * While this validation is being discussed in WordPress Core (#62140),
	 * this method allows registration to proceed by temporarily disabling
	 * the relevant notice.
	 *
	 * @param bool   $trigger       Whether to trigger the error.
	 * @param string $function      The function that was called.
	 * @param string $message       A message explaining what was done incorrectly.
	 * @param string $version       The version of WordPress where the message was added.
	 * @return bool Whether to trigger the error.
	 */
	public static function bypass_block_metadata_doing_it_wrong( $trigger, $function, $message, $version ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable,Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed,Universal.NamingConventions.NoReservedKeywordParameterNames.functionFound
		if ( 'WP_Block_Metadata_Registry::register_collection' === $function ) {
			return false;
		}
		return $trigger;
	}

	/**
	 * Register block patterns
	 */
	public function register_block_patterns() {
		register_block_pattern(
			'woocommerce/order-confirmation-totals-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"24px"}}} --><h2 class="wp-block-heading" style="font-size:24px">' . esc_html__( 'Order details', 'woocommerce' ) . '</h2><!-- /wp:heading -->',
			)
		);
		register_block_pattern(
			'woocommerce/order-confirmation-downloads-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"24px"}}} --><h2 class="wp-block-heading" style="font-size:24px">' . esc_html__( 'Downloads', 'woocommerce' ) . '</h2><!-- /wp:heading -->',
			)
		);
		register_block_pattern(
			'woocommerce/order-confirmation-shipping-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"24px"}}} --><h2 class="wp-block-heading" style="font-size:24px">' . esc_html__( 'Shipping address', 'woocommerce' ) . '</h2><!-- /wp:heading -->',
			)
		);
		register_block_pattern(
			'woocommerce/order-confirmation-billing-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"24px"}}} --><h2 class="wp-block-heading" style="font-size:24px">' . esc_html__( 'Billing address', 'woocommerce' ) . '</h2><!-- /wp:heading -->',
			)
		);
		register_block_pattern(
			'woocommerce/order-confirmation-additional-fields-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"24px"}}} --><h2 class="wp-block-heading" style="font-size:24px">' . esc_html__( 'Additional information', 'woocommerce' ) . '</h2><!-- /wp:heading -->',
			)
		);
	}

	/**
	 * Register block categories
	 *
	 * Used in combination with the `block_categories_all` filter, to append
	 * WooCommerce Blocks related categories to the Gutenberg editor.
	 *
	 * @param array $categories The array of already registered categories.
	 */
	public function register_block_categories( $categories ) {
		$woocommerce_block_categories = array(
			array(
				'slug'  => 'woocommerce',
				'title' => __( 'WooCommerce', 'woocommerce' ),
			),
			array(
				'slug'  => 'woocommerce-product-elements',
				'title' => __( 'WooCommerce Product Elements', 'woocommerce' ),
			),
		);

		return array_merge( $categories, $woocommerce_block_categories );
	}

	/**
	 * Check if a block should have data attributes appended on render. If it's in an allowed namespace, or the block
	 * has explicitly been added to the allowed block list, or if one of the block's parents is in the WooCommerce
	 * namespace it can have data attributes.
	 *
	 * @param string $block_name Name of the block to check.
	 *
	 * @return boolean
	 */
	public function block_should_have_data_attributes( $block_name ) {
		$block_namespace = strtok( $block_name ?? '', '/' );

		/**
		 * Filters the list of allowed block namespaces.
		 *
		 * This hook defines which block namespaces should have block name and attribute `data-` attributes appended on render.
		 *
		 * @since 5.9.0
		 *
		 * @param array $allowed_namespaces List of namespaces.
		 */
		$allowed_namespaces = array_merge( array( 'woocommerce', 'woocommerce-checkout' ), (array) apply_filters( '__experimental_woocommerce_blocks_add_data_attributes_to_namespace', array() ) );

		/**
		 * Filters the list of allowed Block Names
		 *
		 * This hook defines which block names should have block name and attribute data- attributes appended on render.
		 *
		 * @since 5.9.0
		 *
		 * @param array $allowed_namespaces List of namespaces.
		 */
		$allowed_blocks = (array) apply_filters( '__experimental_woocommerce_blocks_add_data_attributes_to_block', array() );

		$blocks_with_woo_parents   = $this->get_registered_blocks_with_woocommerce_parent();
		$block_has_woo_parent      = in_array( $block_name, array_keys( $blocks_with_woo_parents ), true );
		$in_allowed_namespace_list = in_array( $block_namespace, $allowed_namespaces, true );
		$in_allowed_block_list     = in_array( $block_name, $allowed_blocks, true );

		return $block_has_woo_parent || $in_allowed_block_list || $in_allowed_namespace_list;
	}

	/**
	 * Add data- attributes to blocks when rendered if the block is under the woocommerce/ namespace.
	 *
	 * @param string $content Block content.
	 * @param array  $block Parsed block data.
	 * @return string
	 */
	public function add_data_attributes( $content, $block ) {

		if ( ! is_string( $content ) || ! $this->block_should_have_data_attributes( $block['blockName'] ) ) {
			return $content;
		}

		$attributes         = (array) $block['attrs'];
		$exclude_attributes = array( 'className', 'align' );

		$processor = new \WP_HTML_Tag_Processor( $content );

		if (
			false === $processor->next_tag() || $processor->is_tag_closer()
		) {

			return $content;
		}

		foreach ( $attributes as $key  => $value ) {
			if ( ! is_string( $key ) || in_array( $key, $exclude_attributes, true ) ) {
				continue;
			}
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			if ( ! is_scalar( $value ) ) {
				$value = wp_json_encode( $value );
			}

			// For output consistency, we convert camelCase to kebab-case and output in lowercase.
			$key = strtolower( preg_replace( '/(?<!^|\ )[A-Z]/', '-$0', $key ) );

			$processor->set_attribute( "data-{$key}", $value );
		}

		// Set this last to prevent user-input from overriding it.
		$processor->set_attribute( 'data-block-name', $block['blockName'] );
		return $processor->get_updated_html();
	}

	/**
	 * Adds a redirect field to the login form so blocks can redirect users after login.
	 */
	public function redirect_to_field() {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['redirect_to'] ) ) {
			return;
		}
		echo '<input type="hidden" name="redirect" value="' . esc_attr( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Hide legacy widgets with a feature complete block equivalent in the inserter
	 * and prevent them from showing as an option in the Legacy Widget block.
	 *
	 * @param array $widget_types An array of widgets hidden in core.
	 * @return array $widget_types An array including the WooCommerce widgets to hide.
	 */
	public function hide_legacy_widgets_with_block_equivalent( $widget_types ) {
		array_push(
			$widget_types,
			'woocommerce_product_search',
			'woocommerce_product_categories',
			'woocommerce_recent_reviews',
			'woocommerce_product_tag_cloud',
			'woocommerce_price_filter',
			'woocommerce_layered_nav',
			'woocommerce_layered_nav_filters',
			'woocommerce_rating_filter'
		);

		return $widget_types;
	}

	/**
	 * Delete product transients when a product is deleted.
	 */
	public function delete_product_transients() {
		delete_transient( 'wc_blocks_has_downloadable_product' );
	}

	/**
	 * Get list of block types allowed in Widget Areas. New blocks won't be
	 * exposed in the Widget Area unless specifically added here.
	 *
	 * @return array Array of block types.
	 */
	protected function get_widget_area_block_types() {
		return array(
			'AllReviews',
			'Breadcrumbs',
			'CartLink',
			'CatalogSorting',
			'ClassicShortcode',
			'CustomerAccount',
			'FeaturedCategory',
			'FeaturedProduct',
			'MiniCart',
			'ProductCategories',
			'ProductResultsCount',
			'ProductSearch',
			'ReviewsByCategory',
			'ReviewsByProduct',
			'ProductFilters',
			'ProductFilterStatus',
			'ProductFilterPrice',
			'ProductFilterPriceSlider',
			'ProductFilterAttribute',
			'ProductFilterRating',
			'ProductFilterActive',
			'ProductFilterRemovableChips',
			'ProductFilterClearButton',
			'ProductFilterCheckboxList',
			'ProductFilterChips',
			'ProductFilterTaxonomy',

			// Keep hidden legacy filter blocks for backward compatibility.
			'ActiveFilters',
			'AttributeFilter',
			'FilterWrapper',
			'PriceFilter',
			'RatingFilter',
			'StockFilter',
			// End: legacy filter blocks.

			// Below product grids are hidden from inserter however they could have been used in widgets.
			// Keep them for backward compatibility.
			'HandpickedProducts',
			'ProductBestSellers',
			'ProductNew',
			'ProductOnSale',
			'ProductTopRated',
			'ProductsByAttribute',
			'ProductCategory',
			'ProductTag',
			// End: legacy product grids blocks.
		);
	}

	/**
	 * Get list of block types.
	 *
	 * @return array
	 */
	protected function get_block_types() {
		global $pagenow;

		$block_types = array(
			'ActiveFilters',
			'AddToCartForm',
			'AllProducts',
			'AllReviews',
			'AttributeFilter',
			'Breadcrumbs',
			'CartLink',
			'CatalogSorting',
			'ClassicTemplate',
			'ClassicShortcode',
			'ComingSoon',
			'CustomerAccount',
			'EmailContent',
			'FeaturedCategory',
			'FeaturedProduct',
			'FilterWrapper',
			'HandpickedProducts',
			'MiniCart',
			'NextPreviousButtons',
			'StoreNotices',
			'PaymentMethodIcons',
			'PriceFilter',
			'ProductBestSellers',
			'ProductButton',
			'ProductCategories',
			'ProductCategory',
			'ProductCollection\Controller',
			'ProductCollection\NoResults',
			'ProductFilters',
			'ProductFilterStatus',
			'ProductFilterPrice',
			'ProductFilterPriceSlider',
			'ProductFilterAttribute',
			'ProductFilterRating',
			'ProductFilterActive',
			'ProductFilterRemovableChips',
			'ProductFilterClearButton',
			'ProductFilterCheckboxList',
			'ProductFilterChips',
			'ProductFilterTaxonomy',
			'ProductGallery',
			'ProductGalleryLargeImage',
			'ProductGalleryThumbnails',
			'ProductImage',
			'ProductImageGallery',
			'ProductMeta',
			'ProductNew',
			'ProductOnSale',
			'ProductPrice',
			'ProductTemplate',
			'ProductQuery',
			'ProductAverageRating',
			'ProductRating',
			'ProductRatingCounter',
			'ProductRatingStars',
			'ProductResultsCount',
			'ProductSaleBadge',
			'ProductSearch',
			'ProductSKU',
			'ProductStockIndicator',
			'ProductSummary',
			'ProductTag',
			'ProductTitle',
			'ProductTopRated',
			'ProductsByAttribute',
			'RatingFilter',
			'ReviewsByCategory',
			'ReviewsByProduct',
			'RelatedProducts',
			'SingleProduct',
			'StockFilter',
			'PageContentWrapper',
			'OrderConfirmation\Status',
			'OrderConfirmation\Summary',
			'OrderConfirmation\Totals',
			'OrderConfirmation\TotalsWrapper',
			'OrderConfirmation\Downloads',
			'OrderConfirmation\DownloadsWrapper',
			'OrderConfirmation\BillingAddress',
			'OrderConfirmation\ShippingAddress',
			'OrderConfirmation\BillingWrapper',
			'OrderConfirmation\ShippingWrapper',
			'OrderConfirmation\AdditionalInformation',
			'OrderConfirmation\AdditionalFieldsWrapper',
			'OrderConfirmation\AdditionalFields',
			'OrderConfirmation\CreateAccount',
			'ProductDetails',
			'ProductDescription',
			'ProductSpecifications',
			// Generic blocks that will be pushed upstream.
			'Accordion\AccordionGroup',
			'Accordion\AccordionItem',
			'Accordion\AccordionPanel',
			'Accordion\AccordionHeader',
			// End: generic blocks that will be pushed upstream.
			'Reviews\ProductReviews',
			'Reviews\ProductReviewRating',
			'Reviews\ProductReviewsTitle',
			'Reviews\ProductReviewForm',
			'Reviews\ProductReviewDate',
			'Reviews\ProductReviewContent',
			'Reviews\ProductReviewAuthorName',
			'Reviews\ProductReviewsPagination',
			'Reviews\ProductReviewsPaginationNext',
			'Reviews\ProductReviewsPaginationPrevious',
			'Reviews\ProductReviewsPaginationNumbers',
			'Reviews\ProductReviewTemplate',
		);

		$block_types = array_merge(
			$block_types,
			Cart::get_cart_block_types(),
			Checkout::get_checkout_block_types(),
			MiniCartContents::get_mini_cart_block_types()
		);

		if ( wp_is_block_theme() ) {
			$block_types[] = 'AddToCartWithOptions\AddToCartWithOptions';
			$block_types[] = 'AddToCartWithOptions\QuantitySelector';
			$block_types[] = 'AddToCartWithOptions\VariationDescription';
			$block_types[] = 'AddToCartWithOptions\VariationSelector';
			$block_types[] = 'AddToCartWithOptions\VariationSelectorAttribute';
			$block_types[] = 'AddToCartWithOptions\VariationSelectorAttributeName';
			$block_types[] = 'AddToCartWithOptions\VariationSelectorAttributeOptions';
			$block_types[] = 'AddToCartWithOptions\GroupedProductSelector';
			$block_types[] = 'AddToCartWithOptions\GroupedProductItem';
			$block_types[] = 'AddToCartWithOptions\GroupedProductItemSelector';
			$block_types[] = 'AddToCartWithOptions\GroupedProductItemLabel';
		}

		/**
		 * This enables specific blocks in Widget Areas using an opt-in approach.
		 */
		if ( in_array( $pagenow, array( 'widgets.php', 'themes.php', 'customize.php' ), true ) && ( empty( $_GET['page'] ) || 'gutenberg-edit-site' !== $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$block_types = array_intersect(
				$block_types,
				$this->get_widget_area_block_types()
			);
		}

		/**
		 * This disables specific blocks in Post and Page editor by not registering them.
		 */
		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			$block_types = array_diff(
				$block_types,
				array(
					'Breadcrumbs',
					'CatalogSorting',
					'ClassicTemplate',
					'ProductResultsCount',
					'ProductReviews',
					'OrderConfirmation\Status',
					'OrderConfirmation\Summary',
					'OrderConfirmation\Totals',
					'OrderConfirmation\TotalsWrapper',
					'OrderConfirmation\Downloads',
					'OrderConfirmation\DownloadsWrapper',
					'OrderConfirmation\BillingAddress',
					'OrderConfirmation\ShippingAddress',
					'OrderConfirmation\BillingWrapper',
					'OrderConfirmation\ShippingWrapper',
					'OrderConfirmation\AdditionalInformation',
					'OrderConfirmation\AdditionalFieldsWrapper',
					'OrderConfirmation\AdditionalFields',
				)
			);
		}

		/**
		 * Filters the list of allowed block types.
		 *
		 * @since 9.0.0
		 *
		 * @param array $block_types List of block types.
		 */
		return apply_filters( 'woocommerce_get_block_types', $block_types );
	}

	/**
	 * By default, when the classic theme is used, block style is always
	 * enqueued even if the block is not used on the page. We want WooCommerce
	 * store to always performant so we have to manually enqueue the block style
	 * on-demand for classic themes.
	 *
	 * @internal
	 *
	 * @param array  $args Block metadata.
	 * @param string $block_name Block name.
	 *
	 * @return array Block metadata.
	 */
	public function enqueue_block_style_for_classic_themes( $args, $block_name ) {

		// Repeatedly checking the theme is expensive. So statically cache this logic result and remove the filter if not needed.
		static $should_enqueue_block_style_for_classic_themes = null;
		if ( null === $should_enqueue_block_style_for_classic_themes ) {
			$should_enqueue_block_style_for_classic_themes = ! (
				is_admin() ||
				wp_is_block_theme() ||
				( function_exists( 'wp_should_load_block_assets_on_demand' ) && wp_should_load_block_assets_on_demand() ) ||
				wp_should_load_separate_core_block_assets()
			);
		}
		if ( ! $should_enqueue_block_style_for_classic_themes ) {
			remove_filter( 'register_block_type_args', array( $this, 'enqueue_block_style_for_classic_themes' ), 10 );

			return $args;
		}

		if (
			false === strpos( $block_name, 'woocommerce/' ) ||
			( empty( $args['style_handles'] ) && empty( $args['style'] )
			)
		) {
			return $args;
		}

		$style_handlers = $args['style_handles'] ?? $args['style'];

		add_filter(
			'render_block_' . $block_name,
			static function ( $html ) use ( $style_handlers ) {
				array_map( 'wp_enqueue_style', $style_handlers );

				return $html;
			},
			10
		);

		$args['style_handles'] = array();
		$args['style']         = array();

		return $args;
	}
}
