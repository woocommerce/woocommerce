/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, card } from '@woocommerce/icons';
import classnames from 'classnames';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import edit from './edit';
import blockAttributes from './attributes';
import './editor.scss';

const settings = {
	title: __( 'Checkout', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ card } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Display a checkout form so your customers can submit orders.',
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
	attributes: blockAttributes,
	edit,

	//Save the props to post content.
	save( { attributes } ) {
		return (
			<div
				className={ classnames( 'is-loading', attributes.className ) }
			/>
		);
	},
};

registerFeaturePluginBlockType( 'woocommerce/checkout', settings );
