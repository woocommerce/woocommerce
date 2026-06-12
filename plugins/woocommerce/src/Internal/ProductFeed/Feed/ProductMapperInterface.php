<?php
/**
 * Product Mapper Interface.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

use Automattic\WooCommerce\Internal\ProductFeed\Mapping\ProductShapeMapperInterface;

/**
 * Product Mapper Interface.
 *
 * Push-feed flavor of the product-shape mapping contract: implementations map a
 * product to a feed row that is validated by a FeedValidatorInterface and written
 * to a FeedInterface. The mapping contract itself (map_product()) is inherited
 * from ProductShapeMapperInterface, which delivery-agnostic consumers should
 * type against instead.
 *
 * @since 10.5.0
 * @since 11.0.0 Extends ProductShapeMapperInterface; the map_product() contract is inherited unchanged.
 */
interface ProductMapperInterface extends ProductShapeMapperInterface {
}
