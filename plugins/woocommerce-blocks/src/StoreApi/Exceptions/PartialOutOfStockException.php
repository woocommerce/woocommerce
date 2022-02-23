<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Exceptions;

/**
 * PartialOutOfStockException class.
 *
 * @internal This API is used internally by Blocks, this exception is thrown when an item in a draft order has a
 * quantity greater than what is available in stock.
 */
class PartialOutOfStockException extends StockAvailabilityException {}
