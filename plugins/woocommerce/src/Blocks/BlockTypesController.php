<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Admin\Features\Features;
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
	protected function init() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_categories' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'add_data_attributes' ), 10, 2 );
		add_action( 'woocommerce_login_form_end', array( $this, 'redirect_to_field' ) );
		add_filter( 'widget_types_to_hide_from_legacy_widget_block', array( $this, 'hide_legacy_widgets_with_block_equivalent' ) );
		add_action( 'woocommerce_delete_product_transients', array( $this, 'delete_product_transients' ) );
		add_filter(
			'woocommerce_is_checkout',
			function ( $ret ) {
				return $ret || $this->has_block_variation( 'woocommerce/classic-shortcode', 'shortcode', 'checkout' );
			}
		);
		add_filter(
			'woocommerce_is_cart',
			function ( $ret ) {
				return $ret || $this->has_block_variation( 'woocommerce/classic-shortcode', 'shortcode', 'cart' );
			}
		);
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
	 * Check if the current post has a block with a specific attribute value.
	 *
	 * @param string $block_id The block ID to check for.
	 * @param string $attribute The attribute to check.
	 * @param string $value The value to check for.
	 * @return boolean
	 */
	private function has_block_variation( $block_id, $attribute, $value ) {
		$post = get_post();

		if ( ! $post ) {
			return false;
		}

		if ( has_block( $block_id, $post->ID ) ) {
			$blocks = (array) parse_blocks( $post->post_content );

			foreach ( $blocks as $block ) {
				if ( isset( $block['attrs'][ $attribute ] ) && $value === $block['attrs'][ $attribute ] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Register blocks, hooking up assets and render functions as needed.
	 */
	public function register_blocks() {
		$block_types = $this->get_block_types();

		foreach ( $block_types as $block_type ) {
			$block_type_class = __NAMESPACE__ . '\\BlockTypes\\' . $block_type;

			new $block_type_class( $this->asset_api, $this->asset_data_registry, new IntegrationRegistry() );
		}
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

		$content = trim( $content );

		if ( ! $this->block_should_have_data_attributes( $block['blockName'] ) ) {
			return $content;
		}

		$attributes         = (array) $block['attrs'];
		$exclude_attributes = array( 'className', 'align' );

		$processor = new \WP_HTML_Tag_Processor( $content );

		if (
			false === $processor->next_token() ||
			'DIV' !== $processor->get_token_name() ||
			$processor->is_tag_closer()
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
	 * @return array $widget_types An array inluding the WooCommerce widgets to hide.
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
			'CatalogSorting',
			'ClassicTemplate',
			'ClassicShortcode',
			'ComingSoon',
			'CustomerAccount',
			'FeaturedCategory',
			'FeaturedProduct',
			'FilterWrapper',
			'HandpickedProducts',
			'MiniCart',
			'StoreNotices',
			'PriceFilter',
			'ProductBestSellers',
			'ProductButton',
			'ProductCategories',
			'ProductCategory',
			'ProductCollection',
			'ProductCollectionNoResults',
			'ProductGallery',
			'ProductGalleryLargeImage',
			'ProductGalleryLargeImageNextPrevious',
			'ProductGalleryPager',
			'ProductGalleryThumbnails',
			'ProductImage',
			'ProductImageGallery',
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
			'ProductReviews',
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
			'ProductDetails',
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
		);

		$block_types = array_merge(
			$block_types,
			Cart::get_cart_block_types(),
			Checkout::get_checkout_block_types(),
			MiniCartContents::get_mini_cart_block_types()
		);

		// Update plugins/woocommerce-blocks/docs/internal-developers/blocks/feature-flags-and-experimental-interfaces.md
		// when modifying this list.
		if ( Features::is_enabled( 'experimental-blocks' ) ) {
			$block_types[] = 'ProductFilter';
			$block_types[] = 'ProductFilters';
			$block_types[] = 'ProductFiltersOverlay';
			$block_types[] = 'ProductFiltersOverlayNavigation';
			$block_types[] = 'ProductFilterStockStatus';
			$block_types[] = 'ProductFilterPrice';
			$block_types[] = 'ProductFilterAttribute';
			$block_types[] = 'ProductFilterRating';
			$block_types[] = 'ProductFilterActive';
			$block_types[] = 'ProductFilterClearButton';
		}

		/**
		 * This disables specific blocks in Widget Areas by not registering them.
		 */
		if ( in_array( $pagenow, array( 'widgets.php', 'themes.php', 'customize.php' ), true ) && ( empty( $_GET['page'] ) || 'gutenberg-edit-site' !== $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$block_types = array_diff(
				$block_types,
				array(
					'AllProducts',
					'Cart',
					'Checkout',
					'ProductGallery',
				)
			);
		}

		/**
		 * This disables specific blocks in Post and Page editor by not registering them.
		 */
		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			$block_types = array_diff(
				$block_types,
				array(
					'AddToCartForm',
					'Breadcrumbs',
					'CatalogSorting',
					'ClassicTemplate',
					'ProductResultsCount',
					'ProductDetails',
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
					'ProductGallery',
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
}
