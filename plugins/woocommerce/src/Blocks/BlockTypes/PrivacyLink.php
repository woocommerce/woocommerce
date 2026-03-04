<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * PrivacyLink class.
 *
 * Inner block for PrivacyLinks — represents a single privacy link in the editor.
 * This block is purely an editor construct; frontend rendering is handled by PrivacyLinks.
 *
 * @since 10.7.0
 */
class PrivacyLink extends AbstractInnerBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'privacy-link';
}
