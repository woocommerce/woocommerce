/**
 * External dependencies
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import { Icon, info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import attributes from './attributes';

registerBlockType(
	metadata as BlockConfiguration,
	{
		icon: {
			src: (
				<Icon
					icon={ info }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit,
		save() {
			return <InnerBlocks.Content />;
		},
		attributes,
	} as unknown as Partial< BlockConfiguration >
);
