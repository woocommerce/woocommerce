/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import clsx from 'clsx';
import { Icon, currencyDollar } from '@wordpress/icons';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import { blockAttributes } from './attributes';
import deprecated from './deprecated';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ currencyDollar }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
		...blockAttributes,
	},
	edit,
	save( { attributes } ) {
		const { className } = attributes;
		return (
			<div
				{ ...useBlockProps.save( {
					className: clsx( 'is-loading', className ),
				} ) }
			>
				<span
					aria-hidden
					className="wc-block-product-categories__placeholder"
				/>
			</div>
		);
	},
	deprecated,
} );
