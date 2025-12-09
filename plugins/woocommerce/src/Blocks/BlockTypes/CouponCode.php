<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * CouponCode class.
 *
 * @since 10.5.0
 */
class CouponCode extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'coupon-code';

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string|null $key Data to get. Valid keys: "handle", "path", "dependencies". If null, returns the full script array.
	 * @return array|string|null Returns the full script array if $key is null, the specific entry if $key is provided, or null if the key doesn't exist.
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => [ 'wc-blocks' ],
		];
		return null === $key ? $script : ( $script[ $key ] ?? null );
	}
}
