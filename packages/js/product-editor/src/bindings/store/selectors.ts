/**
 * Internal dependencies
 */
import type { BindingsSourcesProps, BindingsStateProps } from './types';

/**
 * Returns all the bindings sources registered.
 *
 * @param {Object} state - Data state.
 * @return {Object}        All registered sources handlers.
 */
export function getAllBindingsSources(
	state: BindingsStateProps
): BindingsSourcesProps {
	return state.sources;
}

/**
 * Returns a specific bindings source.
 *
 * @param {Object} state      - Data state.
 * @param {string} sourceName - Source handler name.
 * @return {Object}             The specific binding source.
 */
export function getBindingsSource(
	state: BindingsStateProps,
	sourceName: string
): BindingsSourcesProps[ string ] {
	return state.sources[ sourceName ];
}
