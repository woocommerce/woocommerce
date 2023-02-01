/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext2 } from '@woocommerce/components';
import { RadioControl } from '@wordpress/components';
import { useController } from 'react-hook-form';
import { Product } from '@woocommerce/data';

export const InventoryStockManualField = () => {
	const { control } = useFormContext2< Product >();
	const { field } = useController( {
		name: 'stock_status',
		control,
	} );
	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore

	const { checked, value, ...inputProps } = field;

	return (
		<RadioControl
			label={ __( 'Stock status', 'woocommerce' ) }
			options={ [
				{
					label: __( 'In stock', 'woocommerce' ),
					value: 'instock',
				},
				{
					label: __( 'Out of stock', 'woocommerce' ),
					value: 'outofstock',
				},
				{
					label: __( 'On backorder', 'woocommerce' ),
					value: 'onbackorder',
				},
			] }
			{ ...inputProps }
			selected={ value }
		/>
	);
};
