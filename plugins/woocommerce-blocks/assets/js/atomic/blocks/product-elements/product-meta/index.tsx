/**
 * External dependencies
 */
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';
import { Icon } from '@wordpress/icons';
import { productMeta } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockSingleProductTemplate( {
	blockName: metadata.name,
	// @ts-expect-error: `metadata` currently does not have a type definition in WordPress core
	blockMetadata: metadata,
	blockSettings: {
		edit,
		save,
		icon: {
			src: (
				<Icon
					icon={ productMeta }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		ancestor: [ 'woocommerce/single-product' ],
	},
	isAvailableOnPostEditor: true,
} );
