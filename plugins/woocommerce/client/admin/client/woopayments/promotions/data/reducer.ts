/**
 * Internal dependencies
 */
import ACTION_TYPES from './action-types';
import type { PmPromotion } from '../types';

type PmPromotionsState = {
	data: PmPromotion[];
};

const defaultState: PmPromotionsState = {
	data: [],
};

export const receivePmPromotions = (
	state = defaultState,
	action: {
		type?: string;
		promotions?: PmPromotion[];
	}
): PmPromotionsState => {
	switch ( action.type ) {
		case ACTION_TYPES.SET_PM_PROMOTIONS:
			return {
				...state,
				data: action.promotions ?? [],
			};
	}

	return state;
};

export default receivePmPromotions;
