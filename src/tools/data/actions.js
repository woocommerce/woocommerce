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

export function updateMssage( source, message, status ) {
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

function* runCommand( id, func ) {
	try {
		yield addCurrentlyRunning( id );
		yield addMessage( id, 'Executing...' );
		yield func();
		yield removeCurrentlyRunning( id );
		yield updateMssage( id, 'Successful!' );
	} catch ( e ) {
		yield updateMssage( id, e.message, 'error' );
		yield removeCurrentlyRunning( id );
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
	yield runCommand( 'Enable WC Admin Tracking Deubg Mode', function* () {
		window.localStorage.setItem( 'debug', 'wc-admin:*' );
	} );
}

export function* updateStoreAge() {
	yield runCommand( 'Update Instllation timestamp', function* () {
		const today = new Date();
		const dd = String( today.getDate() ).padStart( 2, '0' );
		const mm = String( today.getMonth() + 1 ).padStart( 2, '0' ); //January is 0!
		const yyyy = today.getFullYear();

		// eslint-disable-next-line no-alert
		const numberOfDays = window.prompt(
			'Please a date in mm/dd/yyy format',
			mm + '/' + dd + '/' + yyyy
		);

		if ( numberOfDays !== null ) {
			const dates = numberOfDays.split( '/' );
			const newTimestamp = Math.round(
				new Date( dates[ 2 ], dates[ 0 ] - 1, dates[ 1 ] ).getTime() /
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
