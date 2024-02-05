/**
 * Internal dependencies
 */
import { DesignWithoutAIStateMachineContext } from './types';

export const hasFontLibraryInstalled = (
	context: DesignWithoutAIStateMachineContext
) => {
	return context.isFontLibraryInstalled;
};
