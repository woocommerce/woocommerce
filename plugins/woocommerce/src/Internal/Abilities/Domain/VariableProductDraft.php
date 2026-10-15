<?php
/**
 * Variable product draft class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * A variable product that holds its unsaved variations until the create ability saves it.
 */
final class VariableProductDraft extends \WC_Product_Variable {

	/**
	 * Variations built in memory, saved after the parent.
	 *
	 * @var \WC_Product_Variation[]
	 */
	private array $pending_variations = array();

	/**
	 * Set the variations to save after the parent.
	 *
	 * @param \WC_Product_Variation[] $variations Variations.
	 */
	public function set_pending_variations( array $variations ): void {
		$this->pending_variations = $variations;
	}

	/**
	 * Get the variations to save after the parent.
	 *
	 * @return \WC_Product_Variation[]
	 */
	public function get_pending_variations(): array {
		return $this->pending_variations;
	}
}
