/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, cart } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import './style.scss';
import { blockName, blockAttributes } from './attributes';
import './inner-blocks';

/**
 * Register and run the Cart block.
 */
const settings = {
	title: __( 'Cart i2', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ cart } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __( 'Shopping cart.', 'woocommerce' ),
	supports: {
		align: false,
		html: false,
		multiple: false,
		__experimentalExposeControlsToChildren: true,
	},
	example: {
		attributes: {
			isPreview: true,
		},
	},
	attributes: blockAttributes,
	edit: Edit,
	save: Save,
};

registerFeaturePluginBlockType( blockName, settings );
