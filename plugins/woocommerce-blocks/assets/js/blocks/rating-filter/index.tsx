/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, starEmpty } from '@wordpress/icons';
import classNames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import type { Attributes } from './types';
import deprecated from './deprecated';

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
	// Save the props to post content.
	save( { attributes }: { attributes: Attributes } ) {
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
