/**
 * Internal dependencies
 */
import { FlowType } from './types';

export const isAIFlow = (
	flowType: FlowType
): flowType is FlowType.AIOffline | FlowType.AIOnline => {
	return flowType === FlowType.AIOnline || flowType === FlowType.AIOffline;
};

export const isNoAIFlow = ( flowType: FlowType ): flowType is FlowType.noAI => {
	return flowType === FlowType.noAI;
};

export const isOnlineAIFlow = (
	flowType: FlowType
): flowType is FlowType.AIOnline => {
	return flowType === FlowType.AIOnline;
};

export const isOfflineAIFlow = (
	flowType: FlowType
): flowType is FlowType.AIOffline => {
	return flowType === FlowType.AIOffline;
};
