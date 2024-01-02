/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, starEmpty } from '@wordpress/icons';
import { InnerBlocks } from '@wordpress/block-editor';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import wrapperMetadata from './wrapper/block.json';
import EditWrapper from './wrapper/edit';

if ( isExperimentalBuild() ) {
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
		save: InnerBlocks.Content,
	} );

	registerBlockType( wrapperMetadata, {
		icon: {
			src: (
				<Icon
					icon={ starEmpty }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit: EditWrapper,
		save: InnerBlocks.Content,
	} );
}
