/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, currencyDollar } from '@wordpress/icons';
import { isExperimentalBuild } from '@woocommerce/block-settings';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';
import metadata from './block.json';
import wrapperMetadata from './wrapper/block.json';
import Edit from './edit';
import EditWrapper from './wrapper/edit';

if ( isExperimentalBuild() ) {
	registerBlockType( metadata, {
		icon: {
			src: (
				<Icon
					icon={ currencyDollar }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit: Edit,
		save: InnerBlocks.Content,
	} );

	// Register a variant wrapped in Collection Filter for discoverability
	registerBlockType( wrapperMetadata, {
		icon: {
			src: (
				<Icon
					icon={ currencyDollar }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit: EditWrapper,
		save: InnerBlocks.Content,
	} );
}
