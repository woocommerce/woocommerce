/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, card } from '@woocommerce/icons';
import { kebabCase } from 'lodash';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import edit from './edit';
import blockAttributes from './attributes';
import './editor.scss';

const settings = {
	title: __( 'Checkout', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ card } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display the checkout experience for customers.',
		'woo-gutenberg-products-block'
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
			/>
		);
	},
};

registerBlockType( 'woocommerce/checkout', settings );
