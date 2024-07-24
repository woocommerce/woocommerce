/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import Save from './save';
import metadata from './block.json';
import deprecated from './deprecated';
import './store-only.scss';
import './entire-site.scss';

registerBlockType( metadata, {
	title: __( 'Coming Soon', 'woocommerce' ),
	edit: Edit,
	save: Save,
	apiVersion: 2,
	deprecated,
} );
