/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks/index';

/**
 * Switch layout to reduced flex email layout
 * Email render engine can't handle full flex layout se we need to switch to reduced flex layout
 */
function enhanceButtonsBlock() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/change-buttons',
		( settings: Block, name ) => {
			if ( name === 'core/buttons' ) {
				return {
					...settings,
					supports: {
						...settings.supports,
						layout: false, // disable block editor's layouts
						// enable MailPoet's reduced flex email layout
						__experimentalEmailFlexLayout: true, // eslint-disable-line
					},
				};
			}
			return settings;
		}
	);
}

export { enhanceButtonsBlock };
