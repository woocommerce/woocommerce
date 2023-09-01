/**
 * External dependencies
 */
import { assign } from 'xstate';

/**
 * Internal dependencies
 */
import {
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents,
} from './types';
import { businessInfoDescriptionCompleteEvent } from './pages';

const assignBusinessInfoDescription = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	businessInfoDescription: ( context, event: unknown ) => {
		return {
			descriptionText: ( event as businessInfoDescriptionCompleteEvent )
				.payload,
		};
	},
} );
export const actions = {
	assignBusinessInfoDescription,
};
