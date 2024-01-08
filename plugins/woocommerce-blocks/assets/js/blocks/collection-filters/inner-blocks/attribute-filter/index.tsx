/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBuild } from '@woocommerce/block-settings';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';
import metadata from './block.json';
import Edit from './edit';

if ( isExperimentalBuild() ) {
	registerBlockType( metadata, {
		edit: Edit,
		save: InnerBlocks.Content,
	} );
}
