/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */

import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	label: __( 'Tax Status', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	enableSorting: false,
	Edit: ( { data, onChange, field } ) => {
		return (
			<CheckboxControl
				label={ field.label }
				checked={ data.tax_status === 'taxable' }
				onChange={ () =>
					onChange( {
						tax_status:
							data.tax_status === 'taxable' ? 'none' : 'taxable',
					} )
				}
			/>
		);
	},
};
