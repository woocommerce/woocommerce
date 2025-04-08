/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, receipt } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ receipt }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
	},
	edit,
	save() {
		return null;
	},
} );
