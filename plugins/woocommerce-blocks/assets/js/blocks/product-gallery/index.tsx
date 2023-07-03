/**
 * External dependencies
 */
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import metadata from './block.json';
import icon from './icon';

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
	} );
}
