/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, box } from '@wordpress/icons';
import { isExperimentalBuild } from '@woocommerce/block-settings';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';
import edit from './edit';
import metadata from './block.json';

if ( isExperimentalBuild() ) {
	registerBlockType( metadata, {
		icon: {
			src: (
				<Icon
					icon={ box }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit,
		save: InnerBlocks.Content,
	} );
}
