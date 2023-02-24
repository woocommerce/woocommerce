/**
 * External dependencies
 */
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';
import { registerBlockType, unregisterBlockType } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

registerBlockSingleProductTemplate( {
	registerBlockFn: () => {
		// @ts-expect-error: `registerBlockType` is a function that is typed in WordPress core.
		registerBlockType( metadata, {
			icon: {
				src: (
					<Icon
						icon={ button }
						className="wc-block-editor-components-block-icon"
					/>
				),
			},
			edit,
			save() {
				return null;
			},
		} );
	},
	unregisterBlockFn: () => {
		unregisterBlockType( metadata.name );
	},
	blockName: metadata.name,
} );
