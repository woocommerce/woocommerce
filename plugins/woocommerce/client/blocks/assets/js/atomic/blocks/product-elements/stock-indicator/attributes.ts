/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export const blockAttributes: BlockAttributes = {
	isDescendentOfQueryLoop: {
		type: 'boolean',
		default: false,
	},
	isDescendantOfAllProducts: {
		type: 'boolean',
		default: false,
	},
};

export default blockAttributes;
