/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Link, Spinner, useFormContext } from '@woocommerce/components';
import {
	EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME,
	Product,
	ProductShippingClass,
} from '@woocommerce/data';
import interpolateComponents from '@automattic/interpolate-components';
import {
	SelectControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import { getTextControlProps } from './utils';
import { ADMIN_URL } from '../../utils/admin-settings';
import './product-shipping-section.scss';

const DEFAULT_SHIPPING_CLASS_OPTIONS: SelectControl.Option[] = [
	{ value: '', label: __( 'No shipping class', 'woocommerce' ) },
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

export const ProductShippingSection: React.FC = () => {
	const { getInputProps } = useFormContext< Product >();

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

	return (
		<>
			<ProductSectionLayout
				title={ __( 'Shipping', 'woocommerce' ) }
				description={ __(
					'Set up shipping costs and enter dimensions used for accurate rate calculations.',
					'woocommerce'
				) }
			>
				{ hasResolvedShippingClasses ? (
					<div>
						<SelectControl
							label={ __( 'Shipping class', 'woocommerce' ) }
							{ ...getTextControlProps(
								getInputProps( 'shipping_class' )
							) }
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
										>
											<></>
										</Link>
									),
								},
							} ) }
						</span>
					</div>
				) : (
					<div className="product-shipping-section__spinner-wrapper">
						<Spinner />
					</div>
				) }
			</ProductSectionLayout>

			<ProductSectionLayout title={ '' } description={ '' }>
				<h4 className="product-shipping-section__subtitle">
					{ __( 'Dimensions', 'woocommerce' ) }
				</h4>
				<p className="woocommerce-product-form__secondary-text">
					{ __(
						'Enter the size of the product as you’d put it in a shipping box, including packaging like bubble wrap.',
						'woocommerce'
					) }
				</p>
				<div className="product-shipping-section__container">
					<div>
						<div className="components-base-control">
							<div className="components-base-control__field">
								<InputControl
									label={ getInterpolatedSizeLabel(
										__(
											'Width {{span}}A{{/span}}',
											'woocommerce'
										)
									) }
									type="number"
									{ ...getTextControlProps(
										getInputProps( 'dimensions.width' )
									) }
									suffix="cm"
								/>
							</div>
						</div>

						<div className="components-base-control">
							<div className="components-base-control__field">
								<InputControl
									label={ getInterpolatedSizeLabel(
										__(
											'Length {{span}}B{{/span}}',
											'woocommerce'
										)
									) }
									type="number"
									{ ...getTextControlProps(
										getInputProps( 'dimensions.length' )
									) }
									suffix="cm"
								/>
							</div>
						</div>

						<div className="components-base-control">
							<div className="components-base-control__field">
								<InputControl
									label={ getInterpolatedSizeLabel(
										__(
											'Height {{span}}C{{/span}}',
											'woocommerce'
										)
									) }
									type="number"
									{ ...getTextControlProps(
										getInputProps( 'dimensions.height' )
									) }
									suffix="cm"
								/>
							</div>
						</div>

						<div className="components-base-control">
							<div className="components-base-control__field">
								<InputControl
									label={ __( 'Weight', 'woocommerce' ) }
									type="number"
									{ ...getTextControlProps(
										getInputProps( 'weight' )
									) }
									suffix="kg"
								/>
							</div>
						</div>
					</div>
					<div></div>
				</div>
			</ProductSectionLayout>
		</>
	);
};
