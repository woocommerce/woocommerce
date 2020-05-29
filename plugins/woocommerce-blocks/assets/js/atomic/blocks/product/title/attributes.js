/**
 * External dependencies
 */
import { previewProducts } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
export const blockAttributes = {
	product: {
		type: 'object',
		default: previewProducts[ 0 ],
	},
	headingLevel: {
		type: 'number',
		default: 2,
	},
	productLink: {
		type: 'boolean',
		default: true,
	},
};

export default blockAttributes;
