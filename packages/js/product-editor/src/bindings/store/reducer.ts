/**
 * Internal dependencies
 */
import { ACTION_REGISTER_BINDINGS_SOURCE } from './constants';
import type { BindingsAction, BindingsStateProps } from './types';

/**
 * Types & Constants
 */
const INITIAL_STATE: BindingsStateProps = {
	sources: {},
};

export default function reducer(
	state: BindingsStateProps | undefined = INITIAL_STATE,
	action: BindingsAction
): BindingsStateProps {
	switch ( action.type ) {
		case ACTION_REGISTER_BINDINGS_SOURCE:
			const { type, name, ...source } = action;

			return {
				...state,
				sources: {
					...state.sources,
					[ name ]: source,
				},
			};
	}

	return state;
}
