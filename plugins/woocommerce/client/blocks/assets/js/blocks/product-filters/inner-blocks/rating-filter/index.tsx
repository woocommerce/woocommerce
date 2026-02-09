/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { productFilterRating as icon } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType( metadata, {
	icon,
	attributes: {
		...metadata.attributes,
	},
	edit,
	save,
} );
