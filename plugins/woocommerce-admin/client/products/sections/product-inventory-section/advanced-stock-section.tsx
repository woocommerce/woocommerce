/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { getCheckboxProps } from '../utils';

export const AdvancedStockSection: React.FC = () => {
	const { getInputProps } = useFormContext< Product >();

	return (
		<>
			<h4>{ __( 'Restrictions', 'woocommerce' ) }</h4>
			<CheckboxControl
				label={ __(
					'Limit purchases to 1 item per order',
					'woocommerce'
				) }
				{ ...getCheckboxProps( {
					...getInputProps( 'sold_individually' ),
				} ) }
			/>
		</>
	);
};
