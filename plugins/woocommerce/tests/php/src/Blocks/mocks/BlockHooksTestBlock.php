<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\Utils\BlockHooksTrait;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * Mock block.
 */
class BlockHooksTestBlock extends AbstractBlock {
	use BlockHooksTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'test-block';

	/**
	 * Block Hook API placements.
	 *
	 * @var array
	 */
	protected $hooked_block_placements = array(
		array(
			'position' => 'after',
			'anchor'   => 'core/navigation',
			'version'  => '8.4.0',
		),
	);

	/**
	 * Initialize our mock class.
	 */
	public function __construct() {
		parent::__construct(
			Package::container()->get( API::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry(),
		);
	}

	/**
	 * Initialize this block type.
	 */
	protected function initialize() {
		parent::initialize();
		add_filter( 'hooked_block_types', array( $this, 'register_hooked_block' ), 9, 4 );
	}

	/**
	 * Render block content
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		return 'I am a test block';
	}
}
