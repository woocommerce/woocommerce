/**
 * External dependencies
 */
import { useCallback, useMemo } from '@wordpress/element';

import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import {
	DatePicker,
	parseDateTimeLocal,
	formatDateTimeLocal,
} from '../components/date-picker';

const fieldDefinition = {
	type: 'datetime',
	label: __( 'Start sale on', 'woocommerce' ),
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
		const today = useMemo( () => {
			const d = new Date();
			d.setHours( 0, 0, 0, 0 );
			return d;
		}, [] );

		const handleChange = useCallback(
			( value: { date_on_sale_from?: string | null } ) => {
				const newStart = value.date_on_sale_from;
				const currentEnd = data.date_on_sale_to;

				if (
					typeof newStart !== 'string' ||
					! newStart ||
					typeof currentEnd !== 'string' ||
					! currentEnd
				) {
					onChange( value );
					return;
				}

				const startDate = parseDateTimeLocal( newStart );
				const endDate = parseDateTimeLocal( currentEnd );

				if (
					startDate &&
					endDate &&
					startDate.getTime() >= endDate.getTime()
				) {
					const newEndDate = new Date( startDate );
					newEndDate.setDate( newEndDate.getDate() + 1 );

					onChange( {
						...value,
						date_on_sale_to: formatDateTimeLocal( newEndDate ),
					} );
					return;
				}

				onChange( value );
			},
			[ data.date_on_sale_to, onChange ]
		);

		return (
			<DatePicker
				data={ data }
				onChange={ handleChange }
				field={ field }
				fieldKey="date_on_sale_from"
				min={ today }
			/>
		);
	},
};
