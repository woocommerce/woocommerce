/**
 * External dependencies
 */
import { date as formatDate } from '@wordpress/date';
import * as DurationFns from 'temporal-polyfill/fns/Duration';
import * as PlainDateFns from 'temporal-polyfill/fns/PlainDate';

/**
 * Resolves an ISO 8601-2 duration to a date relative to today.
 * We use the store timezone (from wp.date.date) so dates match between server and client.
 */
const resolveDuration = ( duration: string, today: string ): string => {
	const [ year, month, day ] = today.split( '-' ).map( Number );

	return PlainDateFns.toString(
		PlainDateFns.add(
			PlainDateFns.create( year, month, day ),
			DurationFns.fromString( duration )
		)
	);
};

// Constraints are re-resolved on every checkout render, so results are memoized. Today's date is
// part of the key so a session crossing midnight still follows the clock.
const resolvedDurations = new Map< string, string | undefined >();

/**
 * Resolves a date field's min/max constraint to a YYYY-MM-DD value for a date input.
 *
 * A constraint is either an absolute YYYY-MM-DD date or an ISO 8601-2 duration relative to today,
 * such as `P1D` or `-P18Y`. WooCommerce resolves the same expression in PHP when the submitted value is validated.
 *
 * @param constraint The constraint as registered, or undefined when the field is unconstrained.
 * @return The resolved date, or undefined if there is no constraint or it could not be parsed.
 */
export const resolveDateConstraint = (
	constraint: string | undefined
): string | undefined => {
	if ( ! constraint ) {
		return undefined;
	}

	const value = constraint.trim();

	// If it's already a date, return it.
	if ( /^\d{4}-\d{2}-\d{2}$/.test( value ) ) {
		return value;
	}

	const today = formatDate( 'Y-m-d', new Date() );
	const cacheKey = `${ value }|${ today }`;

	if ( ! resolvedDurations.has( cacheKey ) ) {
		let resolved: string | undefined;

		try {
			resolved = resolveDuration( value, today );
		} catch {
			resolved = undefined;
		}

		resolvedDurations.set( cacheKey, resolved );
	}

	return resolvedDurations.get( cacheKey );
};
