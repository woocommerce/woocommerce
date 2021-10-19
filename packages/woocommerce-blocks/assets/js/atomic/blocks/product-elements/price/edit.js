/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, BaseControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import {
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
	withColors,
	ColorPalette,
	FontSizePicker,
	withFontSizes,
} from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import { BLOCK_TITLE, BLOCK_ICON } from './constants';

const TextControl = ( {
	fontSize,
	setFontSize,
	color,
	setColor,
	colorLabel,
} ) => (
	<>
		<FontSizePicker value={ fontSize.size } onChange={ setFontSize } />
		{ /* ColorPalette doesn't accept an id. */
		/* eslint-disable-next-line @wordpress/no-base-control-with-label-without-id */ }
		<BaseControl label={ colorLabel }>
			<ColorPalette
				value={ color.color }
				onChange={ setColor }
				label={ __( 'Color' ) }
			/>
		</BaseControl>
	</>
);
const PriceEdit = ( {
	fontSize,
	saleFontSize,
	setFontSize,
	setSaleFontSize,
	color,
	saleColor,
	setColor,
	setSaleColor,
	attributes,
	setAttributes,
} ) => {
	const { align } = attributes;
	return (
		<>
			{ isFeaturePluginBuild() && (
				<BlockControls>
					<AlignmentToolbar
						value={ align }
						onChange={ ( nextAlign ) => {
							setAttributes( { align: nextAlign } );
						} }
					/>
				</BlockControls>
			) }
			<InspectorControls>
				{ isFeaturePluginBuild() && (
					<>
						<PanelBody
							title={ __(
								'Price',
								'woocommerce'
							) }
						>
							<TextControl
								color={ color }
								setColor={ setColor }
								fontSize={ fontSize }
								setFontSize={ setFontSize }
								colorLabel={ __(
									'Color',
									'woocommerce'
								) }
							/>
						</PanelBody>
						<PanelBody
							title={ __(
								'Sale price',
								'woocommerce'
							) }
						>
							<TextControl
								color={ saleColor }
								setColor={ setSaleColor }
								fontSize={ saleFontSize }
								setFontSize={ setSaleFontSize }
								colorLabel={ __(
									'Color',
									'woocommerce'
								) }
							/>
						</PanelBody>
					</>
				) }
			</InspectorControls>
			<Block { ...attributes } />
		</>
	);
};

const Price = isFeaturePluginBuild()
	? compose( [
			withFontSizes( 'fontSize' ),
			withFontSizes( 'saleFontSize' ),
			withFontSizes( 'originalFontSize' ),
			withColors( 'color', { textColor: 'color' } ),
			withColors( 'saleColor', { textColor: 'saleColor' } ),
			withColors( 'originalColor', { textColor: 'originalColor' } ),
			withProductSelector( {
				icon: BLOCK_ICON,
				label: BLOCK_TITLE,
				description: __(
					'Choose a product to display its price.',
					'woocommerce'
				),
			} ),
	  ] )( PriceEdit )
	: PriceEdit;

export default Price;
