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
 * from ProductShapeMapperInterface.
 *
 * Existing implementations keep working unchanged and automatically satisfy
 * ProductShapeMapperInterface; they should migrate to implementing that
 * interface directly before this one is removed.
 *
 * @since 10.5.0
 * @since 11.0.0 Extends ProductShapeMapperInterface; the map_product() contract is inherited unchanged.
 * @deprecated 11.0.0 Implement Mapping\ProductShapeMapperInterface instead.
 */
interface ProductMapperInterface extends ProductShapeMapperInterface {
}
