/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import { Icon } from '@wordpress/icons';
import { productMeta } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';

const blockConfig = {
	...metadata,
	icon: {
		src: (
			<Icon
				icon={ productMeta }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit,
	save,
	ancestor: [ 'woocommerce/single-product' ],
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
