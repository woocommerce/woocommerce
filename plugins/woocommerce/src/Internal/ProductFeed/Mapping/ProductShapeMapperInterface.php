<?php
/**
 * Product Shape Mapper Interface.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Mapping;

/**
 * Minimal contract for mapping a WooCommerce product to an arbitrary array shape.
 *
 * This interface carries no delivery semantics: implementations may produce feed
 * rows, REST payloads, live query results, or any other shape. It can be consumed
 * by push-feed integrations (via the ProductWalker / FeedInterface machinery) and
 * by pull/live-query integrations alike, without taking a dependency on file or
 * CSV delivery.
 *
 * This interface supersedes the Feed namespace's ProductMapperInterface, which
 * extends it and is deprecated: all integrations, push-feed ones included, should
 * implement this interface directly.
 *
 * @since 11.0.0
 */
interface ProductShapeMapperInterface {
	/**
	 * Map a product to an array shape.
	 *
	 * @param \WC_Product $product The product to map.
	 * @return array The mapped product data.
	 */
	public function map_product( \WC_Product $product ): array;
}
