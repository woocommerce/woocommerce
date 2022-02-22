/**
 * Internal dependencies
 */
import TYPES from './action-types';

const DEFAULT_STATE = {
	experiments: [],
};

const reducer = (state = DEFAULT_STATE, action) => {
	switch (action.type) {
		case TYPES.TOGGLE_EXPERIMENT:
			return {
				...state,
				experiments: state.experiments.map((experiment) => ({
					...experiment,
					variation:
						experiment.name === action.experimentName
							? action.newVariation
							: experiment.variation,
				})),
			};
		case TYPES.SET_EXPERIMENTS:
			return {
				...state,
				experiments: action.experiments,
			};
		default:
			return state;
	}
};

export default reducer;
