<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection;

use InvalidArgumentException;

/**
 * HandlerRegistry class.
 * Manages collection handlers.
 */
class HandlerRegistry {

	/**
	 * Associative array of collection handlers.
	 *
	 * @var array
	 */
	protected $collection_handler_store = [];

	/**
	 * Register handlers for a collection.
	 *
	 * @param string        $collection_name The name of the collection.
	 * @param callable      $build_query     The query builder callable.
	 * @param callable|null $frontend_args   Optional frontend args callable.
	 * @param callable|null $editor_args     Optional editor args callable.
	 * @param callable|null $preview_query   Optional preview query callable.
	 *
	 * @throws InvalidArgumentException If handlers are already registered for the collection.
	 */
	public function register_collection_handlers( $collection_name, $build_query, $frontend_args = null, $editor_args = null, $preview_query = null ) {
		if ( isset( $this->collection_handler_store[ $collection_name ] ) ) {
			throw new InvalidArgumentException( 'Collection handlers already registered for ' . esc_html( $collection_name ) );
		}

		$this->collection_handler_store[ $collection_name ] = [
			'build_query'   => $build_query,
			'frontend_args' => $frontend_args,
			'editor_args'   => $editor_args,
			'preview_query' => $preview_query,
		];

		return $this->collection_handler_store[ $collection_name ];
	}

