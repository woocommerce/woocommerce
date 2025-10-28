<?php
/**
 * Feed Validator class.
 *
 * @package WooCommerce\Internal\ProductCatalog
 * @since   10.4.0
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductCatalog;

use Automattic\WooCommerce\ProductCatalog\Interfaces\FeedValidatorInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Simple field validator for the product catalog.
 *
 * @internal This class is intended for internal use only and should not be used by extensions.
 * @package  WooCommerce\Internal\ProductCatalog
 */
final class FeedValidator implements FeedValidatorInterface {
	/**
	 * Validate single feed row using schema.
	 *
	 * @param array       $entry   Product data row to validate.
	 * @param \WC_Product $product The related product. Will be updated with validation status.
	 * @return array Array of validation issues.
	 */
	public function validate_entry( array $entry, \WC_Product $product ): array { //phpcs:ignore VariableAnalysis
		return array();
	}
}
