/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { toggle } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import classNames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import { blockAttributes } from './attributes';
import { Attributes } from './types';

registerBlockType( metadata, {
	title: __( 'Active Product Filters', 'woo-gutenberg-products-block' ),
	description: __(
		'Display the currently active product filters.',
		'woo-gutenberg-products-block'
	),
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
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/legacy-widget' ],
				// We can't transform if raw instance isn't shown in the REST API.
				isMatch: ( { idBase, instance } ) =>
					idBase === 'woocommerce_layered_nav_filters' &&
					!! instance?.raw,
				transform: ( { instance } ) =>
					createBlock( 'woocommerce/active-filters', {
						displayStyle: 'list',
						heading:
							instance?.raw?.title ||
							__(
								'Active filters',
								'woo-gutenberg-products-block'
							),
						headingLevel: 3,
					} ),
			},
		],
	},
	edit,
	// Save the props to post content.
	save( { attributes }: { attributes: Attributes } ) {
		const { className, displayStyle, heading, headingLevel } = attributes;
		const data = {
			'data-display-style': displayStyle,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};

		return (
			<div
				{ ...useBlockProps.save( {
					className: classNames( 'is-loading', className ),
				} ) }
				{ ...data }
			>
				<span
					aria-hidden
					className="wc-block-active-product-filters__placeholder"
				/>
			</div>
		);
	},
} );
