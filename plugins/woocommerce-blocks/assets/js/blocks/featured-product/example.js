/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { previewProducts } from '@woocommerce/resource-previews';

export const example = {
	attributes: {
		alt: '',
		contentAlign: 'center',
		dimRatio: 50,
		editMode: false,
		hasParallax: false,
		isRepeated: false,
		height: getSetting( 'default_height', 500 ),
		mediaSrc: '',
		overlayColor: '#000000',
		showDesc: true,
		productId: 'preview',
		previewProduct: previewProducts[ 0 ],
	},
};
