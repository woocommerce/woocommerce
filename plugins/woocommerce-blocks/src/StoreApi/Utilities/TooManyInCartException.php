<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Utilities;

/**
 * TooManyInCartException class.
 *
 * @internal This API is used internally by Blocks, this exception is thrown when more than one of a product that
 * can only be purchased individually is in a cart.
 */
class TooManyInCartException extends StockAvailabilityException {

}
