/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';
import {
	setBlockTemplateLoggingThreshold,
	setCronJobs,
	setDBUpdateVersions,
	setIsEmailDisabled,
	setLoggingLevels,
} from './actions';

export function* getCronJobs() {
	const path = `${ API_NAMESPACE }/tools/get-cron-list/v1`;

	try {
		const response = yield apiFetch( {
			path,
			method: 'GET',
		} );
		yield setCronJobs( response );
	} catch ( error ) {
		throw new Error( error );
	}
}

export function* getDBUpdateVersions() {
	const path = `${ API_NAMESPACE }/tools/get-update-versions/v1`;

	try {
		const response = yield apiFetch( {
			path,
			method: 'GET',
		} );
		yield setDBUpdateVersions( response );
	} catch ( error ) {
		throw new Error( error );
	}
}

export function* getIsEmailDisabled() {
	const path = `${ API_NAMESPACE }/tools/get-email-status/v1`;

	try {
		const response = yield apiFetch( {
			path,
			method: 'GET',
		} );
		yield setIsEmailDisabled( response );
	} catch ( error ) {
		yield setIsEmailDisabled( 'error' );
		throw new Error( error );
	}
}

export function* getLoggingLevels() {
	const path = `${ API_NAMESPACE }/tools/get-logging-levels/v1`;

	try {
		const response = yield apiFetch( {
			path,
			method: 'GET',
		} );
		yield setLoggingLevels( response );
	} catch ( error ) {
		throw new Error( error );
	}
}

export function* getBlockTemplateLoggingThreshold() {
	const path = `${ API_NAMESPACE }/tools/get-block-template-logging-threshold/v1`;

	try {
		const response = yield apiFetch( {
			path,
			method: 'GET',
		} );
		yield setBlockTemplateLoggingThreshold( response );
	} catch ( error ) {
		throw new Error( error );
	}
}
