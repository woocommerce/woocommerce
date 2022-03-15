/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { setFeatures, setModifiedFeatures } from './actions';
import { API_NAMESPACE, OPTION_NAME_PREFIX } from './constants';

export function* getModifiedFeatures() {
	try {
		const response = yield apiFetch({
			path: `${API_NAMESPACE}/options?search=` + OPTION_NAME_PREFIX,
		});

		yield setModifiedFeatures(Object.keys(response));
	} catch (error) {
		throw new Error();
	}
}

export function* getFeatures() {
	try {
		const response = yield apiFetch({
			path: `${API_NAMESPACE}/features`,
		});

		yield setFeatures(response);
	} catch (error) {
		throw new Error();
	}
}
