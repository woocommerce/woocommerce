/**
 * Internal dependencies
 */
import { ACTION_REGISTER_BINDINGS_SOURCE } from './constants';
import type { RegisterSourceAction } from './types';

/**
 * Register an external source
 *
 * @param {RegisterSourceAction} action Name of the source to register.
 */
export function registerSourceHandler( action: RegisterSourceAction ) {
	const { type, ...settings } = action;
	return {
		type: ACTION_REGISTER_BINDINGS_SOURCE,
		...settings,
	};
}
