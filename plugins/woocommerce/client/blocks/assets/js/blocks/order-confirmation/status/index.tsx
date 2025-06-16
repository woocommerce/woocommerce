/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ info }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit,
	save,
	deprecated: [
		{
			attributes: {
				...metadata.attributes,
			},
			save() {
				return null;
			},
			migrate( attributes: ( typeof metadata )[ 'attributes' ] ) {
				return attributes;
			},
		},
	],
} );
