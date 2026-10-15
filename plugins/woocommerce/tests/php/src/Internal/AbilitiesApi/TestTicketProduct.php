<?php
/**
 * TestTicketProduct class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * The product class of the `ticket` type, as an extension defines it.
 */
class TestTicketProduct extends \WC_Product_Simple {

	/**
	 * The product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'ticket';
	}
}
