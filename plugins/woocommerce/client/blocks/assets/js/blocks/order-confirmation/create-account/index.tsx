/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, people } from '@wordpress/icons';

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
				icon={ people }
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
