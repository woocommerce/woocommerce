<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Link types as defined in the Agentic Commerce Protocol.
 */
class LinkType {
	/**
	 * Terms of use/service.
	 */
	const TERMS_OF_USE = 'terms_of_use';

	/**
	 * Privacy policy.
	 */
	const PRIVACY_POLICY = 'privacy_policy';

	/**
	 * Seller shop policies.
	 */
	const SELLER_SHOP_POLICIES = 'seller_shop_policies';
}