	/**
	 * Register core collection handlers.
	 */
	public function register_core_collections() {
		$this->register_collection_handlers(
			'woocommerce/product-collection/hand-picked',
			function ( $collection_args, $common_query_values, $query ) {
				// For Hand-Picked collection, if no products are selected, we should return an empty result set.
				// This ensures that the collection doesn't display any products until the user explicitly chooses them.
				if ( empty( $query['handpicked_products'] ) ) {
					return array(
						'post__in' => array( -1 ),
					);
				}
			}
		);

		$this->register_collection_handlers(
			'woocommerce/product-collection/related',
			function ( $collection_args ) {
				// No products should be shown if no related product reference is set.
				if ( empty( $collection_args['relatedProductReference'] ) ) {
					return array(
						'post__in' => array( -1 ),
					);
				}

				$category_callback = function () use ( $collection_args ) {
					return $collection_args['relatedBy']['categories'];
				};

				$tag_callback = function () use ( $collection_args ) {
					return $collection_args['relatedBy']['tags'];
				};

				add_filter( 'woocommerce_product_related_posts_relate_by_category', $category_callback, PHP_INT_MAX );
				add_filter( 'woocommerce_product_related_posts_relate_by_tag', $tag_callback, PHP_INT_MAX );

				$related_products = wc_get_related_products(
					$collection_args['relatedProductReference'],
					// Use a higher limit so that the result set contains enough products for the collection to subsequently filter.
					100,
					array(),
					$collection_args['relatedBy']
				);

				remove_filter( 'woocommerce_product_related_posts_relate_by_category', $category_callback, PHP_INT_MAX );
				remove_filter( 'woocommerce_product_related_posts_relate_by_tag', $tag_callback, PHP_INT_MAX );

				if ( empty( $related_products ) ) {
					return array(
						'post__in' => array( -1 ),
					);
				}

				// Have it filter the results to products related to the one provided.
				return array(
					'post__in' => $related_products,
				);
			},
			function ( $collection_args, $query ) {
				$product_reference = $query['productReference'] ?? null;
				// Infer the product reference from the location if an explicit product is not set.
				if ( empty( $product_reference ) ) {
					$location = $collection_args['productCollectionLocation'];
					if ( isset( $location['type'] ) && 'product' === $location['type'] ) {
						$product_reference = $location['sourceData']['productId'];
					}
				}

				$collection_args['relatedProductReference'] = $product_reference;
				$collection_args['relatedBy']               = ! isset( $query['relatedBy'] ) ? array(
					'categories' => true,
					'tags'       => true,
				) : array(
					'categories' => isset( $query['relatedBy']['categories'] ) && true === $query['relatedBy']['categories'],
					'tags'       => isset( $query['relatedBy']['tags'] ) && true === $query['relatedBy']['tags'],
				);

				return $collection_args;
			},
			function ( $collection_args, $query, $request ) {
				$product_reference = $request->get_param( 'productReference' );
				// In some cases the editor will send along block location context that we can infer the product reference from.
				if ( empty( $product_reference ) ) {
					$location = $collection_args['productCollectionLocation'];
					if ( isset( $location['type'] ) && 'product' === $location['type'] ) {
						$product_reference = $location['sourceData']['productId'];
					}
				}

				$collection_args['relatedProductReference'] = $product_reference;

				$related_by                   = $request->get_param( 'relatedBy' );
				$collection_args['relatedBy'] = ! isset( $related_by ) ? array(
					'categories' => true,
					'tags'       => true,
				) : array(
					'categories' => rest_sanitize_boolean( $related_by['categories'] ?? false ),
					'tags'       => rest_sanitize_boolean( $related_by['tags'] ?? false ),
				);

				return $collection_args;
			}
		);

		$this->register_collection_handlers(
			'woocommerce/product-collection/upsells',
			function ( $collection_args ) {
				$product_reference = $collection_args['upsellsProductReferences'] ?? null;
				// No products should be shown if no upsells product reference is set.
				if ( empty( $product_reference ) ) {
					return array(
						'post__in' => array( -1 ),
					);
				}

				$products = array_map( 'wc_get_product', $product_reference );

				if ( empty( $products ) ) {
					return array(
						'post__in' => array( -1 ),
					);
				}

				$all_upsells = array_reduce(
					$products,
					function ( $acc, $product ) {
						return array_merge(
							$acc,
							$product->get_upsell_ids()
						);
					},
					array()
				);

				// Remove duplicates and product references. We don't want to display
				// what's already in cart.
				$unique_upsells = array_unique( $all_upsells );
				$upsells        = array_diff( $unique_upsells, $product_reference );

				return array(
					'post__in' => empty( $upsells ) ? array( -1 ) : $upsells,
				);
			},
			function ( $collection_args, $query ) {
				$product_references = isset( $query['productReference'] ) ? array( $query['productReference'] ) : null;
				// Infer the product reference from the location if an explicit product is not set.
				if ( empty( $product_references ) ) {
					$location = $collection_args['productCollectionLocation'];
					if ( isset( $location['type'] ) && 'product' === $location['type'] ) {
						$product_references = array( $location['sourceData']['productId'] );
					}

					if ( isset( $location['type'] ) && 'cart' === $location['type'] ) {
						$product_references = $location['sourceData']['productIds'];
					}

					if ( isset( $location['type'] ) && 'order' === $location['type'] ) {
						$product_references = $this->get_product_ids_from_order( $location['sourceData']['orderId'] ?? 0 );
					}
				}

				$collection_args['upsellsProductReferences'] = $product_references;
				return $collection_args;
			},
			function ( $collection_args, $query, $request ) {
				$product_reference = $request->get_param( 'productReference' );
				// In some cases the editor will send along block location context that we can infer the product reference from.
				if ( empty( $product_reference ) ) {
					$location = $collection_args['productCollectionLocation'];
					if ( isset( $location['type'] ) && 'product' === $location['type'] ) {
						$product_reference = $location['sourceData']['productId'];
					}
				}

				$collection_args['upsellsProductReferences'] = array( $product_reference );
				return $collection_args;
			}
		);

		$this->register_collection_handlers(
			'woocommerce/product-collection/cross-sells',
			function ( $collection_args ) {
				$product_reference = $collection_args['crossSellsProductReferences'] ?? null;
				// No products should be shown if no cross-sells product reference is set.
				if ( empty( $product_reference ) ) {
					return array(
						'post__in' => array( -1 ),
					);
				}

				$products = array_filter( array_map( 'wc_get_product', $product_reference ) );

				if ( empty( $products ) ) {
					return array(
						'post__in' => array( -1 ),
					);
				}

				$product_ids = array_map(
					function ( $product ) {
						return $product->get_id();
					},
					$products
				);

				$all_cross_sells = array_reduce(
					$products,
					function ( $acc, $product ) {
						return array_merge(
							$acc,
							$product->get_cross_sell_ids()
						);
					},
					array()
				);

				// Remove duplicates and product references. We don't want to display
				// what's already in cart.
				$unique_cross_sells = array_unique( $all_cross_sells );
				$cross_sells        = array_diff( $unique_cross_sells, $product_ids );

				return array(
					'post__in' => empty( $cross_sells ) ? array( -1 ) : $cross_sells,
				);
			},
			function ( $collection_args, $query ) {
				$product_references = isset( $query['productReference'] ) ? array( $query['productReference'] ) : null;
				// Infer the product reference from the location if an explicit product is not set.
				if ( empty( $product_references ) ) {
					$location = $collection_args['productCollectionLocation'];
					if ( isset( $location['type'] ) && 'product' === $location['type'] ) {
						$product_references = array( $location['sourceData']['productId'] );
					}

					if ( isset( $location['type'] ) && 'cart' === $location['type'] ) {
						$product_references = $location['sourceData']['productIds'];
					}

					if ( isset( $location['type'] ) && 'order' === $location['type'] ) {
						$product_references = $this->get_product_ids_from_order( $location['sourceData']['orderId'] ?? 0 );
					}
				}

				$collection_args['crossSellsProductReferences'] = $product_references;
				return $collection_args;
			},
			function ( $collection_args, $query, $request ) {
				$product_reference = $request->get_param( 'productReference' );
				// In some cases the editor will send along block location context that we can infer the product reference from.
				if ( empty( $product_reference ) ) {
					$location = $collection_args['productCollectionLocation'];
					if ( isset( $location['type'] ) && 'product' === $location['type'] ) {
						$product_reference = $location['sourceData']['productId'];
					}
				}

				$collection_args['crossSellsProductReferences'] = array( $product_reference );
				return $collection_args;
			}
		);
		return $this->collection_handler_store;
	}

	/**
	 * Get collection handler by name.
	 *
	 * @param string $name Collection name.
	 * @return array|null Collection handler array or null if not found.
	 */
	public function get_collection_handler( $name ) {
		return $this->collection_handler_store[ $name ] ?? null;
	}

	/**
	 * Removes any custom collection handlers for the given collection.
	 *
	 * @param string $collection_name The name of the collection to unregister.
	 */
	public function unregister_collection_handlers( $collection_name ) {
		unset( $this->collection_handler_store[ $collection_name ] );
	}


	/**
	 * Get product IDs from an order.
	 *
	 * @param int $order_id The order ID.
	 * @return array<int> The product IDs.
	 */
	private function get_product_ids_from_order( $order_id ) {
		$product_references = array();
		if ( empty( $order_id ) ) {
			return $product_references;
		}

		$order = wc_get_order( $order_id );
		if ( $order ) {
			$product_references = array_filter(
				array_map(
					function ( $item ) {
						return $item->get_product_id();
					},
					$order->get_items( 'line_item' )
				)
			);
		}
		return $product_references;
	}
}
