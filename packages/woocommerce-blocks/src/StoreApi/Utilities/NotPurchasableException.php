<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Utilities;

/**
 * NotPurchasableException class.
 *
 * @internal This API is used internally by Blocks, this exception is thrown when an item in the cart is not able to be
 * purchased.
 */
class NotPurchasableException extends StockAvailabilityException {

}
