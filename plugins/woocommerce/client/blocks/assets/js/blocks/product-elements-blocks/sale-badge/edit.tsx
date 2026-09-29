/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';
import type { ReactElement } from 'react';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';

/**
 * Internal dependencies
 */
import Block from './block';
import type { BlockAttributes } from './types';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';

const Edit = ( {
	attributes,
	setAttributes,
	context,
}: BlockEditProps< BlockAttributes > & { context: Context } ): ReactElement => {
	const blockProps = useBlockProps();

	// Remove the `style` prop from the block props to avoid passing it to the wrapper div.
	const { style, ...wrapperProps } = blockProps;
	const { isDescendentOfSingleProductTemplate } =
		useIsDescendentOfSingleProductTemplate();

	const blockAttrs = {
		...attributes,
		...context,
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Badge content', 'woocommerce' ) }>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Show', 'woocommerce' ) }
						value={ attributes.badgeContent || 'text' }
						onChange={ ( badgeContent ) => {
							if (
								badgeContent === 'text' ||
								badgeContent === 'amount' ||
								badgeContent === 'percentage'
							) {
								setAttributes( { badgeContent } );
							}
						} }
						isBlock
					>
						<ToggleGroupControlOption
							label={ __( 'Text', 'woocommerce' ) }
							value="text"
						/>
						<ToggleGroupControlOption
							label={ __( 'Amount', 'woocommerce' ) }
							value="amount"
						/>
						<ToggleGroupControlOption
							label={ __( 'Percentage', 'woocommerce' ) }
							value="percentage"
						/>
					</ToggleGroupControl>
					{ ! attributes.badgeContent ||
					attributes.badgeContent === 'text' ? (
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Text', 'woocommerce' ) }
							value={
								attributes.saleText ??
								__( 'Sale', 'woocommerce' )
							}
							onChange={ ( saleText ) =>
								setAttributes( { saleText } )
							}
						/>
					) : (
						<>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Prefix', 'woocommerce' ) }
								value={ attributes.prefix ?? '' }
								onChange={ ( prefix ) =>
									setAttributes( { prefix } )
								}
							/>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Suffix', 'woocommerce' ) }
								value={ attributes.suffix ?? '' }
								onChange={ ( suffix ) =>
									setAttributes( { suffix } )
								}
							/>
						</>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...wrapperProps }>
				<Block
					{ ...blockAttrs }
					blockAttributes={ attributes }
					isDescendentOfSingleProductTemplate={
						isDescendentOfSingleProductTemplate
					}
				/>
			</div>
		</>
	);
};

export default Edit;
