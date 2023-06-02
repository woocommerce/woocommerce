/**
 * External dependencies
 */
import { DEFAULT_HEIGHT } from '@woocommerce/block-settings';
import { previewProducts } from '@woocommerce/resource-previews';

export const example = {
	attributes: {
		contentAlign: 'center',
		dimRatio: 50,
		editMode: false,
		height: DEFAULT_HEIGHT,
		mediaSrc: '',
		showDesc: true,
		productId: 'preview',
		previewProduct: previewProducts[ 0 ],
	},
};
