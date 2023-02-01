/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext2 } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { RadioControl } from '@wordpress/components';
import { useController } from 'react-hook-form';

export const PricingTaxesChargeField = () => {
	const { control } = useFormContext2< Product >();
	const { field } = useController( {
		name: 'tax_status',
		control,
	} );

	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	const { value, ref, ...taxStatusProps } = field;

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
