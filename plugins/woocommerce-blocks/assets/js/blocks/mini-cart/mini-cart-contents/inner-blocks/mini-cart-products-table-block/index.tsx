/**
 * External dependencies
 */
import { Icon, list } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: (
		<Icon icon={ list } className="wc-block-editor-components-block-icon" />
	),
	edit: Edit,
	save: Save,
} );
