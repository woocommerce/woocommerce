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
			onChange={ field.onChange }
			selected={ field.value }
		/>
	);
};
