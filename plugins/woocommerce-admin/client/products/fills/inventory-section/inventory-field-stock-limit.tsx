/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext2 } from '@woocommerce/components';
import { CheckboxControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';
import { useController } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { getCheckboxTracks } from '../../sections/utils';

export const InventoryStockLimitField = () => {
	const { control } = useFormContext2< Product >();
	const { field } = useController( {
		name: 'sold_individually',
		control,
	} );

	const { value, ...checkboxProps } = field;

	return (
		<>
			<h4>{ __( 'Restrictions', 'woocommerce' ) }</h4>
			<CheckboxControl
				label={ __(
					'Limit purchases to 1 item per order',
					'woocommerce'
				) }
				{ ...checkboxProps }
				selected={ value }
				{ ...getCheckboxTracks( 'sold_individually' ) }
			/>
		</>
	);
};
