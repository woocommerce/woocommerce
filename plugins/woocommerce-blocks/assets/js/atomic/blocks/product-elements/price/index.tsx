/**
 * External dependencies
 */
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';
import { currencyDollar, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import { supports } from './supports';
import metadata from './block.json';

registerBlockSingleProductTemplate( {
	blockName: metadata.name,
	blockMetadata: metadata,
	blockSettings: {
		...sharedConfig,
		supports,
		icon: (
			<Icon
				icon={ currencyDollar }
				className="wc-block-editor-components-block-icon"
			/>
		),
		edit,
	},
	isAvailableOnPostEditor: true,
} );
