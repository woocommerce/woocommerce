/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, starEmpty } from '@wordpress/icons';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';

if ( isExperimentalBlocksEnabled() ) {
	registerBlockType( metadata, {
		icon: {
			src: (
				<Icon
					icon={ starEmpty }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		attributes: {
			...metadata.attributes,
		},
		edit,
	} );
}
