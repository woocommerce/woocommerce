/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, Disabled } from '@wordpress/components';
import { formatPrice, getCurrency } from '@woocommerce/price-format';
import {
	useBlockProps,
	InspectorControls,
	withColors,

	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { colorNames } from './constants';
import { getHasColorClasses, getStyleColorVars } from '../../utils/colors';
import type { EditProps } from './types';

const PriceSliderEdit = ( {
	clientId,
	context,

	attributes,
	setAttributes,

	// Custom colors
	sliderHandle,
	setSliderHandle,
	sliderHandleBorder,
	setSliderHandleBorder,
	slider,
	setSlider,
}: EditProps ): JSX.Element | null => {
	const {
		showInputFields,
		inlineInput,

		customSliderHandle,
		customSliderHandleBorder,
		customSlider,
	} = attributes;

	const { isLoading, price } = context.filterData;

	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-price-slider', {
			'is-loading': isLoading,
			...getHasColorClasses( attributes, colorNames ),
		} ),
		style: getStyleColorVars(
			'wc-product-filter-price',
			attributes,
			colorNames
		),
	} );

	const colorGradientSettings = useMultipleOriginColorsAndGradients();

	if ( isLoading ) {
		return <>{ __( 'Loading…', 'woocommerce' ) }</>;
	}

	if ( ! price ) {
		return null;
	}

	const { minPrice, maxPrice, minRange, maxRange } = price;
	const formattedMinPrice = formatPrice(
		minPrice,
		getCurrency( { minorUnit: 0 } )
	);

	const formattedMaxPrice = formatPrice(
		maxPrice,
		getCurrency( { minorUnit: 0 } )
	);

	const priceMin = showInputFields ? (
		<input className="min" type="text" defaultValue={ formattedMinPrice } />
	) : (
		<span>{ formattedMinPrice }</span>
	);

	const priceMax = showInputFields ? (
		<input className="max" type="text" defaultValue={ formattedMaxPrice } />
	) : (
		<span>{ formattedMaxPrice }</span>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleControl
						label={ __( 'Show input fields', 'woocommerce' ) }
						checked={ showInputFields }
						onChange={ () =>
							setAttributes( {
								showInputFields: ! showInputFields,
							} )
						}
						__nextHasNoMarginBottom
					/>

					{ showInputFields && (
						<ToggleControl
							label={ __( 'Inline input fields', 'woocommerce' ) }
							checked={ inlineInput }
							onChange={ () =>
								setAttributes( { inlineInput: ! inlineInput } )
							}
							__nextHasNoMarginBottom
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<InspectorControls group="color">
				{ colorGradientSettings.hasColorsOrGradients && (
					<ColorGradientSettingsDropdown
						__experimentalIsRenderedInSidebar
						settings={ [
							{
								label: __( 'Slider Handle', 'woocommerce' ),
								colorValue:
									sliderHandle.color || customSliderHandle,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setSliderHandle( colorValue );
									setAttributes( {
										customSliderHandle: colorValue,
									} );
								},
								resetAllFilter: () => {
									setSliderHandle( '' );
									setAttributes( {
										customSliderHandle: '',
									} );
								},
							},
							{
								label: __(
									'Slider Handle Border',
									'woocommerce'
								),
								colorValue:
									sliderHandleBorder.color ||
									customSliderHandleBorder,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setSliderHandleBorder( colorValue );
									setAttributes( {
										customSliderHandleBorder: colorValue,
									} );
								},
								resetAllFilter: () => {
									setSliderHandleBorder( '' );
									setAttributes( {
										customSliderHandleBorder: '',
									} );
								},
							},
							{
								label: __( 'Slider', 'woocommerce' ),
								colorValue: slider.color || customSlider,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setSlider( colorValue );
									setAttributes( {
										customSlider: colorValue,
									} );
								},
								resetAllFilter: () => {
									setSlider( '' );
									setAttributes( {
										customSlider: '',
									} );
								},
							},
						] }
						panelId={ clientId }
						{ ...colorGradientSettings }
					/>
				) }
			</InspectorControls>

			<div { ...blockProps }>
				<Disabled>
					<div
						className={ clsx(
							'wc-block-product-filter-price-slider__content',
							{
								'wc-block-product-filter-price-slider__content--inline':
									inlineInput && showInputFields,
							}
						) }
					>
						<div className="wc-block-product-filter-price-slider__left text">
							{ priceMin }
						</div>
						<div className="wc-block-product-filter-price-slider__range">
							<div className="range-bar"></div>
							<input
								type="range"
								className="min"
								min={ minRange }
								max={ maxRange }
								defaultValue={ minPrice }
							/>
							<input
								type="range"
								className="max"
								min={ minRange }
								max={ maxRange }
								defaultValue={ maxPrice }
							/>
						</div>
						<div className="wc-block-product-filter-price-slider__right text">
							{ priceMax }
						</div>
					</div>
				</Disabled>
			</div>
		</>
	);
};

export default withColors( ...colorNames )( PriceSliderEdit );
