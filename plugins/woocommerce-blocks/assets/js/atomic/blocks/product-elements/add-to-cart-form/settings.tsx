/**
 * External dependencies
 */
import { Icon, button } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';

export const AddToCartFormBlockSettings = {
	edit,
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	ancestor: [ 'woocommerce/single-product' ],
	save() {
		return null;
	},
};
