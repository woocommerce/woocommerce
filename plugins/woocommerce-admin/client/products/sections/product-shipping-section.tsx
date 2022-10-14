/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Link, Spinner, useFormContext } from '@woocommerce/components';
import {
	EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME,
	OPTIONS_STORE_NAME,
	PartialProduct,
	ProductShippingClass,
} from '@woocommerce/data';
import interpolateComponents from '@automattic/interpolate-components';
import {
	BaseControl,
	Card,
	CardBody,
	SelectControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ADMIN_URL } from '../../utils/admin-settings';
import { ProductSectionLayout } from '../layout/product-section-layout';
import {
	ShippingDimensionsImage,
	ShippingDimensionsImageProps,
} from '../fields/shipping-dimensions-image';
import { useProductHelper } from '../use-product-helper';
import { AddNewShippingClassModal } from '../shared/add-new-shipping-class-modal';
import { getTextControlProps } from './utils';
import './product-shipping-section.scss';

export type ProductShippingSectionProps = {
	product?: PartialProduct;
};

// This should never be a real slug value of any existing shipping class
const ADD_NEW_SHIPPING_CLASS_OPTION_VALUE = '__ADD_NEW_SHIPPING_CLASS_OPTION__';

const DEFAULT_SHIPPING_CLASS_OPTIONS: SelectControl.Option[] = [
	{ value: '', label: __( 'No shipping class', 'woocommerce' ) },
	{
		value: ADD_NEW_SHIPPING_CLASS_OPTION_VALUE,
		label: __( 'Add new shipping class', 'woocommerce' ),
	},
];

const UNCATEGORIZED_CATEGORY_SLUG = 'uncategorized';

function mapShippingClassToSelectOption(
	shippingClasses: ProductShippingClass[]
): SelectControl.Option[] {
	return shippingClasses.map( ( { slug, name } ) => ( {
		value: slug,
		label: name,
	} ) );
}

function getInterpolatedSizeLabel( mixedString: string ) {
	return interpolateComponents( {
		mixedString,
		components: {
			span: <span className="woocommerce-product-form__secondary-text" />,
		},
	} );
}

/**
 * This extracts a shipping class from the product categories. Using
 * the first category different to `Uncategorized`.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/34657
 * @param product The product
 * @return The default shipping class
 */
function extractDefaultShippingClassFromProduct(
	product: PartialProduct
): Partial< ProductShippingClass > | undefined {
	const category = product?.categories?.find(
		( { slug } ) => slug !== UNCATEGORIZED_CATEGORY_SLUG
	);
	if ( category ) {
		return {
			name: category.name,
			slug: category.slug,
		};
	}
}

