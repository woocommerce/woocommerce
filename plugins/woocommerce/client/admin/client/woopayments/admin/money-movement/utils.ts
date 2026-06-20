/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

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

export const getTransactionDetailsRoute = ( item: {
	id?: string;
	transaction_id?: string;
	charge_id?: string;
	payment_intent_id?: string;
	payment_intent?: string;
	metadata?: Record< string, unknown >;
	type?: string;
	charge?:
		| string
		| {
				id?: string;
				payment_intent?: string;
				balance_transaction?: string | { id?: string };
		  };
} ) => {
	const charge = typeof item.charge === 'object' ? item.charge : undefined;
	const primaryId =
		item.payment_intent_id ||
		item.payment_intent ||
		charge?.payment_intent ||
		item.charge_id ||
		( typeof item.charge === 'string' ? item.charge : charge?.id ) ||
		item.id ||
		item.transaction_id ||
		'';
	const balanceTransaction = charge?.balance_transaction;
	const balanceTransactionId =
		typeof balanceTransaction === 'string'
			? balanceTransaction
			: balanceTransaction?.id;
	const transactionId =
		item.transaction_id ||
		balanceTransactionId ||
		( item.id?.startsWith( 'txn_' ) ? item.id : '' );
	const metadataChargeType = item.metadata?.charge_type;
	const transactionType =
		typeof metadataChargeType === 'string' ? metadataChargeType : item.type;

	return buildPathWithQuery( '/woopayments/transactions/details', {
		id: primaryId,
		transaction_id:
			transactionId && transactionId !== primaryId
				? transactionId
				: undefined,
		transaction_type: transactionType,
	} );
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

export const formatDateTime = ( value?: string | number ) => {
	if ( ! value ) {
		return '-';
	}

	const timestamp =
		typeof value === 'number' && value < 10000000000 ? value * 1000 : value;
	const date = new Date( timestamp );

	if ( Number.isNaN( date.getTime() ) ) {
		return '-';
	}

	return date.toLocaleString( undefined, {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour: 'numeric',
		minute: '2-digit',
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

export const getChargeChannelLabel = (
	paymentMethodType?: string,
	metadata: Record< string, unknown > = {},
	salesChannel?: string
) => {
	const explicitChannel =
		typeof salesChannel === 'string' ? salesChannel : undefined;
	const ippChannel =
		typeof metadata.ipp_channel === 'string'
			? metadata.ipp_channel
			: undefined;
	const channel = explicitChannel || ippChannel;

	if (
		paymentMethodType === 'card_present' ||
		paymentMethodType === 'interac_present' ||
		channel === 'mobile_pos' ||
		channel === 'in_person' ||
		channel === 'pos' ||
		channel === 'terminal'
	) {
		return channel === 'mobile_pos'
			? __( 'In-person (POS)', 'woocommerce' )
			: __( 'In-person', 'woocommerce' );
	}

	if ( channel && channel !== 'online' && channel !== 'online_store' ) {
		return formatLabel( channel );
	}

	return __( 'Online store', 'woocommerce' );
};
