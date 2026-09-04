/**
 * External dependencies
 */
import { date as formatDate } from '@wordpress/date';
import * as DurationFns from 'temporal-polyfill/fns/Duration';
import * as PlainDateFns from 'temporal-polyfill/fns/PlainDate';

// Remove once TypeScript's lib ships Temporal types.
/* eslint-disable @typescript-eslint/naming-convention -- Temporal's own class names. */
declare const Temporal:
	| {
			PlainDate: {
				from: ( date: string ) => {
					add: ( duration: unknown ) => { toString: () => string };
				};
			};
			Duration: { from: ( duration: string ) => unknown };
	  }
	| undefined;
/* eslint-enable @typescript-eslint/naming-convention */

/**
 * Resolves an ISO 8601-2 duration to a date relative to today.
 * We use the store timezone (from wp.date.date) so dates match between server and client.
 *
 * Uses native `Temporal` where available, falling back to the polyfill.
 */
const resolveDuration = ( duration: string ): string => {
	const today = formatDate( 'Y-m-d', new Date() );

	if ( typeof Temporal !== 'undefined' ) {
		return Temporal.PlainDate.from( today )
			.add( Temporal.Duration.from( duration ) )
			.toString();
	}

	const [ year, month, day ] = today.split( '-' ).map( Number );

	return PlainDateFns.toString(
		PlainDateFns.add(
			PlainDateFns.create( year, month, day ),
			DurationFns.fromString( duration )
		)
	);
};

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

	try {
		return resolveDuration( value );
	} catch {
		return undefined;
	}
};
