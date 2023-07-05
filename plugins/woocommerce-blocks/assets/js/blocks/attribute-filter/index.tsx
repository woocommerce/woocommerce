/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { Icon, category } from '@wordpress/icons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import edit from './edit';
import type { BlockAttributes } from './types';
import { blockAttributes } from './attributes';
import metadata from './block.json';
import deprecated from './deprecated';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ category }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	supports: {
		...metadata.supports,
		...( isFeaturePluginBuild() && {
			__experimentalBorder: {
				radius: false,
				color: true,
				width: false,
			},
		} ),
	},
	attributes: {
		...metadata.attributes,
		...blockAttributes,
	},
	edit,
	// Save the props to post content.
	save( { attributes }: { attributes: BlockAttributes } ) {
		const { className } = attributes;

		return (
			<div
				{ ...useBlockProps.save( {
					className: classNames( 'is-loading', className ),
				} ) }
			/>
		);
	},
	deprecated,
} );
