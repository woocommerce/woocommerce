/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext, Link } from '@woocommerce/components';
import {
	Product,
	EXPERIMENTAL_TAX_CLASSES_STORE_NAME,
	TaxClass,
} from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { RadioControl } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import { STANDARD_RATE_TAX_CLASS_SLUG } from '../../constants';

export const PricingTaxesClassField = () => {
	const { getInputProps } = useFormContext< Product >();

	const { isResolving: isTaxClassesResolving, taxClasses } = useSelect(
		( select ) => {
			const { hasFinishedResolution, getTaxClasses } = select(
				EXPERIMENTAL_TAX_CLASSES_STORE_NAME
			);
			return {
				isResolving: ! hasFinishedResolution( 'getTaxClasses' ),
				taxClasses: getTaxClasses< TaxClass[] >(),
			};
		}
	);

	const taxClassProps = getInputProps( 'tax_class' );
	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	delete taxClassProps.checked;
	delete taxClassProps.value;

	if ( isTaxClassesResolving || taxClasses.length <= 0 ) {
		return null;
	}

	return (
		<RadioControl
			{ ...taxClassProps }
			label={
				<>
					<span>{ __( 'Tax class', 'woocommerce' ) }</span>
					<span className="woocommerce-product-form__secondary-text">
						{ interpolateComponents( {
							mixedString: __(
								'Apply a tax rate if this product qualifies for tax reduction or exemption. {{link}}Learn more{{/link}}',
								'woocommerce'
							),
							components: {
								link: (
									<Link
										href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/#shipping-tax-class"
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
			}
			options={ taxClasses.map( ( taxClass ) => ( {
				label: taxClass.name,
				value:
					taxClass.slug === STANDARD_RATE_TAX_CLASS_SLUG
						? ''
						: taxClass.slug,
			} ) ) }
		/>
	);
};
