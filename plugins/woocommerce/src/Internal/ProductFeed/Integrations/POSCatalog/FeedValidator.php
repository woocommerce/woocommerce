<?php
/**
 *  Feed Validator class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog;

use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedValidatorInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple field validator for the POS catalog.
 *
 * @since 10.5.0
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
