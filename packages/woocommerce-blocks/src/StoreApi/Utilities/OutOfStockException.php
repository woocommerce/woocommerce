<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Utilities;

/**
 * OutOfStockException class.
 *
 * @internal This API is used internally by Blocks, this exception is thrown when an item in a draft order is out
 * of stock completely.
 */
class OutOfStockException extends StockAvailabilityException {

}
