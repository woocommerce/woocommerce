/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { previewCategories } from '@woocommerce/resource-previews';

export const example = {
	attributes: {
		contentAlign: 'center',
		dimRatio: 50,
		editMode: false,
		height: getSetting( 'default_height', 500 ),
		mediaSrc: '',
		showDesc: true,
		categoryId: 'preview',
		previewCategory: previewCategories[ 0 ],
	},
};
