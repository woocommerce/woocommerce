/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import ProductItemCTAEdit from './edit';
import { shouldBlockifiedAddToCartWithOptionsBeRegistered } from '../../utils';
import './style.scss';

if ( shouldBlockifiedAddToCartWithOptionsBeRegistered ) {
	registerBlockType( metadata, {
		edit: ProductItemCTAEdit,
		attributes: metadata.attributes,
		icon: {
			src: (
				<Icon
					icon={ button }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		save: () => null,
	} );
}
