<?php
declare( strict_types = 1);

defined( 'ABSPATH' ) || exit;
use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplate;
use Automattic\WooCommerce\Blocks\Templates\CartTemplate;
use Automattic\WooCommerce\Blocks\Templates\MiniCartTemplate;
use Automattic\WooCommerce\Blocks\Templates\CheckoutTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductCatalogTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductAttributeTemplate;
use Automattic\WooCommerce\Blocks\Templates\OrderConfirmationTemplate;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * This class adds actions to track usage of the Product Collection Block.
 */
class WC_Product_Collection_Block_Tracking {

	/**
	 * Init Tracking.
	 */
	public function init() {
		add_action( 'save_post', array( $this, 'track_collection_instances' ), 10, 2 );
	}

	/**
	 * Track feature usage of the Product Collection block within the site editor.
	 *
	 * @param int      $post_id  The post ID.
	 * @param \WP_Post $post     The post object.
	 *
	 * @return void
	 */
	public function track_collection_instances( $post_id, $post ) {

		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST || ! wp_is_block_theme() ) {
			return;
		}

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// Don't track autosaves and drafts.
		$post_status = $post->post_status;
		if ( 'publish' !== $post_status ) {
			return;
		}

		// Important: Only track instances within specific types.
		$post_type = $post->post_type;
		if ( ! in_array( $post_type, array( 'post', 'page', 'wp_template', 'wp_template_part', 'wp_block' ), true ) ) {
			return;
		}

		if ( ! has_block( 'woocommerce/product-collection', $post ) && ! has_block( 'core/template-part', $post ) && ! has_block( 'core/block', $post ) ) {
			return;
		}

		$blocks = parse_blocks( $post->post_content );
		if ( empty( $blocks ) ) {
			return;
		}

		$instances = $this->parse_blocks_track_data( $blocks );
		if ( empty( $instances ) ) {
			return;
		}

		// Count orders.
		// Hint: Product count included in Track event. See WC_Tracks::get_blog_details().
		$order_count = 0;
		foreach ( wc_get_order_statuses() as $status_slug => $status_name ) {
			$order_count += wc_orders_count( $status_slug );
		}
		$additional_data = array(
			'editor_context' => $this->parse_editor_location_context( $post ),
			'order_count'    => $order_count,
		);

