/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';

export function getNoticesRequest() {
	return {
		type: TYPES.GET_NOTICES_REQUEST,
	};
}

export function getNoticesSuccess( notices ) {
	return {
		type: TYPES.GET_NOTICES_SUCCESS,
		notices,
	};
}

export function getNoticesError( error ) {
	return {
		type: TYPES.GET_NOTICES_ERROR,
		error,
	};
}

export function dismissNoticeRequest( id ) {
	return {
		type: TYPES.DISMISS_NOTICE_REQUEST,
		id,
	};
}

export function dismissNoticeSuccess( id ) {
	return {
		type: TYPES.DISMISS_NOTICE_SUCCESS,
		id,
	};
}

export function dismissNoticeError( id, error ) {
	return {
		type: TYPES.DISMISS_NOTICE_ERROR,
		id,
		error,
	};
}

export function* dismissNotice( id ) {
	yield dismissNoticeRequest( id );

	try {
		const url = `${ WC_ADMIN_NAMESPACE }/notices/${ id }/dismiss`;
		const response = yield apiFetch( { path: url, method: 'DELETE' } );
		yield dismissNoticeSuccess( id );
		return response;
	} catch ( error ) {
		yield dismissNoticeError( id, error );
		throw new Error( error );
	}
}
