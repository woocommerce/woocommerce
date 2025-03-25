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
import { shouldBlockifiedAddToCartWithOptionsBeRegistered } from '../utils';
import '../../../base/components/quantity-selector/style.scss';
import './style.scss';
import './editor.scss';

if ( shouldBlockifiedAddToCartWithOptionsBeRegistered ) {
	registerBlockType( metadata, {
		edit: AddToCartWithOptionsQuantitySelectorEdit,
		attributes: metadata.attributes,
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
}
