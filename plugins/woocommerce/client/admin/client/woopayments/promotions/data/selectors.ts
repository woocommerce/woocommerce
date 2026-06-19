/**
 * Internal dependencies
 */
import type { PmPromotion } from '../types';

type PmPromotionsRootState = {
	promotions?: {
		data?: PmPromotion[];
	};
};

const EMPTY_ARR: PmPromotion[] = [];

export const getPmPromotions = ( state: PmPromotionsRootState ) => {
	return state.promotions?.data || EMPTY_ARR;
};
