/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';

import { useCallback, useMemo, useState } from '@wordpress/element';

import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { getLocalDefaultSaleStart } from '../price/utils';
import {
	DatePicker,
	formatDateTimeLocal,
	parseDateTimeLocal,
} from '../components/date-picker';

const fieldDefinition = {
	type: 'boolean',
	label: __( 'Schedule sale', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	Edit: ( { data, onChange, field } ) => {
		const [ tempDateOnSaleFrom, setTempDateOnSaleFrom ] = useState(
			data.date_on_sale_from || ''
		);
		const [ tempDateOnSaleTo, setTempDateOnSaleTo ] = useState(
			data.date_on_sale_to || ''
		);
		const checked = !! data.date_on_sale_to || !! data.date_on_sale_from;
		const today = useMemo( () => {
			const d = new Date();
			d.setHours( 0, 0, 0, 0 );
			return d;
		}, [] );
		const dateOnSaleFrom = useMemo(
			() =>
				typeof data.date_on_sale_from === 'string' &&
				data.date_on_sale_from
					? parseDateTimeLocal( data.date_on_sale_from )
					: null,
			[ data.date_on_sale_from ]
		);
		const minDateOnSaleTo = useMemo( () => {
			if ( dateOnSaleFrom ) {
				const min = new Date( dateOnSaleFrom );
				min.setMinutes( min.getMinutes() + 1 );
				return min;
			}

			return today;
		}, [ dateOnSaleFrom, today ] );
		const handleScheduleChange = useCallback(
			( value: boolean ) => {
				if ( ! value ) {
					setTempDateOnSaleFrom( data.date_on_sale_from || '' );
					setTempDateOnSaleTo( data.date_on_sale_to || '' );
					onChange( {
						date_on_sale_from: '',
						date_on_sale_to: '',
					} );
					return;
				}

				let nextDateOnSaleFrom =
					data.date_on_sale_from || tempDateOnSaleFrom;
				const nextDateOnSaleTo =
					data.date_on_sale_to || tempDateOnSaleTo;

				if ( ! nextDateOnSaleFrom && ! nextDateOnSaleTo ) {
					nextDateOnSaleFrom = getLocalDefaultSaleStart();
				}

				onChange( {
					date_on_sale_from: nextDateOnSaleFrom,
					date_on_sale_to: nextDateOnSaleTo,
				} );
			},
			[
				data.date_on_sale_from,
				data.date_on_sale_to,
				onChange,
				tempDateOnSaleFrom,
				tempDateOnSaleTo,
			]
		);
		const handleDateOnSaleFromChange = useCallback(
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
			<div className="woocommerce-schedule-sale-control">
				<CheckboxControl
					label={ field.label }
					checked={ checked }
					onChange={ handleScheduleChange }
				/>
				{ checked && (
					<div className="woocommerce-schedule-sale-control__dates">
						<DatePicker
							data={ data }
							onChange={ handleDateOnSaleFromChange }
							field={ {
								label: __( 'Start sale on', 'woocommerce' ),
							} }
							fieldKey="date_on_sale_from"
							min={ today }
						/>
						<DatePicker
							data={ data }
							onChange={ onChange }
							field={ {
								label: __( 'End sale on', 'woocommerce' ),
							} }
							fieldKey="date_on_sale_to"
							min={ minDateOnSaleTo }
						/>
					</div>
				) }
			</div>
		);
	},
	getValue: ( { item } ) =>
		!! item.date_on_sale_to || !! item.date_on_sale_from,
	setValue: ( { item, value } ) => {
		if ( ! value ) {
			return {
				date_on_sale_from: '',
				date_on_sale_to: '',
			};
		}

		let dateOnSaleFrom = item.date_on_sale_from || '';
		const dateOnSaleTo = item.date_on_sale_to || '';

		if ( ! dateOnSaleFrom && ! dateOnSaleTo ) {
			dateOnSaleFrom = getLocalDefaultSaleStart();
		}

		return {
			date_on_sale_from: dateOnSaleFrom,
			date_on_sale_to: dateOnSaleTo,
		};
	},
};
