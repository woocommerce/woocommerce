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
import { recordEvent } from '@woocommerce/tracks';
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
import './product-shipping-section.scss';
import {
	ADD_NEW_SHIPPING_CLASS_OPTION_VALUE,
	UNCATEGORIZED_CATEGORY_SLUG,
} from '../constants';

export type ProductShippingSectionProps = {
	product?: PartialProduct;
};

type ServerErrorResponse = {
	code: string;
};

export const DEFAULT_SHIPPING_CLASS_OPTIONS: SelectControl.Option[] = [
	{ value: '', label: __( 'No shipping class', 'woocommerce' ) },
	{
		value: ADD_NEW_SHIPPING_CLASS_OPTION_VALUE,
		label: __( 'Add new shipping class', 'woocommerce' ),
	},
];

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
 * the first category different to `Uncategorized` and check if the
 * category was not added to the shipping class list
 *
 * @see https://github.com/woocommerce/woocommerce/issues/34657
 * @see https://github.com/woocommerce/woocommerce/issues/35037
 * @param product The product
 * @param shippingClasses The shipping classes
 * @return The default shipping class
 */
function extractDefaultShippingClassFromProduct(
	product?: PartialProduct,
	shippingClasses?: ProductShippingClass[]
): Partial< ProductShippingClass > | undefined {
	const category = product?.categories?.find(
		( { slug } ) => slug !== UNCATEGORIZED_CATEGORY_SLUG
	);
	if (
		category &&
		! shippingClasses?.some( ( { slug } ) => slug === category.slug )
	) {
		return {
			name: category.name,
			slug: category.slug,
		};
	}
}

export function ProductShippingSection( {
	product,
}: ProductShippingSectionProps ) {
	const { getInputProps, getSelectControlProps, setValue } =
		useFormContext< PartialProduct >();
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
	const { createErrorNotice } = useDispatch( 'core/notices' );

	const dimensionProps = {
		onBlur: () => {
			setHighlightSide( undefined );
		},
		sanitize: ( value: PartialProduct[ keyof PartialProduct ] ) =>
			parseNumber( String( value ) ),
		suffix: dimensionUnit,
	};

	const inputWidthProps = getInputProps( 'dimensions.width', dimensionProps );
	const inputLengthProps = getInputProps(
		'dimensions.length',
		dimensionProps
	);
	const inputHeightProps = getInputProps(
		'dimensions.height',
		dimensionProps
	);
	const inputWeightProps = getInputProps( 'weight', {
		sanitize: ( value: PartialProduct[ keyof PartialProduct ] ) =>
			parseNumber( String( value ) ),
	} );
	const shippingClassProps = getInputProps( 'shipping_class' );

	function handleShippingClassServerError(
		error: ServerErrorResponse
	): Promise< ProductShippingClass > {
		let message = __(
			'We couldn’t add this shipping class. Try again in a few seconds.',
			'woocommerce'
		);

		if ( error.code === 'term_exists' ) {
			message = __(
				'A shipping class with that slug already exists.',
				'woocommerce'
			);
		}

		createErrorNotice( message, {
			explicitDismiss: true,
		} );

		throw error;
	}

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
								label={ __( 'Shipping class', 'woocommerce' ) }
								{ ...getSelectControlProps( 'shipping_class', {
									className: 'half-width-field',
								} ) }
								onChange={ ( value: string ) => {
									if (
										value ===
										ADD_NEW_SHIPPING_CLASS_OPTION_VALUE
									) {
										setShowShippingClassModal( true );
										return;
									}
									shippingClassProps.onChange( value );
								} }
								options={ [
									...DEFAULT_SHIPPING_CLASS_OPTIONS,
									...mapShippingClassToSelectOption(
										shippingClasses ?? []
									),
								] }
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
												onClick={ () => {
													recordEvent(
														'product_shipping_global_settings_link_click'
													);
												} }
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
												String( inputWidthProps.value )
											) }
											label={ getInterpolatedSizeLabel(
												__(
													'Width {{span}}A{{/span}}',
													'woocommerce'
												)
											) }
											onFocus={ () => {
												setHighlightSide( 'A' );
											} }
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
												String( inputLengthProps.value )
											) }
											label={ getInterpolatedSizeLabel(
												__(
													'Length {{span}}B{{/span}}',
													'woocommerce'
												)
											) }
											onFocus={ () => {
												setHighlightSide( 'B' );
											} }
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
												String( inputHeightProps.value )
											) }
											label={ getInterpolatedSizeLabel(
												__(
													'Height {{span}}C{{/span}}',
													'woocommerce'
												)
											) }
											onFocus={ () => {
												setHighlightSide( 'C' );
											} }
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
												String( inputWeightProps.value )
											) }
											label={ __(
												'Weight',
												'woocommerce'
											) }
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
					shippingClass={ extractDefaultShippingClassFromProduct(
						product,
						shippingClasses
					) }
					onAdd={ ( shippingClassValues ) =>
						createProductShippingClass<
							Promise< ProductShippingClass >
						>( shippingClassValues )
							.then( ( value ) => {
								recordEvent(
									'product_new_shipping_class_modal_add_button_click'
								);
								invalidateResolution(
									'getProductShippingClasses'
								);
								setValue( 'shipping_class', value.slug );
								return value;
							} )
							.catch( handleShippingClassServerError )
					}
					onCancel={ () => setShowShippingClassModal( false ) }
				/>
			) }
		</ProductSectionLayout>
	);
}
