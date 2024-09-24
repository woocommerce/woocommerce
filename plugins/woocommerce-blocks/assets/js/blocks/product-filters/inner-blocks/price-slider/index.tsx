/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';
import './style.scss';

registerBlockType( metadata, {
	edit,
	save,
	description: __(
		'Allow customers to filter the product list by choosing a price range.',
		'woocommerce'
	),
} );
