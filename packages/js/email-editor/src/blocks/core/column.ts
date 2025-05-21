/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks/index';

function enhanceColumnBlock() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/change-column',
		( settings: Block, name ) => {
			if ( name === 'core/column' ) {
				return {
					...settings,
					supports: {
						...settings.supports,
						background: {
							backgroundImage: true,
						},
					},
				};
			}
			return settings;
		}
	);
}

export { enhanceColumnBlock };
