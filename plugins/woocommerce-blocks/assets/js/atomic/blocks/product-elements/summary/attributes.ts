/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export const blockAttributes: BlockAttributes = {
	productId: {
		type: 'number',
		default: 0,
	},
	isDescendantOfAllProducts: {
		type: 'boolean',
		default: false,
	},
	showDescriptionIfEmpty: {
		type: 'boolean',
		default: false,
	},
	showLink: {
		type: 'boolean',
		default: true,
	},
	summaryLength: {
		type: 'number',
		default: 0,
	},
};

export default blockAttributes;
