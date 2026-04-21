/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { CheckboxControl } from '../components/compat-controls';

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
				onCheckedChange={ () =>
					onChange( {
						tax_status:
							data.tax_status === 'taxable' ? '' : 'taxable',
					} )
				}
			/>
		);
	},
};
