/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

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

blockAttributes = {
	...blockAttributes,
	align: {
		type: 'string',
	},
};

export default blockAttributes;
