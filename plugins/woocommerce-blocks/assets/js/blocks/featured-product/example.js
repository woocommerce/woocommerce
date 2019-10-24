/**
 * External dependencies
 */
import { DEFAULT_HEIGHT } from '@woocommerce/block-settings';
/**
 * Internal dependencies
 */
import { previewProducts } from '../../previews/products';

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
