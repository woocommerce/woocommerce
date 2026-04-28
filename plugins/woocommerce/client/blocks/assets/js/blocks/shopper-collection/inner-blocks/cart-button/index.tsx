/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, button as buttonIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import Save from './save';
import './style.scss';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ buttonIcon }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
	save: Save,
} );