		foreach ( $instances as $instance ) {

			$event_properties = array_merge(
				$additional_data,
				$instance
			);

			\WC_Tracks::record_event(
				'product_collection_instance',
				$event_properties
			);
		}
	}

	/**
	 * Track usage of the Product Collection block within the given blocks.
	 *
	 * @param array $blocks                The parsed blocks to check.
	 * @param bool  $is_in_single_product  Whether we are in a single product container (used for keeping state in the recurring process).
	 * @param bool  $is_in_template_part   Whether we are in a template part (used for keeping state in the recurring process).
	 * @param bool  $is_in_synced_pattern  Whether we are in a synced block (used for keeping state in the recurring process).
	 *
	 * @return array Parsed instances of the Product Collection block.
	 */
	private function parse_blocks_track_data( $blocks, $is_in_single_product = false, $is_in_template_part = false, $is_in_synced_pattern = false ) {

		$instances = array();

		if ( ! is_array( $blocks ) || empty( $blocks ) ) {
			return $instances;
		}

		foreach ( $blocks as $block ) {

			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( 'woocommerce/product-collection' === $block['blockName'] ) {
				$instances[] = array(
					'collection'        => $block['attrs']['collection'] ?? 'product-catalog',
					'in_single_product' => $is_in_single_product ? 'yes' : 'no',
					'in_template_part'  => $is_in_template_part ? 'yes' : 'no',
					'in_synced_pattern' => $is_in_synced_pattern ? 'yes' : 'no',
					'filters'           => wp_json_encode( $this->get_query_filters_usage_data( $block ), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ),
				);
			}

			// Track instances within single product container.
			$local_is_in_single_product = $is_in_single_product;
			if ( 'woocommerce/single-product' === $block['blockName'] ) {
				$local_is_in_single_product = true;
			}

			// Track instances within template part.
			// Hint: Supports up to two levels of depth.
			if ( ! $is_in_synced_pattern && ! $is_in_template_part && 'core/template-part' === $block['blockName'] ) {

				$template_part_theme = $block['attrs']['theme'] ?? '';
				$template_part_slug  = $block['attrs']['slug'] ?? '';
				$template_part       = get_block_template( $template_part_theme . '//' . $template_part_slug, 'wp_template_part' );
				if ( $template_part instanceof WP_Block_Template && ! empty( $template_part->content ) ) {
					// Recursive.
					$instances = array_merge( $instances, $this->parse_blocks_track_data( parse_blocks( $template_part->content ), $local_is_in_single_product, true, $is_in_synced_pattern ) );
				}
			}

			// Track instances within synced block.
			// Hint: Supports up to two levels of depth.
			if ( ! $is_in_synced_pattern && ! $is_in_template_part && 'core/block' === $block['blockName'] ) {

				$block_id       = $block['attrs']['ref'] ?? 0;
				$synced_pattern = get_post( $block_id );
				if ( $synced_pattern instanceof WP_Post && ! empty( $synced_pattern->post_content ) ) {
					// Recursive.
					$instances = array_merge( $instances, $this->parse_blocks_track_data( parse_blocks( $synced_pattern->post_content ), $local_is_in_single_product, $is_in_template_part, true ) );
				}
			}

			// Recursive.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$instances = array_merge( $instances, $this->parse_blocks_track_data( $block['innerBlocks'], $local_is_in_single_product, $is_in_template_part, $is_in_synced_pattern ) );
			}
		}

		return $instances;
	}

	/**
	 * Parse editor's location context from WP Post.
	 *
	 * Possible contexts:
	 * - post
	 * - page
	 * - single-product
	 * - product-archive
	 * - cart
	 * - checkout
	 * - product-catalog
	 * - order-confirmation
	 *
	 * @param WP_Post $post The Post instance.
	 *
	 * @return string Returns the context.
	 */
	private function parse_editor_location_context( $post ) {
		$context = 'other';

		if ( ! $post instanceof \WP_Post ) {
			return $context;
		}

		$post_type = $post->post_type;
		if ( ! in_array( $post_type, array( 'post', 'page', 'wp_template', 'wp_template_part', 'wp_block' ), true ) ) {
			return $context;
		}

		if ( 'wp_template' === $post_type ) {
			$name = $post->post_name;
			if ( false !== strpos( $name, SingleProductTemplate::SLUG ) ) {
				$context = 'single-product';
			} elseif ( ProductAttributeTemplate::SLUG === $name ) {
				$context = 'product-archive';
			} elseif ( false !== strpos( $name, 'taxonomy-' ) ) { // Including the '-' in the check to avoid false positives.
				$taxonomy           = str_replace( 'taxonomy-', '', $name );
				$product_taxonomies = get_object_taxonomies( 'product', 'names' );
				if ( in_array( $taxonomy, $product_taxonomies, true ) ) {
					$context = 'product-archive';
				}
			} elseif ( in_array( $name, array( CartTemplate::SLUG, MiniCartTemplate::SLUG ), true ) ) {
				$context = 'cart';
			} elseif ( CheckoutTemplate::SLUG === $name ) {
				$context = 'checkout';
			} elseif ( ProductCatalogTemplate::SLUG === $name ) {
				$context = 'product-catalog';
			} elseif ( OrderConfirmationTemplate::SLUG === $name ) {
				$context = 'order-confirmation';
			}
		}

		if ( in_array( $post_type, array( 'wp_block', 'wp_template_part' ), true ) ) {
			$context = 'isolated';
		}

		if ( 'page' === $post_type ) {
			$context = 'page';
		}
		if ( 'post' === $post_type ) {
			$context = 'post';
		}

		return $context;
	}

	/**
	 * Parse the collection query filters from the query attributes.
	 *
	 * @param array $block The parsed block.
	 * @return array The filters data for tracking.
	 */
	private function get_query_filters_usage_data( $block ) {

		if ( ! isset( $block['attrs'] ) ) {
			return array();
		}

		$query_attrs = $block['attrs']['query'] ?? array();
		$filters     = array(
			'inherit'      => 'no',
			'order-by'     => 'no',
			'on-sale'      => 'no',
			'stock-status' => 'no',
			'handpicked'   => 'no',
			'keyword'      => 'no',
			'attributes'   => 'no',
			'category'     => 'no',
			'tag'          => 'no',
			'featured'     => 'no',
			'created'      => 'no',
			'price'        => 'no',
		);

		if ( ! empty( $query_attrs['inherit'] ) && true === $query_attrs['inherit'] ) {
			$filters['inherit'] = 'yes';
		}

		if ( ( ! empty( $query_attrs['order'] ) && 'asc' !== $query_attrs['order'] ) || ( ! empty( $query_attrs['orderBy'] ) && 'title' !== $query_attrs['orderBy'] ) ) {
			$filters['order-by'] = 'yes';
		}

		if ( ! empty( $query_attrs['woocommerceOnSale'] ) ) {
			$filters['on-sale'] = 'yes';
		}

		if ( ! empty( $query_attrs['woocommerceStockStatus'] ) ) {
			$stock_statuses = wc_get_product_stock_status_options();
			$default_values = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ? array_diff_key( $stock_statuses, array( ProductStockStatus::OUT_OF_STOCK => '' ) ) : $stock_statuses;
			$default_diff   = array_diff( array_keys( $default_values ), $query_attrs['woocommerceStockStatus'] );
			if ( ! empty( $default_diff ) ) {
				$filters['stock-status'] = 'yes';
			}
		}

		if ( ! empty( $query_attrs['woocommerceAttributes'] ) ) {
			$filters['attributes'] = 'yes';
		}

		if ( ! empty( $query_attrs['timeFrame'] ) ) {
			$filters['created'] = 'yes';
		}

		if ( ! empty( $query_attrs['taxQuery'] ) ) {

			if ( ! empty( $query_attrs['taxQuery']['product_cat'] ) ) {
				$filters['category'] = 'yes';
			}

			if ( ! empty( $query_attrs['taxQuery']['product_tag'] ) ) {
				$filters['tag'] = 'yes';
			}
		}

		if ( ! empty( $query_attrs['woocommerceHandPickedProducts'] ) ) {
			$filters['handpicked'] = 'yes';
		}

		if ( ! empty( $query_attrs['search'] ) ) {
			$filters['keyword'] = 'yes';
		}

		if ( ! empty( $query_attrs['featured'] ) ) {
			$filters['featured'] = 'yes';
		}

		if ( ! empty( $query_attrs['priceRange'] ) ) {
			$filters['price'] = 'yes';
		}

		return $filters;
	}
}
