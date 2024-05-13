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
	isFontLibraryAvailable: boolean;
};

export interface Theme {
	_links: {
		'wp:user-global-styles': { href: string }[];
	};
}
