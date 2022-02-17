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
			let experiments = [...state.experiments];
			experiments = experiments.map((experiment) => {
				if (experiment.name === action.experimentName) {
					experiment.variation = action.newVariation;
				}
				return experiment;
			});
			return {
				...state,
				experiments,
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
