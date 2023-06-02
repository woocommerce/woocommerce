/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, grid } from '@woocommerce/icons';
import '@woocommerce/atomic-blocks';

/**
 * Internal dependencies
 */
import Editor from './edit';
import { attributes as sharedAttributes, defaults } from '../attributes';
import { getBlockClassName } from '../utils.js';

const blockSettings = {
	title: __( 'All Products', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ grid } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Display products from your store in a grid layout.',
		'woocommerce'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: false,
	},
	example: {
		attributes: {
			isPreview: true,
		},
	},
	attributes: sharedAttributes,
	defaults,
	/**
	 * Renders and manages the block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Editor { ...props } />;
	},
	// Save the props to post content.
	save( { attributes } ) {
		const dataAttributes = {};
		Object.keys( attributes )
			.sort()
			.forEach( ( key ) => {
				dataAttributes[ key ] = attributes[ key ];
			} );
		const data = {
			'data-attributes': JSON.stringify( dataAttributes ),
		};
		return (
			<div
				className={ getBlockClassName(
					'wc-block-all-products',
					attributes
				) }
				{ ...data }
			>
				<InnerBlocks.Content />
			</div>
		);
	},
};

/**
 * Register and run the "All Products" block.
 */
registerBlockType( 'woocommerce/all-products', {
	...blockSettings,
	/**
	 * Deprecation rule to handle the previous default rows which was 1 instead of 3.
	 */
	deprecated: [
		{
			attributes: Object.assign( {}, blockSettings.attributes, {
				rows: { type: 'number', default: 1 },
			} ),
			save( { attributes } ) {
				const data = {
					'data-attributes': JSON.stringify( attributes ),
				};
				return (
					<div
						className={ getBlockClassName(
							'wc-block-all-products',
							attributes
						) }
						{ ...data }
					>
						<InnerBlocks.Content />
					</div>
				);
			},
		},
	],
} );
