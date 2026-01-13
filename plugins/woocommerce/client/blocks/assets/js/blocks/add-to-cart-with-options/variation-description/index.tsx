/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, page } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import VariationDescriptionEdit from './edit';

registerBlockType( metadata, {
	edit: VariationDescriptionEdit,
	icon: {
		src: (
			<Icon
				icon={ page }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	save: () => null,
} );
