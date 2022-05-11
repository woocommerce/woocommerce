/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { EXPERIMENT_NAME_PREFIX, TRANSIENT_NAME_PREFIX } from './constants';

function toggleFrontendExperiment( experimentName, newVariation ) {
	const storageItem = JSON.parse(
		window.localStorage.getItem( EXPERIMENT_NAME_PREFIX + experimentName )
	);

	storageItem.variationName = newVariation;

	window.localStorage.setItem(
		EXPERIMENT_NAME_PREFIX + experimentName,
		JSON.stringify( storageItem )
	);
}

function* toggleBackendExperiment( experimentName, newVariation ) {
	try {
		const payload = {};
		payload[ TRANSIENT_NAME_PREFIX + experimentName ] = newVariation;
		yield apiFetch( {
			method: 'POST',
			path: '/wc-admin/options',
			headers: { 'content-type': 'application/json' },
			body: JSON.stringify( payload ),
		} );
	} catch ( error ) {
		throw new Error();
	}
}

export function* toggleExperiment( experimentName, currentVariation, source ) {
	const newVariation =
		currentVariation === 'control' ? 'treatment' : 'control';

	if ( source === 'frontend' ) {
		toggleFrontendExperiment( experimentName, newVariation );
	} else {
		yield toggleBackendExperiment( experimentName, newVariation );
	}

	return {
		type: TYPES.TOGGLE_EXPERIMENT,
		experimentName,
		newVariation,
		source,
	};
}

export function setExperiments( experiments ) {
	return {
		type: TYPES.SET_EXPERIMENTS,
		experiments,
	};
}
