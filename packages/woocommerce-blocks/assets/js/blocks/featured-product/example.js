/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { previewProducts } from '@woocommerce/resource-previews';

export const example = {
	attributes: {
		contentAlign: 'center',
		dimRatio: 50,
		editMode: false,
		height: getSetting( 'default_height', 500 ),
		mediaSrc: '',
		showDesc: true,
		productId: 'preview',
		previewProduct: previewProducts[ 0 ],
	},
};
