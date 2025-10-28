<?php
/**
 * Feed validator interface.
 *
 * @package WooCommerce\ProductCatalog
 * @since   10.4.0
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\ProductCatalog\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for validating catalog feed entries.
 *
 * @package WooCommerce\ProductCatalog
 */
interface FeedValidatorInterface {
	/**
	 * Validate a single entry.
	 *
	 * @param array       $row     The entry to validate.
	 * @param \WC_Product $product The related product. Will be updated with validation status.
	 * @return string[]            Validation issues.
	 */
	public function validate_entry( array $row, \WC_Product $product ): array;
}
