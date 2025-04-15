/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { percent, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import save from '../save';
import edit from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: (
		<Icon
			icon={ percent }
			className="wc-block-editor-components-block-icon"
		/>
	),
	edit,
	save,
} );
