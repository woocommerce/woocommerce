/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks/index';

/**
 * Disables Styles for button
 * Currently we are not able to read these styles in renderer
 */
function enhanceButtonBlock() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/change-button',
		( settings: Block, name ) => {
			if ( name === 'core/button' ) {
				return { ...settings, styles: [] };
			}
			return settings;
		}
	);
}

export { enhanceButtonBlock };
