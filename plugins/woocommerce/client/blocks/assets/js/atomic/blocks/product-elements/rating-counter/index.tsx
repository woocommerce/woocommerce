/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, starFilled } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

registerBlockType( metadata, {
	apiVersion: 3,
	icon: {
		src: (
			<Icon
				icon={ starFilled }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit,
} );
