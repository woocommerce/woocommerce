/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	__experimentalProductSectionLayout as ProductSectionLayout,
} from '@woocommerce/components';
import { registerPlugin } from '@wordpress/plugins';
import { PartialProduct, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { Card, CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	ShippingClassField,
	ShippingDimensionsWidthField,
	ShippingDimensionsLengthField,
	ShippingDimensionsHeightField,
	ShippingDimensionsWeightField,
	ProductShippingSectionPropsType,
	DimensionPropsType,
	ShippingDimensionsPropsType,
} from './index';
import {
	PLUGIN_ID,
	SHIPPING_SECTION_BASIC_ID,
	SHIPPING_SECTION_DIMENSIONS_ID,
	TAB_SHIPPING_ID,
} from '../constants';
import {
	ShippingDimensionsImage,
	ShippingDimensionsImageProps,
} from '../../fields/shipping-dimensions-image';
import { useProductHelper } from '../../use-product-helper';

import './shipping-section.scss';

const ShippingSection = () => {
	const [ highlightSide, setHighlightSide ] =
		useState< ShippingDimensionsImageProps[ 'highlight' ] >();
	const { parseNumber } = useProductHelper();

	const { dimensionUnit, hasResolvedUnits } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );
		return {
			dimensionUnit: getOption( 'woocommerce_dimension_unit' ),
			weightUnit: getOption( 'woocommerce_weight_unit' ),
			hasResolvedUnits:
				hasFinishedResolution( 'getOption', [
					'woocommerce_dimension_unit',
				] ) &&
				hasFinishedResolution( 'getOption', [
					'woocommerce_weight_unit',
				] ),
		};
	}, [] );

	const dimensionProps: DimensionPropsType = {
		onBlur: () => {
			setHighlightSide( undefined );
		},
		sanitize: ( value: PartialProduct[ keyof PartialProduct ] ) =>
			parseNumber( String( value ) ),
		suffix: dimensionUnit,
	};

	return (
		<>
			<WooProductSectionItem
				id={ SHIPPING_SECTION_BASIC_ID }
				location={ TAB_SHIPPING_ID }
				pluginId={ PLUGIN_ID }
				order={ 1 }
			>
				<ProductSectionLayout
					title={ __( 'Shipping', 'woocommerce' ) }
					description={ __(
						'Set up shipping costs and enter dimensions used for accurate rate calculations.',
						'woocommerce'
					) }
				>
					<Card>
						<CardBody className="product-shipping-section__classes">
							<WooProductFieldItem.Slot
								section={ SHIPPING_SECTION_BASIC_ID }
							/>
						</CardBody>
					</Card>
					<Card>
						<CardBody className="product-shipping-section__dimensions">
							<h4>{ __( 'Dimensions', 'woocommerce' ) }</h4>
							<p className="woocommerce-product-form__secondary-text">
								{ __(
									`Enter the size of the product as you'd put it in a shipping box, including packaging like bubble wrap.`,
									'woocommerce'
								) }
							</p>
							<div className="product-shipping-section__dimensions-body">
								<div className="product-shipping-section__dimensions-body-col">
									{ hasResolvedUnits && (
										<WooProductFieldItem.Slot
											section={
												SHIPPING_SECTION_DIMENSIONS_ID
											}
											fillProps={
												{
													setHighlightSide,
													dimensionProps,
												} as ShippingDimensionsPropsType
											}
										/>
									) }
								</div>
								<div className="product-shipping-section__dimensions-body-col">
									<ShippingDimensionsImage
										highlight={ highlightSide }
										className="product-shipping-section__dimensions-image"
									/>
								</div>
							</div>
						</CardBody>
					</Card>
				</ProductSectionLayout>
			</WooProductSectionItem>
			<WooProductFieldItem
				id="shipping/class"
				section={ SHIPPING_SECTION_BASIC_ID }
				pluginId={ PLUGIN_ID }
				order={ 1 }
			>
				{ ( { product }: ProductShippingSectionPropsType ) => (
					<ShippingClassField product={ product } />
				) }
			</WooProductFieldItem>
			<WooProductFieldItem
				id="shipping/dimensions/width"
				section={ SHIPPING_SECTION_DIMENSIONS_ID }
				pluginId={ PLUGIN_ID }
				order={ 1 }
			>
				{ ( { ...props }: ShippingDimensionsPropsType ) => (
					<ShippingDimensionsWidthField { ...props } />
				) }
			</WooProductFieldItem>
			<WooProductFieldItem
				id="shipping/dimensions/length"
				section={ SHIPPING_SECTION_DIMENSIONS_ID }
				pluginId={ PLUGIN_ID }
				order={ 3 }
			>
				{ ( { ...props }: ShippingDimensionsPropsType ) => (
					<ShippingDimensionsLengthField { ...props } />
				) }
			</WooProductFieldItem>
			<WooProductFieldItem
				id="shipping/dimensions/height"
				section={ SHIPPING_SECTION_DIMENSIONS_ID }
				pluginId={ PLUGIN_ID }
				order={ 5 }
			>
				{ ( { ...props }: ShippingDimensionsPropsType ) => (
					<ShippingDimensionsHeightField { ...props } />
				) }
			</WooProductFieldItem>
			<WooProductFieldItem
				id="shipping/dimensions/weight"
				section={ SHIPPING_SECTION_DIMENSIONS_ID }
				pluginId={ PLUGIN_ID }
				order={ 7 }
			>
				<ShippingDimensionsWeightField />
			</WooProductFieldItem>
		</>
	);
};

registerPlugin( 'wc-admin-product-editor-shipping-section', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => <ShippingSection />,
} );
