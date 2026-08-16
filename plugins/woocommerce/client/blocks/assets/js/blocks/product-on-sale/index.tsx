/**
 * External dependencies
 */
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { Icon, percent } from '@wordpress/icons';
/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';
import sharedAttributes, {
	sharedAttributeBlockTypes,
} from '../../utils/shared-attributes';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ percent }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...sharedAttributes,
		...metadata.attributes,
	},
	transforms: {
		from: [
			{
				type: 'block',
				blocks: sharedAttributeBlockTypes.filter(
					( value ) => value !== 'woocommerce/product-on-sale'
				),
				transform: ( attributes ) =>
					createBlock( 'woocommerce/product-on-sale', attributes ),
			},
		],
	},

	/**
	 * Renders and manages the block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Block { ...props } />;
	},

	save() {
		return null;
	},
} );
