/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { CheckboxControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getCheckboxTracks } from '../../sections/utils';

export const InventoryStockLimitField = () => {
	const { getCheckboxControlProps } = useFormContext< Product >();

	return (
		<>
			<h4>{ __( 'Restrictions', 'woocommerce' ) }</h4>
			<CheckboxControl
				label={ __(
					'Limit purchases to 1 item per order',
					'woocommerce'
				) }
				{ ...getCheckboxControlProps(
					'sold_individually',
					getCheckboxTracks( 'sold_individually' )
				) }
			/>
		</>
	);
};
