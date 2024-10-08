/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { toggle } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import clsx from 'clsx';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import { blockAttributes } from './attributes';
import { Attributes } from './types';
import deprecated from './deprecated';

registerBlockType( metadata, {
	apiVersion: 3,
	icon: {
		src: (
			<Icon
				icon={ toggle }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
		...blockAttributes,
	},
	edit,
	// Save the props to post content.
	save( { attributes }: { attributes: Attributes } ) {
		const { className } = attributes;

		return (
			<div
				{ ...useBlockProps.save( {
					className: clsx( 'is-loading', className ),
				} ) }
			>
				<span
					aria-hidden
					className="wc-block-active-filters__placeholder"
				/>
			</div>
		);
	},
	deprecated,
} );
