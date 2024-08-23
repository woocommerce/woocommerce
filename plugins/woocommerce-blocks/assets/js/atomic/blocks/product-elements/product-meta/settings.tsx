/**
 * External dependencies
 */
import { Icon } from '@wordpress/icons';
import { productMeta } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';

export const ProductMetaBlockSettings = {
	edit,
	save,
	icon: {
		src: (
			<Icon
				icon={ productMeta }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	ancestor: [ 'woocommerce/single-product' ],
};
