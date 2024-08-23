/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { Save, Edit } from './edit';

registerBlockType( metadata, {
	apiVersion: 3,
	icon: {
		src: (
			<Icon
				icon={ info }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
	},
	edit: Edit,
	save: Save,
} );
