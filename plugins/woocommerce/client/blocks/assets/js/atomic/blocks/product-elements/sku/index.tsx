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
import edit from './edit';
registerBlockType( metadata, {
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
} );
