/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import ProductItemTemplateEdit from './edit';
import ProductItemTemplateSave from './save';

registerBlockType( metadata, {
	edit: ProductItemTemplateEdit,
	attributes: metadata.attributes,
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	save: ProductItemTemplateSave,
} );
