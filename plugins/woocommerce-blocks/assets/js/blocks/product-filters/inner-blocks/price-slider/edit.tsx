/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Disabled } from '@wordpress/components';
import { formatPrice, getCurrency } from '@woocommerce/price-format';

/**
 * Internal dependencies
 */
import { EditProps } from './types';

const Edit = ( { attributes, setAttributes, context }: EditProps ) => {
	const { showInputFields, inlineInput } = attributes;
	const blockProps = useBlockProps( {
		className: 'wc-block-product-filter-price-slider',
	} );

	const { isLoading, price } = context.filterData;

	if ( isLoading ) {
		return 'Loading...';
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
					/>
					{ showInputFields && (
						<ToggleControl
							label={ __( 'Inline input fields', 'woocommerce' ) }
							checked={ inlineInput }
							onChange={ () =>
								setAttributes( { inlineInput: ! inlineInput } )
							}
						/>
					) }
				</PanelBody>
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

export default Edit;
