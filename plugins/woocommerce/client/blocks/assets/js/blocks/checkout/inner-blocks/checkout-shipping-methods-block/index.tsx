/**
 * External dependencies
 */
import { Icon, shipping } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';
import metadata from './block.json';
import './style.scss';

registerBlockType( 'woocommerce/checkout-shipping-methods-block', {
	apiVersion: metadata.apiVersion,
	title: metadata.title,
	icon: {
		src: (
			<Icon
				icon={ shipping }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes,
	edit: Edit,
	save: Save,
} );
