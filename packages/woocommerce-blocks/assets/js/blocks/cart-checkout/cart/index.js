/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { Icon, cart } from '@woocommerce/icons';
import classnames from 'classnames';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';
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
	title: __( 'Cart', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ cart } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __( 'Shopping cart.', 'woocommerce' ),
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

	// Save the props to post content.
	save( { attributes } ) {
		return (
			<div className={ classnames( 'is-loading', attributes.className ) }>
				<InnerBlocks.Content />
			</div>
		);
	},
};

registerFeaturePluginBlockType( 'woocommerce/cart', settings );
