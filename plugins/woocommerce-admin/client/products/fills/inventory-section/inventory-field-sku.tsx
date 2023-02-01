/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext2 } from '@woocommerce/components';
import { TextControl } from '@wordpress/components';
import { useController } from 'react-hook-form';
import { Product } from '@woocommerce/data';

export const InventorySkuField = () => {
	const { control } = useFormContext2< Product >();
	const { field } = useController( {
		name: 'sku',
		control,
	} );

	return (
		<TextControl
			label={ __( 'SKU (Stock Keeping Unit)', 'woocommerce' ) }
			{ ...field }
			className="half-width-field"
		/>
	);
};
