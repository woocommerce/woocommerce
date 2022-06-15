/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
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

registerBlockType( metadata, {
	title: __( 'Filter Products by Attribute', 'woo-gutenberg-products-block' ),
	description: __(
		'Allow customers to filter the grid by product attribute, such as color. Works in combination with the All Products block.',
		'woo-gutenberg-products-block'
	),
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
				radius: true,
				color: true,
				width: false,
			},
		} ),
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
					idBase === 'woocommerce_layered_nav' && !! instance?.raw,
				transform: ( { instance } ) =>
					createBlock( 'woocommerce/attribute-filter', {
						attributeId: 0,
						showCounts: true,
						queryType: instance?.raw?.query_type || 'or',
						heading:
							instance?.raw?.title ||
							__(
								'Filter by attribute',
								'woo-gutenberg-products-block'
							),
						headingLevel: 3,
						displayStyle: instance?.raw?.display_type || 'list',
						showFilterButton: false,
						isPreview: false,
					} ),
			},
		],
	},
	edit,
	// Save the props to post content.
	save( { attributes }: { attributes: BlockAttributes } ) {
		const {
			className,
			showCounts,
			queryType,
			attributeId,
			heading,
			headingLevel,
			displayStyle,
			showFilterButton,
		} = attributes;
		const data: Record< string, unknown > = {
			'data-attribute-id': attributeId,
			'data-show-counts': showCounts,
			'data-query-type': queryType,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};
		if ( displayStyle !== 'list' ) {
			data[ 'data-display-style' ] = displayStyle;
		}
		if ( showFilterButton ) {
			data[ 'data-show-filter-button' ] = showFilterButton;
		}
		return (
			<div
				{ ...useBlockProps.save( {
					className: classNames( 'is-loading', className ),
				} ) }
				{ ...data }
			>
				<span
					aria-hidden
					className="wc-block-product-attribute-filter__placeholder"
				/>
			</div>
		);
	},
} );
