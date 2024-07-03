/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export const blockAttributes: BlockAttributes = {
	productId: {
		type: 'number',
		default: 0,
	},
	isDescendentOfQueryLoop: {
		type: 'boolean',
		default: false,
	},
	isDescendentOfSingleProductTemplate: {
		type: 'boolean',
		default: false,
	},
	isDescendentOfSingleProductBlock: {
		type: 'boolean',
		default: false,
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
		default: false,
	},
	summaryLength: {
		type: 'number',
		default: 0,
	},
	linkText: {
		type: 'string',
		default: '',
	},
};

export default blockAttributes;
