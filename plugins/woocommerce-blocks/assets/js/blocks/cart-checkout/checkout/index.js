/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, card } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import { example } from './example';
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
	example,
	attributes: {
		/**
		 * Are we previewing?
		 */
		isPreview: {
			type: 'boolean',
			default: false,
		},
	},
	edit,
	/**
	 * Save the props to post content.
	 */
	save( { attributes } ) {
		const { className } = attributes;
		return (
			<div className={ className }>
				Checkout block coming soon to store near you
			</div>
		);
	},
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE === 'experimental' ) {
	registerBlockType( 'woocommerce/checkout', settings );
}
