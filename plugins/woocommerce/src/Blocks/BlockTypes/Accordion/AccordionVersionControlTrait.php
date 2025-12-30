<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\Accordion;

use Automattic\WooCommerce\Blocks\Utils\Utils;

/**
 * Trait for handling version-specific behavior for Accordion blocks.
 *
 * This trait provides common functionality to hide accordion blocks from the inserter
 * on WordPress 6.9 or later.
 */
trait AccordionVersionControlTrait {

	/**
	 * Initialize version-specific behavior for accordion blocks.
	 *
	 * This method should be called in the block's initialize() method.
	 *
	 * @since 10.4.3
	 */
	protected function initialize_accordion_version_control() {
		add_filter( 'block_type_metadata_settings', array( $this, 'add_accordion_block_type_metadata_settings' ), 10, 2 );
	}

	/**
	 * Modify block type metadata settings to hide accordion blocks from inserter on WordPress 6.9 or later.
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata Metadata provided for registering a block type.
	 *
	 * @since 10.4.3
	 *
	 * @return array Modified settings.
	 */
	public function add_accordion_block_type_metadata_settings( $settings, $metadata ) {
		// Check if this is one of our accordion blocks.
		if (
			! empty( $metadata['name'] ) &&
			$this->is_accordion_block( $metadata['name'] )
		) :
			// Hide the accordion block from the inserter on WordPress 6.9 or later.
			if ( Utils::wp_version_compare( '6.9', '>=' ) ) :
				// Ensure supports array exists.
				if ( ! isset( $settings['supports'] ) ) :
					$settings['supports'] = array();
				endif;

				// Hide from inserter.
				$settings['supports']['inserter'] = false;
			endif;
		endif;

		return $settings;
	}

	/**
	 * Check if the given block name is an accordion block.
	 *
	 * @param string $block_name The block name to check.
	 *
	 * @since 10.4.3
	 *
	 * @return bool True if it's an accordion block, false otherwise.
	 */
	private function is_accordion_block( $block_name ) {
		$accordion_blocks = array(
			'woocommerce/accordion-group',
			'woocommerce/accordion-item',
			'woocommerce/accordion-header',
			'woocommerce/accordion-panel',
		);

		return in_array( $block_name, $accordion_blocks, true );
	}
}
