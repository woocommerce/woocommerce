/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { RadioControl } from '@wordpress/components';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';

export const ManualStockSection: React.FC = () => {
	const { getInputProps } = useFormContext< Product >();
	const inputProps = getInputProps( 'stock_status' );
	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	delete inputProps.checked;
	delete inputProps.value;

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
		/>
	);
};
