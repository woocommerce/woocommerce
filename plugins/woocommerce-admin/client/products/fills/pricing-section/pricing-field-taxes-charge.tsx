/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { RadioControl } from '@wordpress/components';

export const PricingTaxesChargeField = () => {
	const { getInputProps } = useFormContext< Product >();

	const taxStatusProps = getInputProps( 'tax_status' );
	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	delete taxStatusProps.checked;
	delete taxStatusProps.value;

	return (
		<RadioControl
			{ ...taxStatusProps }
			label={ __( 'Charge sales tax on', 'woocommerce' ) }
			options={ [
				{
					label: __( 'Product and shipping', 'woocommerce' ),
					value: 'taxable',
				},
				{
					label: __( 'Only shipping', 'woocommerce' ),
					value: 'shipping',
				},
				{
					label: __( `Don't charge tax`, 'woocommerce' ),
					value: 'none',
				},
			] }
		/>
	);
};
