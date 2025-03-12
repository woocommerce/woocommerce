/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { title as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './editor.scss';

if ( isExperimentalBlocksEnabled() ) {
	// @ts-expect-error metadata is not typed.
	registerBlockType( metadata, {
		edit,
		icon,
	} );
}
