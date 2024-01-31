/**
 * Internal dependencies
 */
import { FlowType } from '../types';

export type DesignWithoutAIStateMachineContext = {
	startLoadingTime: number | null;
	apiCallLoader: {
		hasErrors: boolean;
	};
	flowType: FlowType.noAI;
};
