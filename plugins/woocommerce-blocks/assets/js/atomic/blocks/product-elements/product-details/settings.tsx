/**
 * External dependencies
 */
import { Icon } from '@wordpress/icons';
import { productDetails } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import edit from './edit';

export const ProductDetailsBlockSettings = {
	icon: {
		src: (
			<Icon
				icon={ productDetails }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit,
};
