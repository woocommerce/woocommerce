/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { DatePicker, parseDateTimeLocal } from '../components/date-picker';

const fieldDefinition = {
	type: 'datetime',
	label: __( 'End sale on', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	isVisible: ( product ) => {
		return !! product.date_on_sale_to || !! product.date_on_sale_from;
	},
	Edit: ( { data, onChange, field } ) => {
		const dateOnSaleFrom = useMemo(
			() =>
				typeof data?.date_on_sale_from === 'string' &&
				data.date_on_sale_from
					? parseDateTimeLocal( data.date_on_sale_from )
					: null,
			[ data?.date_on_sale_from ]
		);

		const minDate = useMemo( () => {
			if ( dateOnSaleFrom ) {
				const min = new Date( dateOnSaleFrom );
				min.setMinutes( min.getMinutes() + 1 );
				return min;
			}
			const today = new Date();
			today.setHours( 0, 0, 0, 0 );
			return today;
		}, [ dateOnSaleFrom ] );

		return (
			<DatePicker
				data={ data }
				onChange={ onChange }
				field={ field }
				fieldKey="date_on_sale_to"
				min={ minDate }
			/>
		);
	},
};
