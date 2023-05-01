/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { TextControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';

export const InventorySkuField = () => {
	const { getInputProps } = useFormContext< Product >();

	return (
		<TextControl
			label={ __( 'SKU (Stock Keeping Unit)', 'woocommerce' ) }
			{ ...getInputProps( 'sku', {
				className: 'half-width-field',
			} ) }
		/>
	);
};
