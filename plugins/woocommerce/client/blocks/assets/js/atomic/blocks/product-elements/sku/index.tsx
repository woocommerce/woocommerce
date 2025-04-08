/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon } from '@wordpress/icons';
import { barcode } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import sharedConfig from '../shared/config';
import edit from './edit';
import { supports } from './supports';

registerBlockType( metadata, {
	...sharedConfig,
	icon: (
		<Icon
			icon={ barcode }
			className="wc-block-editor-components-block-icon"
		/>
	),
	edit,
	save() {
		return null;
	},
	supports,
} );
