/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';
import { isBoolean } from '@woocommerce/types';
import { getSettingWithCoercion } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import AddToCartWithOptionsVariationSelectorEdit from './edit';
import AddToCartWithOptionsVariationSelectorSave from './save';

const isBlockTheme = getSettingWithCoercion( 'isBlockTheme', false, isBoolean );

if ( isBlockTheme ) {
	registerBlockType( metadata, {
		edit: AddToCartWithOptionsVariationSelectorEdit,
		attributes: metadata.attributes,
		icon: {
			src: (
				<Icon
					icon={ button }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		save: AddToCartWithOptionsVariationSelectorSave,
	} );
}
