/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import AddToCartWithOptionsGroupedProductSelectorEdit from './edit';
import AddToCartWithOptionsGroupedProductSelectorSave from './save';
import { shouldBlockifiedAddToCartWithOptionsBeRegistered } from '../utils';

if ( shouldBlockifiedAddToCartWithOptionsBeRegistered ) {
	registerBlockType( metadata, {
		edit: AddToCartWithOptionsGroupedProductSelectorEdit,
		attributes: metadata.attributes,
		icon: {
			src: (
				<Icon
					icon={ button }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		save: AddToCartWithOptionsGroupedProductSelectorSave,
	} );
}
