/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import { ratingFilterIcon } from './icon';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: {
			src: ratingFilterIcon,
		},
		attributes: {
			...metadata.attributes,
		},
		edit,
	} );
}
