/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import { isExperimentalBlockStylingEnabled } from '@woocommerce/block-settings';

let blockAttributes: BlockAttributes = {
	headingLevel: {
		type: 'number',
		default: 2,
	},
	showProductLink: {
		type: 'boolean',
		default: true,
	},
	linkTarget: {
		type: 'string',
	},
	productId: {
		type: 'number',
		default: 0,
	},
};

if ( isExperimentalBlockStylingEnabled() ) {
	blockAttributes = {
		...blockAttributes,
		align: {
			type: 'string',
		},
	};
}
export default blockAttributes;
