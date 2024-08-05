/**
 * External dependencies
 */
import { Icon, button } from '@wordpress/icons';
import { registerBlockSingleProductTemplate } from '@woocommerce/blocks-utils';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './style.scss';
import './editor.scss';

const blockSettings = {
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

registerBlockSingleProductTemplate( {
	blockName: metadata.name,
	blockMetadata: metadata,
	blockSettings,
	isAvailableOnPostEditor: true,
} );
