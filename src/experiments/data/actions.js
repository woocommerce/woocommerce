/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { EXPERIMENT_NAME_PREFIX } from './constants';

export function toggleExperiment( experimentName ) {
	const storageItem = JSON.parse(
		window.localStorage.getItem( EXPERIMENT_NAME_PREFIX + experimentName )
	);

	const newVariation =
		storageItem.variationName === 'control' ? 'treatment' : 'control';

	storageItem.variationName = newVariation;

	window.localStorage.setItem(
		EXPERIMENT_NAME_PREFIX + experimentName,
		JSON.stringify( storageItem )
	);

	return {
		type: TYPES.TOGGLE_EXPERIMENT,
		experimentName,
		newVariation,
	};
}

export function setExperiments( experiments ) {
	return {
		type: TYPES.SET_EXPERIMENTS,
		experiments,
	};
}
