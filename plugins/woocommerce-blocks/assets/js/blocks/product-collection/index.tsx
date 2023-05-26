/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';
import icon from './icon';
import './variations';

if ( isExperimentalBuild() ) {
	registerBlockType( metadata, {
		icon,
		edit,
		save,
	} );
}
