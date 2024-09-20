/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Disabled } from '@wordpress/components';
import { formatPrice } from '@woocommerce/price-format';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';

const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attrs: Partial< BlockAttributes > ) => void;
} ) => {
	const { showInputFields, inlineInput } = attributes;
	const blockProps = useBlockProps( {
		className: 'wc-block-product-filter-price-slider',
	} );

	// Mock data for preview
	const minPrice = 0;
	const maxPrice = 1000;
	const formattedMinPrice = formatPrice( minPrice );
	const formattedMaxPrice = formatPrice( maxPrice );

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
								min={ minPrice }
								max={ maxPrice }
								defaultValue={ minPrice }
							/>
							<input
								type="range"
								className="max"
								min={ minPrice }
								max={ maxPrice }
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
