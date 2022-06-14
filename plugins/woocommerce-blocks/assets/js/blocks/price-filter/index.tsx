/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import classNames from 'classnames';
import { Icon, currencyDollar } from '@wordpress/icons';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import { blockAttributes } from './attributes';

registerBlockType( metadata, {
	title: __( 'Filter Products by Price', 'woo-gutenberg-products-block' ),
	description: __(
		'Allow customers to filter the products by choosing a lower or upper price limit. Works in combination with the All Products block.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: (
			<Icon
				icon={ currencyDollar }
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
					idBase === 'woocommerce_price_filter' && !! instance?.raw,
				transform: ( { instance } ) =>
					createBlock( 'woocommerce/price-filter', {
						showInputFields: false,
						showFilterButton: true,
						heading:
							instance?.raw?.title ||
							__(
								'Filter by price',
								'woo-gutenberg-products-block'
							),
						headingLevel: 3,
					} ),
			},
		],
	},
	edit,
	save( { attributes } ) {
		const {
			className,
			showInputFields,
			showFilterButton,
			heading,
			headingLevel,
		} = attributes;
		const data = {
			'data-showinputfields': showInputFields,
			'data-showfilterbutton': showFilterButton,
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
					className="wc-block-product-categories__placeholder"
				/>
			</div>
		);
	},
} );
