/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

registerBlockType( metadata, {
	title: __( 'Coming Soon', 'woocommerce' ),
	edit: Edit,
	save: Save,
	apiVersion: 2,
} );
