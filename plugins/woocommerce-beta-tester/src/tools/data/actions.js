/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';

export function addCurrentlyRunning( command ) {
	return {
		type: TYPES.ADD_CURRENTLY_RUNNING,
		command,
	};
}

export function removeCurrentlyRunning( command ) {
	return {
		type: TYPES.REMOVE_CURRENTLY_RUNNING,
		command,
	};
}

export function addMessage( source, message ) {
	return {
		type: TYPES.ADD_MESSAGE,
		source,
		message,
	};
}

export function updateMessage( source, message, status ) {
	return {
		type: TYPES.ADD_MESSAGE,
		source,
		message,
		status,
	};
}

export function removeMessage( source ) {
	return {
		type: TYPES.REMOVE_MESSAGE,
		source,
	};
}

export function updateCommandParams( source, params ) {
	return {
		type: TYPES.ADD_COMMAND_PARAMS,
		source,
		params,
	};
}

export function setCronJobs( cronJobs ) {
	return {
		type: TYPES.SET_CRON_JOBS,
		cronJobs,
	};
}

export function setDBUpdateVersions( versions ) {
	return {
		type: TYPES.SET_DB_UPDATE_VERSIONS,
		versions,
	};
}

export function setIsEmailDisabled( isEmailDisabled ) {
	return {
		type: TYPES.IS_EMAIL_DISABLED,
		isEmailDisabled,
	};
}

function* runCommand( commandName, func ) {
	try {
		yield addCurrentlyRunning( commandName );
		yield addMessage( commandName, 'Executing...' );
		yield func();
		yield removeCurrentlyRunning( commandName );
		yield updateMessage( commandName, 'Successful!' );
	} catch ( e ) {
		yield updateMessage( commandName, e.message, 'error' );
		yield removeCurrentlyRunning( commandName );
	}
}

export function* triggerWcaInstall() {
	yield runCommand( 'Trigger WCA Install', function* () {
		yield apiFetch( {
			path: API_NAMESPACE + '/tools/trigger-wca-install/v1',
			method: 'POST',
		} );
	} );
}

export function* resetOnboardingWizard() {
	yield runCommand( 'Reset Onboarding Wizard', function* () {
		const optionsToDelete = [
			'woocommerce_task_list_tracked_completed_tasks',
			'woocommerce_onboarding_profile',
			'_transient_wc_onboarding_themes',
		];
		yield apiFetch( {
			method: 'DELETE',
			path: `${ API_NAMESPACE }/options/${ optionsToDelete.join( ',' ) }`,
		} );
	} );
}

export function* resetJetpackConnection() {
	yield runCommand( 'Reset Jetpack Connection', function* () {
		yield apiFetch( {
			method: 'DELETE',
			path: `${ API_NAMESPACE }/options/jetpack_options`,
		} );
	} );
}

export function* enableTrackingDebug() {
	yield runCommand( 'Enable WC Admin Tracking Debug Mode', function* () {
		window.localStorage.setItem( 'debug', 'wc-admin:*' );
	} );
}

export function* updateStoreAge() {
	yield runCommand( 'Update Installation timestamp', function* () {
		const today = new Date();
		const dd = String( today.getDate() ).padStart( 2, '0' );
		const mm = String( today.getMonth() + 1 ).padStart( 2, '0' ); //January is 0!
		const yyyy = today.getFullYear();

		// eslint-disable-next-line no-alert
		const numberOfDays = window.prompt(
			'Please enter a date in yyyy/mm/dd format',
			yyyy + '/' + mm + '/' + dd
		);

		if ( numberOfDays !== null ) {
			const dates = numberOfDays.split( '/' );
			const newTimestamp = Math.round(
				new Date( dates[ 0 ], dates[ 1 ] - 1, dates[ 2 ] ).getTime() /
					1000
			);
			const payload = {
				woocommerce_admin_install_timestamp: JSON.parse( newTimestamp ),
			};
			yield apiFetch( {
				method: 'POST',
				path: '/wc-admin/options',
				headers: { 'content-type': 'application/json' },
				body: JSON.stringify( payload ),
			} );
		}
	} );
}

