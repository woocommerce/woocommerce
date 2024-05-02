/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { button as icon } from '@wordpress/icons';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import save from './save';

if ( isExperimentalBuild() ) {
	registerBlockType( metadata, {
		icon,
		edit: Edit,
		save,
	} );
}
