/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { Icon } from '@wordpress/icons';
import { sparkles } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

registerBlockType( metadata, {
	edit: Edit,
	save: Save,
	attributes: {
		// header: {
		// 	type: 'string',
		// 	default: __( 'Great things coming soon', 'woocommerce' ),
		// },
		// subheader: {
		// 	type: 'string',
		// 	default: __(
		// 		'Something big is brewing! Our store is in the works - Launching shortly!',
		// 		'woocommerce'
		// 	),
		// },
		storePagesOnly: {
			type: 'boolean',
		},
	},
} );
