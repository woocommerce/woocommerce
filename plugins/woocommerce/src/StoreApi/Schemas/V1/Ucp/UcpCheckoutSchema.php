<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp;

/**
 * Cart schema the UCP checkout routes read from.
 *
 * A UCP checkout is a promoted cart, so it reads the same raw-string cart response as
 * {@see UcpCartSchema}; the UCP checkout mapper reshapes it.
 *
 * @since 11.4.0
 */
class UcpCheckoutSchema extends UcpCartSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'ucp-checkout';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-checkout';
}
