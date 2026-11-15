/**
 * Internal dependencies
 */
import { getStateForContext } from './utils';

/**
 * Cache of parsed query-state contexts keyed by the serialized string stored
 * in Redux state. `state[ context ]` is stored as a JSON string so it can be
 * used as a stable cache key elsewhere, and this cache guarantees that parsing
 * the same string twice returns the same object reference. Without this, the
 * selector would produce a fresh object every call, which `@wordpress/data`'s
 * SCRIPT_DEBUG unstable-reference check (added in wp-6.8) correctly flags.
 */
const parsedContextCache = new Map();

const getParsedContext = ( serialized ) => {
	if ( ! parsedContextCache.has( serialized ) ) {
		parsedContextCache.set( serialized, JSON.parse( serialized ) );
	}
	return parsedContextCache.get( serialized );
};

/**
 * Selector for retrieving a specific query-state for the given context.
 *
 * @param {Object} state        Current state.
 * @param {string} context      Context for the query-state being retrieved.
 * @param {string} queryKey     Key for the specific query-state item.
 * @param {*}      defaultValue Default value for the query-state key if it doesn't
 *                              currently exist in state.
 *
 * @return {*} The currently stored value or the defaultValue if not present.
 */
export const getValueForQueryKey = (
	state,
	context,
	queryKey,
	defaultValue = {}
) => {
	const stateContext = getStateForContext( state, context );
	if ( stateContext === null ) {
		return defaultValue;
	}
	const parsed = getParsedContext( stateContext );
	return typeof parsed[ queryKey ] !== 'undefined'
		? parsed[ queryKey ]
		: defaultValue;
};

/**
 * Selector for retrieving the query-state for the given context.
 *
 * @param {Object} state        The current state.
 * @param {string} context      The context for the query-state being retrieved.
 * @param {*}      defaultValue The default value to return if there is no state for
 *                              the given context.
 *
 * @return {*} The currently stored query-state for the given context or
 *             defaultValue if not present in state.
 */
export const getValueForQueryContext = (
	state,
	context,
	defaultValue = {}
) => {
	const stateContext = getStateForContext( state, context );
	return stateContext === null
		? defaultValue
		: getParsedContext( stateContext );
};
