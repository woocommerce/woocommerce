<?php
/**
 *  Feed Validator class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog;

use Automattic\WooCommerce\Enums\ProductType;
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
		if ( ProductType::VARIATION === $product->get_type() ) {
			if ( has_term( 'pos-hidden', 'pos_product_visibility', $product->get_parent_id() ) ) {
				return array(
					'Parent product is hidden from the POS catalog',
				);
			}
		}
		return array();
	}
}
