/**
 * External dependencies
 */
import { previewProducts } from '@woocommerce/resource-previews';

export const blockAttributes = {
	product: {
		type: 'object',
		default: previewProducts[ 0 ],
	},
	productLink: {
		type: 'boolean',
		default: true,
	},
	showSaleBadge: {
		type: 'boolean',
		default: true,
	},
	saleBadgeAlign: {
		type: 'string',
		default: 'right',
	},
};

export default blockAttributes;
