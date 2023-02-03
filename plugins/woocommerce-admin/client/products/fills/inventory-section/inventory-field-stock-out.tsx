/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { RadioControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';

export const InventoryStockOutField = () => {
	const { getInputProps } = useFormContext< Product >();

	const backordersProp = getInputProps( 'backorders' );
	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	delete backordersProp.checked;
	delete backordersProp.value;

	return (
		<RadioControl
			label={ __( 'When out of stock', 'woocommerce' ) }
			options={ [
				{
					label: __( 'Allow purchases', 'woocommerce' ),
					value: 'yes',
				},
				{
					label: __(
						'Allow purchases, but notify customers',
						'woocommerce'
					),
					value: 'notify',
				},
				{
					label: __( "Don't allow purchases", 'woocommerce' ),
					value: 'no',
				},
			] }
			{ ...backordersProp }
		/>
	);
};
