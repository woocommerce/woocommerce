/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import './style.scss';
import edit from './edit';
import metadata from './block.json';
import { stockStatusFilterIcon } from './icon';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: {
			src: stockStatusFilterIcon,
		},
		edit,
	} );
}
