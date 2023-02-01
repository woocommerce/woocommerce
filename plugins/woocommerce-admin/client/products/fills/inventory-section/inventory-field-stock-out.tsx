/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext2 } from '@woocommerce/components';
import { useController } from 'react-hook-form';
import { RadioControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';

export const InventoryStockOutField = () => {
	const { control } = useFormContext2< Product >();
	const { field } = useController( {
		name: 'backorders',
		control,
	} );

	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	const { value, checked, ...backordersProp } = field;

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
