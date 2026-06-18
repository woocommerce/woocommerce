/**
 * Internal dependencies
 */
import { formatWooPaymentsAmount } from '../overview/utils';

export const getErrorMessage = ( error: unknown, fallback: string ): string => {
	if ( error instanceof Error && error.message ) {
		return error.message;
	}

	if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		typeof error.message === 'string'
	) {
		return error.message;
	}

	return fallback;
};

export const buildPathWithQuery = (
	path: string,
	query: Record< string, unknown > = {}
) => {
	const params = new URLSearchParams();

	Object.entries( query ).forEach( ( [ key, value ] ) => {
		if ( value === undefined || value === null || value === '' ) {
			return;
		}

		if ( Array.isArray( value ) ) {
			value.forEach( ( item ) => params.append( key, String( item ) ) );
			return;
		}

		params.append( key, String( value ) );
	} );

	const queryString = params.toString();

	return queryString ? `${ path }?${ queryString }` : path;
};

export const getResourceId = ( item: {
	id?: string;
	transaction_id?: string;
	dispute_id?: string;
	charge_id?: string;
} ) =>
	item.id || item.transaction_id || item.dispute_id || item.charge_id || '';

export const getDisputeId = ( item: { id?: string; dispute_id?: string } ) =>
	item.dispute_id || item.id || '';

export const getChargeId = ( item: {
	id?: string;
	charge_id?: string;
	charge?: string | { id?: string };
} ) => {
	if ( typeof item.charge === 'string' ) {
		return item.charge;
	}

	return item.charge_id || item.charge?.id || item.id || '';
};

export const formatDate = ( value?: string | number ) => {
	if ( ! value ) {
		return '-';
	}

	const timestamp =
		typeof value === 'number' && value < 10000000000 ? value * 1000 : value;
	const date = new Date( timestamp );

	if ( Number.isNaN( date.getTime() ) ) {
		return '-';
	}

	return date.toLocaleDateString( undefined, {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	} );
};

export const formatAmount = ( amount?: number, currency?: string ) =>
	typeof amount === 'number'
		? formatWooPaymentsAmount( amount, currency )
		: '-';

export const formatLabel = ( value?: string ) => {
	if ( ! value ) {
		return '-';
	}

	return value
		.replace( /_/g, ' ' )
		.replace( /^\w/, ( match ) => match.toUpperCase() );
};
