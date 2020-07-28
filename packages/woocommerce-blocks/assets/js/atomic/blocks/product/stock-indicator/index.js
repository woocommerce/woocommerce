/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';
import { Icon, box } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import edit from './edit';

const blockConfig = {
	title: __( 'Product Stock Indicator', 'woocommerce' ),
	description: __(
		'Display product stock status.',
		'woocommerce'
	),
	icon: {
		src: <Icon srcElement={ box } />,
		foreground: '#96588a',
	},
	edit,
};

registerExperimentalBlockType( 'woocommerce/product-stock-indicator', {
	...sharedConfig,
	...blockConfig,
} );
