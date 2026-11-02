<?php
/**
 * WooCommerce Block Templates block container interface compatibility shim.
 */

namespace Automattic\WooCommerce\Admin\BlockTemplates;

/**
 * Removed block templates block container interface.
 *
 * @deprecated 10.9.0 Block template extension APIs were deprecated. The block templates API was removed in 11.0.0 with no replacement.
 */
interface BlockContainerInterface extends BlockInterface, ContainerInterface {}
