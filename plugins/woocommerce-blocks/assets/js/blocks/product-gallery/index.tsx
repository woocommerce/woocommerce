/**
 * External dependencies
 */
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit } from './edit';
import { Save } from './save';
import metadata from './block.json';
import icon from './icon';
import './inner-blocks/product-gallery-large-image';
import './inner-blocks/product-gallery-thumbnails';

if ( isExperimentalBuild() ) {
	registerBlockSingleProductTemplate( {
		blockName: metadata.name,
		// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core
		blockMetadata: metadata,
		blockSettings: {
			icon,
			// @ts-expect-error `edit` can be extended to include other attributes
			edit: Edit,
			save: Save,
			ancestor: [ 'woocommerce/single-product' ],
		},
		isAvailableOnPostEditor: true,
	} );
}
