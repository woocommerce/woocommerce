<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown when ShopperList::add_item() would exceed MAX_ITEMS. Internal message only.
 */
class ShopperListFullException extends \Exception {
}
