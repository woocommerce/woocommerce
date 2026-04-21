/**
 * External dependencies
 */
import { InputControl } from '@wordpress/ui';
/**
 * Internal dependencies
 */

import type { ProductEntityRecord } from '../types';

type DateFieldKey = 'date_on_sale_from' | 'date_on_sale_to';

type DatePickerProps = {
	data: ProductEntityRecord;
	onChange: ( value: Partial< ProductEntityRecord > ) => void;
	field: {
		id?: string;
		label?: string;
	};
	fieldKey: DateFieldKey;
	min?: Date;
};

export function parseDateTimeLocal( value: string ) {
	if ( ! value ) {
		return null;
	}

	const parsedDate = new Date( value );

	if ( Number.isNaN( parsedDate.getTime() ) ) {
		return null;
	}

	return parsedDate;
}

export function formatDateTimeLocal( date: Date ) {
	const year = date.getFullYear();
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );
	const hours = String( date.getHours() ).padStart( 2, '0' );
	const minutes = String( date.getMinutes() ).padStart( 2, '0' );

	return `${ year }-${ month }-${ day }T${ hours }:${ minutes }`;
}

export function DatePicker( {
	data,
	onChange,
	field,
	fieldKey,
	min,
}: DatePickerProps ) {
	return (
		<InputControl
			label={ field.label }
			type="datetime-local"
			value={ data[ fieldKey ] || '' }
			min={ min ? formatDateTimeLocal( min ) : undefined }
			onChange={ ( event ) =>
				onChange( {
					[ fieldKey ]: event.target.value || null,
				} as Partial< ProductEntityRecord > )
			}
		/>
	);
}