export function ProductShippingSection( {
	product,
}: ProductShippingSectionProps ) {
	const { getInputProps } = useFormContext< PartialProduct >();
	const { formatNumber, parseNumber } = useProductHelper();
	const [ highlightSide, setHighlightSide ] =
		useState< ShippingDimensionsImageProps[ 'highlight' ] >();
	const [ showShippingClassModal, setShowShippingClassModal ] =
		useState( false );

	const { shippingClasses, hasResolvedShippingClasses } = useSelect(
		( select ) => {
			const { getProductShippingClasses, hasFinishedResolution } = select(
				EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
			);
			return {
				hasResolvedShippingClasses: hasFinishedResolution(
					'getProductShippingClasses'
				),
				shippingClasses:
					getProductShippingClasses< ProductShippingClass[] >(),
			};
		},
		[]
	);

	const { dimensionUnit, weightUnit, hasResolvedUnits } = useSelect(
		( select ) => {
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
		},
		[]
	);

	const { createProductShippingClass, invalidateResolution } = useDispatch(
		EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
	);

	const selectShippingClassProps = getTextControlProps(
		getInputProps( 'shipping_class' )
	);
	const inputWidthProps = getTextControlProps(
		getInputProps( 'dimensions.width' )
	);
	const inputLengthProps = getTextControlProps(
		getInputProps( 'dimensions.length' )
	);
	const inputHeightProps = getTextControlProps(
		getInputProps( 'dimensions.height' )
	);
	const inputWeightProps = getTextControlProps( getInputProps( 'weight' ) );

	return (
		<ProductSectionLayout
			title={ __( 'Shipping', 'woocommerce' ) }
			description={ __(
				'Set up shipping costs and enter dimensions used for accurate rate calculations.',
				'woocommerce'
			) }
		>
			<Card>
				<CardBody className="product-shipping-section__classes">
					{ hasResolvedShippingClasses ? (
						<>
							<SelectControl
								{ ...selectShippingClassProps }
								label={ __( 'Shipping class', 'woocommerce' ) }
								options={ [
									...DEFAULT_SHIPPING_CLASS_OPTIONS,
									...mapShippingClassToSelectOption(
										shippingClasses ?? []
									),
								] }
								onChange={ ( value: string ) => {
									if (
										value ===
										ADD_NEW_SHIPPING_CLASS_OPTION_VALUE
									) {
										setShowShippingClassModal( true );
										return;
									}
									selectShippingClassProps?.onChange( value );
								} }
							/>
							<span className="woocommerce-product-form__secondary-text">
								{ interpolateComponents( {
									mixedString: __(
										'Manage shipping classes and rates in {{link}}global settings{{/link}}.',
										'woocommerce'
									),
									components: {
										link: (
											<Link
												href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping&section=classes` }
												target="_blank"
												type="external"
											>
												<></>
											</Link>
										),
									},
								} ) }
							</span>
						</>
					) : (
						<div className="product-shipping-section__spinner-wrapper">
							<Spinner />
						</div>
					) }
				</CardBody>
			</Card>

			<Card>
				<CardBody className="product-shipping-section__dimensions">
					{ hasResolvedUnits ? (
						<>
							<h4>{ __( 'Dimensions', 'woocommerce' ) }</h4>
							<p className="woocommerce-product-form__secondary-text">
								{ __(
									'Enter the size of the product as you’d put it in a shipping box, including packaging like bubble wrap.',
									'woocommerce'
								) }
							</p>
							<div className="product-shipping-section__dimensions-body">
								<div className="product-shipping-section__dimensions-body-col">
									<BaseControl
										id="product_shipping_dimensions_width"
										className={ inputWidthProps.className }
										help={ inputWidthProps.help }
									>
										<InputControl
											{ ...inputWidthProps }
											value={ formatNumber(
												inputWidthProps.value
											) }
											label={ getInterpolatedSizeLabel(
												__(
													'Width {{span}}A{{/span}}',
													'woocommerce'
												)
											) }
											onChange={ ( value: string ) =>
												inputWidthProps?.onChange(
													parseNumber( value )
												)
											}
											onFocus={ () => {
												setHighlightSide( 'A' );
											} }
											onBlur={ () => {
												setHighlightSide( undefined );
												inputWidthProps?.onBlur();
											} }
											suffix={ dimensionUnit }
										/>
									</BaseControl>

									<BaseControl
										id="product_shipping_dimensions_length"
										className={ inputLengthProps.className }
										help={ inputLengthProps.help }
									>
										<InputControl
											{ ...inputLengthProps }
											value={ formatNumber(
												inputLengthProps.value
											) }
											label={ getInterpolatedSizeLabel(
												__(
													'Length {{span}}B{{/span}}',
													'woocommerce'
												)
											) }
											onChange={ ( value: string ) =>
												inputLengthProps?.onChange(
													parseNumber( value )
												)
											}
											onFocus={ () => {
												setHighlightSide( 'B' );
											} }
											onBlur={ () => {
												setHighlightSide( undefined );
												inputLengthProps?.onBlur();
											} }
											suffix={ dimensionUnit }
										/>
									</BaseControl>

									<BaseControl
										id="product_shipping_dimensions_height"
										className={ inputHeightProps.className }
										help={ inputHeightProps.help }
									>
										<InputControl
											{ ...inputHeightProps }
											value={ formatNumber(
												inputHeightProps.value
											) }
											label={ getInterpolatedSizeLabel(
												__(
													'Height {{span}}C{{/span}}',
													'woocommerce'
												)
											) }
											onChange={ ( value: string ) =>
												inputHeightProps?.onChange(
													parseNumber( value )
												)
											}
											onFocus={ () => {
												setHighlightSide( 'C' );
											} }
											onBlur={ () => {
												setHighlightSide( undefined );
												inputHeightProps?.onBlur();
											} }
											suffix={ dimensionUnit }
										/>
									</BaseControl>

									<BaseControl
										id="product_shipping_weight"
										className={ inputWeightProps.className }
										help={ inputWeightProps.help }
									>
										<InputControl
											{ ...inputWeightProps }
											value={ formatNumber(
												inputWeightProps.value
											) }
											label={ __(
												'Weight',
												'woocommerce'
											) }
											onChange={ ( value: string ) =>
												inputWeightProps?.onChange(
													parseNumber( value )
												)
											}
											suffix={ weightUnit }
										/>
									</BaseControl>
								</div>
								<div className="product-shipping-section__dimensions-body-col">
									<ShippingDimensionsImage
										highlight={ highlightSide }
										className="product-shipping-section__dimensions-image"
									/>
								</div>
							</div>
						</>
					) : (
						<div className="product-shipping-section__spinner-wrapper">
							<Spinner />
						</div>
					) }
				</CardBody>
			</Card>

			{ showShippingClassModal && (
				<AddNewShippingClassModal
					shippingClass={
						product &&
						extractDefaultShippingClassFromProduct( product )
					}
					onAdd={ ( values ) =>
						createProductShippingClass<
							Promise< ProductShippingClass >
						>( values ).then( ( value ) => {
							invalidateResolution( 'getProductShippingClasses' );
							return value;
						} )
					}
					onCancel={ () => setShowShippingClassModal( false ) }
				/>
			) }
		</ProductSectionLayout>
	);
}
