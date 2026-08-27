/**
 * External dependencies
 */
import { date as formatDate } from '@wordpress/date';
import * as DurationFns from 'temporal-polyfill/fns/Duration';
import * as PlainDateFns from 'temporal-polyfill/fns/PlainDate';

const ABSOLUTE_DATE = /^\d{4}-\d{2}-\d{2}$/;

/**
 * Today's date in the store's timezone.
 *
 * Read from the shopper's clock rather than the rendered markup, so a cached page or a tab left open
 * across midnight still constrains the picker to the right day.
 */
const getToday = () => {
	const [ year, month, day ] = formatDate( 'Y-m-d', new Date() )
		.split( '-' )
		.map( Number );

	return PlainDateFns.create( year, month, day );
};

/**
 * Resolves a date field's min/max constraint to a YYYY-MM-DD date.
 *
 * A constraint is either an absolute YYYY-MM-DD date or an ISO 8601-2 duration relative to today, such as
 * `P1D` or `-P18Y`. DateFieldType::resolve_constraint() resolves the same expression in PHP when the submitted
 * value is validated, and matches Temporal's default `constrain` overflow so both agree on month arithmetic.
 *
 * @param constraint The constraint as registered, or undefined when the field is unconstrained.
 * @return The resolved date, or undefined if there is no constraint or it could not be parsed.
 */
export const resolveDateConstraint = (
	constraint: string | undefined
): string | undefined => {
	if ( typeof constraint !== 'string' ) {
		return undefined;
	}

	const value = constraint.trim();

	if ( ABSOLUTE_DATE.test( value ) ) {
		return value;
	}

	try {
		return PlainDateFns.toBasicString(
			PlainDateFns.add( getToday(), DurationFns.fromString( value ) )
		);
	} catch {
		return undefined;
	}
};
