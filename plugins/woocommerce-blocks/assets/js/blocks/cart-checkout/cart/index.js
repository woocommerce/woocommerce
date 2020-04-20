/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, cart } from '@woocommerce/icons';
import { kebabCase } from 'lodash';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import edit from './edit';
import './style.scss';
import blockAttributes from './attributes';

/**
 * Register and run the Cart block.
 */
const settings = {
	title: __( 'Cart', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ cart } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __( 'Shopping cart.', 'woo-gutenberg-products-block' ),
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
	attributes: blockAttributes,
	edit,

	/**
	 * Save the props to post content.
	 */
	save( { attributes } ) {
		const data = {};

		Object.keys( blockAttributes ).forEach( ( key ) => {
			if (
				blockAttributes[ key ].save !== false &&
				typeof attributes[ key ] !== 'undefined'
			) {
				data[ 'data-' + kebabCase( key ) ] = attributes[ key ];
			}
		} );

		return (
			<div
				className={ classnames( 'is-loading', attributes.className ) }
				{ ...data }
			>
				<InnerBlocks.Content />
			</div>
		);
	},
};

registerBlockType( 'woocommerce/cart', settings );
