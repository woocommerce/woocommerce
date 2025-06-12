/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import AttributeItemTemplateEdit from './edit';
import AttributeItemTemplateSave from './save';

registerBlockType( metadata, {
	edit: AttributeItemTemplateEdit,
	attributes: metadata.attributes,
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	save: AttributeItemTemplateSave,
} );
