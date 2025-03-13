/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Icon, category } from '@wordpress/icons';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import edit from './edit';
import type { BlockAttributes } from './types';
import { blockAttributes } from './attributes';
import metadata from './block.json';
import deprecated from './deprecated';

registerBlockType( metadata, {
	apiVersion: 3,
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
					className: clsx( 'is-loading', className ),
				} ) }
			/>
		);
	},
	deprecated,
} );
