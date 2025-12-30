<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\Accordion;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\Utils\Utils;

/**
 * AccordionGroup class.
 */
class AccordionGroup extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'accordion-group';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		add_filter( 'block_type_metadata_settings', array( $this, 'add_accordion_block_type_metadata_settings' ), 10, 2 );
		parent::initialize();
	}

	/**
	 * Modify block type metadata settings to hide accordion blocks from inserter on WordPress 6.9 or later.
	 *
	 * @param array $settings Array of determined settings for registering a block type.
	 * @param array $metadata Metadata provided for registering a block type.
	 *
	 * @return array Modified settings.
	 */
	public function add_accordion_block_type_metadata_settings( $settings, $metadata ) {
		// Check if this is the accordion group block.
		if (
			! empty( $metadata['name'] ) &&
			'woocommerce/accordion-group' === $metadata['name']
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
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! $content ) {
			return $content;
		}

		$p = new \WP_HTML_Tag_Processor( $content );

		if ( $p->next_tag( array( 'class_name' => 'wp-block-woocommerce-accordion-group' ) ) ) {
			$interactivity_context = array(
				'autoclose' => $attributes['autoclose'],
				'isOpen'    => array(),
			);
			$p->set_attribute( 'data-wp-interactive', 'woocommerce/accordion' );
			$p->set_attribute( 'data-wp-context', wp_json_encode( $interactivity_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );

			// Only modify content if directives have been set.
			$content = $p->get_updated_html();
		}

		return $content;
	}
}
