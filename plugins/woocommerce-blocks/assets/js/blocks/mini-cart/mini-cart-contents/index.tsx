/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { cart } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit, { Save as save } from './edit';
import { blockName, attributes } from './attributes';
import './inner-blocks';

const settings: BlockConfiguration = {
	apiVersion: 3,
	title: __( 'Mini-Cart Contents', 'woocommerce' ),
	icon: {
		src: (
			<Icon
				icon={ cart }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __( 'Display a Mini-Cart widget.', 'woocommerce' ),
	supports: {
		align: false,
		html: false,
		multiple: false,
		reusable: false,
		inserter: false,
		color: {
			link: true,
		},
		lock: false,
		__experimentalBorder: {
			color: true,
			width: true,
		},
	},
	attributes,
	example: {
		attributes: {
			isPreview: true,
		},
	},
	edit,
	save,
};

registerBlockType( blockName, settings );
