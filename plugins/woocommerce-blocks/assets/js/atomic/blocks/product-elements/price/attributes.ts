/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';

export const blockAttributes: BlockAttributes = {
	productId: {
		type: 'number',
		default: 0,
	},
	isDescendentOfQueryLoop: {
		type: 'boolean',
		default: false,
	},
	textAlign: {
		type: 'string',
	},
};

export default blockAttributes;
