<?php

/**
 * WooCommerce Block Templates block interface compatibility shim.
 */

namespace Automattic\WooCommerce\Admin\BlockTemplates;

/**
 * Removed block templates block interface.
 *
 * @deprecated 10.9.0 Block template extension APIs were deprecated. The block templates API was removed in 11.0.0 with no replacement.
 */
interface BlockInterface {
	/**
	 * Key for the block name in the block configuration.
	 */
	public const NAME_KEY = 'blockName';

	/**
	 * Key for the block ID in the block configuration.
	 */
	public const ID_KEY = 'id';

	/**
	 * Key for the internal order in the block configuration.
	 */
	public const ORDER_KEY = 'order';

	/**
	 * Key for the block attributes in the block configuration.
	 */
	public const ATTRIBUTES_KEY = 'attributes';

	/**
	 * Key for the block hide conditions in the block configuration.
	 */
	public const HIDE_CONDITIONS_KEY = 'hideConditions';

	/**
	 * Key for the block disable conditions in the block configuration.
	 */
	public const DISABLE_CONDITIONS_KEY = 'disableConditions';
}
