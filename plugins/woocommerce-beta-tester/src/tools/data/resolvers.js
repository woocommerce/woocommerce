/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';
import {
	setCronJobs,
	setDBUpdateVersions,
	setIsEmailDisabled,
	setLoggingLevels,
	updateCommandParams,
} from './actions';
import { UPDATE_BLOCK_TEMPLATE_LOGGING_THRESHOLD_ACTION_NAME } from '../commands/update-block-template-logging-threshold';
import { UPDATE_COMING_SOON_MODE_ACTION_NAME } from '../commands/set-coming-soon-mode';
import { TRIGGER_UPDATE_CALLBACKS_ACTION_NAME } from '../commands/trigger-update-callbacks';
import { UPDATE_WCCOM_REQUEST_ERRORS_MODE } from '../commands/set-wccom-request-errors';
import { FAKE_WOO_PAYMENTS_ACTION_NAME } from '../commands/fake-woo-payments';

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
		const dbUpdateVersions = yield apiFetch( {
			path,
			method: 'GET',
		} );

		dbUpdateVersions.reverse();
		yield setDBUpdateVersions( dbUpdateVersions );
		yield updateCommandParams( TRIGGER_UPDATE_CALLBACKS_ACTION_NAME, {
			version:
				Array.isArray( dbUpdateVersions ) && dbUpdateVersions.length > 0
					? dbUpdateVersions[ 0 ]
					: null,
		} );
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
		const threshold = yield apiFetch( {
			path,
			method: 'GET',
		} );
		yield updateCommandParams(
			UPDATE_BLOCK_TEMPLATE_LOGGING_THRESHOLD_ACTION_NAME,
			{
				threshold,
			}
		);
	} catch ( error ) {
		throw new Error( error );
	}
}

export function* getComingSoonMode() {
	const path = `${ API_NAMESPACE }/tools/get-force-coming-soon-mode/v1`;

	try {
		const mode = yield apiFetch( {
			path,
			method: 'GET',
		} );
		yield updateCommandParams( UPDATE_COMING_SOON_MODE_ACTION_NAME, {
			mode: mode || 'disabled',
		} );
	} catch ( error ) {
		throw new Error( error );
	}
}

export function* getWccomRequestErrorsMode() {
	const path = `${ API_NAMESPACE }/tools/get-wccom-request-errors/v1`;

	try {
		const mode = yield apiFetch( {
			path,
			method: 'GET',
		} );

		yield updateCommandParams( UPDATE_WCCOM_REQUEST_ERRORS_MODE, {
			mode: mode || 'disabled',
		} );
	} catch ( error ) {
		throw new Error( error );
	}
}

export function* getIsFakeWooPaymentsEnabled() {
	try {
		const response = yield apiFetch( {
			path: API_NAMESPACE + '/tools/fake-wcpay-completion/v1',
			method: 'GET',
		} );
		yield updateCommandParams( FAKE_WOO_PAYMENTS_ACTION_NAME, {
			enabled: response.enabled || 'no',
		} );
	} catch ( error ) {
		throw new Error( error );
	}
}
