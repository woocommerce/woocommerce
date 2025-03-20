/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import { Icon } from '@wordpress/icons';
import { productDetails } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './style.scss';

const blockConfig = {
	...metadata,
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

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: false,
} );
