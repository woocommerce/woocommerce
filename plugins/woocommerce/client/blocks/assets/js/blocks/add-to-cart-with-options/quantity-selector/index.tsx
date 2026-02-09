/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import AddToCartWithOptionsQuantitySelectorEdit from './edit';

registerBlockType( metadata, {
	edit: AddToCartWithOptionsQuantitySelectorEdit,
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	save() {
		return null;
	},
} );
