<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Error;

/**
 * Thrown when failing to serialize a leaf value.
 *
 * Not generally safe for clients, as the wrong given value could
 * be something not intended to ever be seen by clients.
 */
class SerializationError extends \Exception {}
