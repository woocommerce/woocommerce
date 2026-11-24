/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, grid } from '@wordpress/icons';
import '@woocommerce/product-elements';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import deprecated from './deprecated';
import edit from './edit';
import save from './save';

const { name } = metadata;
export { metadata, name };

const settings = {
	icon: {
		src: (
			<Icon
				icon={ grid }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit,
	// Save the props to post content.
	save,
	deprecated,
};

registerBlockType( name, settings );