export function* runWcAdminDailyJob() {
	yield runCommand( 'Run wc_admin_daily job', function* () {
		yield apiFetch( {
			path: API_NAMESPACE + '/tools/run-wc-admin-daily/v1',
			method: 'POST',
		} );
	} );
}

export function* deleteAllProducts() {
	if ( ! confirm( 'Are you sure you want to delete all of the products?' ) ) {
		return;
	}

	yield runCommand( 'Delete all products', function* () {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/tools/delete-all-products/v1`,
			method: 'POST',
		} );
	} );
}

export function* runSelectedCronJob( params ) {
	yield runCommand( 'Run selected cron job', function* () {
		yield apiFetch( {
			path: API_NAMESPACE + '/tools/run-wc-admin-daily/v1',
			method: 'POST',
			data: params,
		} );
	} );
}

export function* runSelectedUpdateCallbacks( params ) {
	yield runCommand( 'Run version update callbacks', function* () {
		yield apiFetch( {
			path: API_NAMESPACE + '/tools/trigger-selected-update-callbacks/v1',
			method: 'POST',
			data: params,
		} );
	} );
}

export function* runDisableEmail() {
	yield runCommand( 'Disable/Enable WooCommerce emails', function* () {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/tools/toggle-emails/v1`,
			method: 'POST',
		} );
		yield setIsEmailDisabled( response );
	} );
}

export function* resetCustomizeYourStore() {
	yield runCommand( 'Reset Customize Your Store', function* () {
		const optionsToDelete = [
			'woocommerce_customize_store_onboarding_tour_hidden',
			'woocommerce_admin_customize_store_completed',
			'woocommerce_admin_customize_store_completed_theme_id',
		];
		yield apiFetch( {
			method: 'DELETE',
			path: `${ API_NAMESPACE }/options/${ optionsToDelete.join( ',' ) }`,
		} );

		yield apiFetch( {
			path: API_NAMESPACE + '/tools/reset-cys',
			method: 'POST',
		} );

		yield apiFetch( {
			path: '/wc-admin/ai/patterns',
			method: 'DELETE',
		} );
	} );
}

export function setLoggingLevels( loggingLevels ) {
	return {
		type: TYPES.SET_LOGGING_LEVELS,
		loggingLevels,
	};
}

export function setBlockTemplateLoggingThreshold(
	blockTemplateLoggingThreshold
) {
	return {
		type: TYPES.SET_BLOCK_TEMPLATE_LOGGING_THRESHOLD,
		blockTemplateLoggingThreshold,
	};
}

export function* updateBlockTemplateLoggingThreshold( params ) {
	yield runCommand( 'Update block template logging threshold', function* () {
		yield apiFetch( {
			path:
				API_NAMESPACE +
				'/tools/update-block-template-logging-threshold/v1',
			method: 'POST',
			data: params,
		} );
	} );
}

export function* updateComingSoonMode( params ) {
	yield runCommand( 'Update coming soon mode', function* () {
		yield apiFetch( {
			path: API_NAMESPACE + '/tools/update-coming-soon-mode/v1',
			method: 'POST',
			data: params,
		} );
	} );
}

export function* updateWccomRequestErrorsMode( params ) {
	yield runCommand( 'Update wccom request errors mode', function* () {
		yield apiFetch( {
			path: API_NAMESPACE + '/tools/set-wccom-request-errors/v1',
			method: 'POST',
			data: params,
		} );
	} );
}

export function* fakeWooPayments( params ) {
	yield runCommand( 'Toggle Fake WooPayments Completion', function* () {
		const newStatus = params.enabled === 'yes' ? 'no' : 'yes';

		yield apiFetch( {
			path: API_NAMESPACE + '/tools/fake-wcpay-completion/v1',
			method: 'POST',
			data: {
				enabled: newStatus,
			},
		} );

		yield updateCommandParams( 'fakeWooPayments', {
			enabled: newStatus,
		} );

		yield updateMessage(
			'Toggle Fake WooPayments Completion',
			`Fake WooPayments completion ${
				newStatus === 'yes' ? 'disabled' : 'enabled'
			}`
		);
	} );
}
