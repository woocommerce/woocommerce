/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Link, useFormContext } from '@woocommerce/components';
import {
	EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME,
	Product,
	ProductShippingClass,
} from '@woocommerce/data';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import { getTextControlProps } from './utils';
import { ADMIN_URL } from '../../utils/admin-settings';

const DEFAULT_SHIPPING_CLASS_OPTIONS: SelectControl.Option[] = [
	{ value: '', label: __( 'No shipping class', 'woocommerce' ) },
	{ value: '-1', label: __( 'Standard shipping', 'woocommerce' ) },
];

function mapShippingClassToSelectOption(
	shippingClasses: ProductShippingClass[]
): SelectControl.Option[] {
	return shippingClasses.map( ( { id, name } ) => ( {
		value: `${ id }`,
		label: name,
	} ) );
}

export const ProductShippingSection: React.FC = () => {
	const { getInputProps } = useFormContext< Product >();

	const shippingClassOptions = useSelect( ( select ) => {
		const { getProductShippingClasses } = select(
			EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
		);
		const shippingClasses =
			getProductShippingClasses< ProductShippingClass[] >();
		if ( Array.isArray( shippingClasses ) ) {
			return [
				...DEFAULT_SHIPPING_CLASS_OPTIONS,
				...mapShippingClassToSelectOption( shippingClasses ),
			];
		}
		return DEFAULT_SHIPPING_CLASS_OPTIONS;
	}, [] );

	return (
		<ProductSectionLayout
			title={ __( 'Shipping', 'woocommerce' ) }
			description={ __(
				'Set up shipping costs and enter dimensions used for accurate rate calculations.',
				'woocommerce'
			) }
		>
			<SelectControl
				label={ __( 'Shipping class', 'woocommerce' ) }
				{ ...getTextControlProps(
					getInputProps( 'shipping_class_id' )
				) }
				options={ shippingClassOptions }
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
		</ProductSectionLayout>
	);
};
