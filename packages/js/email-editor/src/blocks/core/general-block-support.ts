/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import {
	Block as WPBlock,
	BlockSupports as WPBlockSupports,
} from '@wordpress/blocks/index';

// Extend the BlockSupports type to include shadow
// The shadow is not included in WP6.4 but it is in WP6.5
// We can remove it once we upgrade packages to WP6.5
type BlockSupports = WPBlockSupports & { shadow: boolean };
type Block = WPBlock & { supports?: BlockSupports };

/**
 * Disables Shadow Support for all blocks
 * Currently we are not able to read these styles in renderer
 */
function alterSupportConfiguration() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/block-support',
		( settings: Block ) => {
			if ( settings.supports?.shadow ) {
				return {
					...settings,
					supports: { ...settings.supports, shadow: false },
				};
			}
			return settings;
		}
	);
}

export { alterSupportConfiguration };
